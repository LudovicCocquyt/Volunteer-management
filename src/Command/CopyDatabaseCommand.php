<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Copie toutes les données de l'ancienne base (SOURCE_DATABASE_URL, PostgreSQL)
 * vers la base courante (DATABASE_URL, MySQL/MariaDB), table par table, en
 * conservant les identifiants.
 *
 * Le schéma cible doit déjà exister (doctrine:migrations:migrate).
 * Commande à usage unique : à supprimer une fois la migration terminée.
 */
#[AsCommand(
    name: 'app:database:copy-from-source',
    description: 'Copie les données de SOURCE_DATABASE_URL vers DATABASE_URL',
)]
final class CopyDatabaseCommand extends Command
{
    private const SKIPPED_TABLES = ['doctrine_migration_versions', 'messenger_messages'];

    public function __construct(
        private readonly Connection $target,
        #[Autowire('%env(default::SOURCE_DATABASE_URL)%')]
        private readonly ?string $sourceUrl = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('purge', null, InputOption::VALUE_NONE, 'Vide les tables cibles avant la copie (pour relancer la copie)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (empty($this->sourceUrl)) {
            $io->error('Définissez SOURCE_DATABASE_URL (URL de l\'ancienne base PostgreSQL) dans .env.local.');

            return Command::FAILURE;
        }

        $parser = new DsnParser([
            'postgresql' => 'pdo_pgsql',
            'postgres'   => 'pdo_pgsql',
            'pgsql'      => 'pdo_pgsql',
            'mysql'      => 'pdo_mysql',
        ]);
        $source = DriverManager::getConnection($parser->parse($this->sourceUrl));

        $io->title('Copie des données');
        $io->text(sprintf('Source : %s (%s)', $source->getDatabase(), $source->getDatabasePlatform()::class));
        $io->text(sprintf('Cible  : %s (%s)', $this->target->getDatabase(), $this->target->getDatabasePlatform()::class));

        $targetSchema = $this->target->createSchemaManager();
        $sourceSchema = $source->createSchemaManager();

        $tables = array_values(array_diff($this->tableNames($targetSchema->listTableNames()), self::SKIPPED_TABLES));
        // PostgreSQL renvoie les mots réservés entre guillemets ("user") : on normalise
        $sourceTables = $this->tableNames($sourceSchema->listTableNames());
        if ([] === $tables) {
            $io->error('Aucune table dans la base cible : lancez d\'abord doctrine:migrations:migrate.');

            return Command::FAILURE;
        }

        // Sécurité : on ne copie jamais par-dessus des données existantes sans --purge
        $notEmpty = array_filter($tables, fn (string $t) => $this->count($this->target, $t) > 0);
        if ([] !== $notEmpty && !$input->getOption('purge')) {
            $io->error(sprintf('Tables cibles non vides : %s. Relancez avec --purge pour les vider.', implode(', ', $notEmpty)));

            return Command::FAILURE;
        }

        if (!$io->confirm(sprintf('Copier %d table(s) vers "%s" ?', count($tables), $this->target->getDatabase()), false)) {
            return Command::SUCCESS;
        }

        $report = [];
        $this->target->executeStatement('SET FOREIGN_KEY_CHECKS = 0');
        $this->target->beginTransaction();

        try {
            foreach ($tables as $table) {
                $quotedTable = $this->target->quoteIdentifier($table);

                if ($input->getOption('purge')) {
                    $this->target->executeStatement('DELETE FROM ' . $quotedTable);
                }

                if (!in_array($table, $sourceTables, true)) {
                    $report[] = [$table, '-', 0, 'ABSENTE DE LA SOURCE'];
                    continue;
                }

                $columns = array_map(
                    fn ($column) => $column->getName(),
                    $targetSchema->listTableColumns($table)
                );

                $rows = $source->fetchAllAssociative('SELECT * FROM ' . $source->quoteIdentifier($table));
                foreach ($rows as $row) {
                    $data = [];
                    foreach ($columns as $column) {
                        if (!array_key_exists($column, $row)) {
                            continue; // colonne absente côté source : valeur par défaut MySQL
                        }
                        $value = $row[$column];
                        // pdo_pgsql renvoie de vrais booléens, MySQL attend 0/1
                        $data[$this->target->quoteIdentifier($column)] = is_bool($value) ? (int) $value : $value;
                    }
                    $this->target->insert($quotedTable, $data);
                }

                $copied = $this->count($this->target, $table);
                $report[] = [$table, count($rows), $copied, count($rows) === $copied ? 'OK' : 'ÉCART'];
            }

            $this->target->commit();
        } catch (\Throwable $e) {
            $this->target->rollBack();
            $this->target->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
            $io->error('Copie annulée, aucune donnée écrite : ' . $e->getMessage());

            return Command::FAILURE;
        }

        $this->target->executeStatement('SET FOREIGN_KEY_CHECKS = 1');

        $io->table(['Table', 'Lignes source', 'Lignes copiées', 'Statut'], $report);

        $ignored = array_diff($sourceTables, $tables, self::SKIPPED_TABLES);
        if ([] !== $ignored) {
            $io->note('Tables de la source non copiées (absentes du schéma actuel) : ' . implode(', ', $ignored));
        }

        if (array_intersect(['ÉCART', 'ABSENTE DE LA SOURCE'], array_column($report, 3))) {
            $io->warning('Des écarts ont été détectés : vérifiez avant de mettre en service.');

            return Command::FAILURE;
        }

        $io->success('Toutes les données ont été copiées.');

        return Command::SUCCESS;
    }

    /**
     * @param string[] $names
     *
     * @return string[]
     */
    private function tableNames(array $names): array
    {
        return array_map(fn (string $name) => trim($name, '"`'), $names);
    }

    private function count(Connection $connection, string $table): int
    {
        return (int) $connection->fetchOne('SELECT COUNT(*) FROM ' . $connection->quoteIdentifier($table));
    }
}

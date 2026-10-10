<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Dotenv\Dotenv;

/**
 * Vérifie la configuration de production après chaque déploiement :
 *   php bin/console app:security:check --env=prod
 */
#[AsCommand(
    name: 'app:security:check',
    description: 'Vérifie la configuration de sécurité de l\'environnement (à lancer en prod après chaque déploiement)',
)]
final class SecurityCheckCommand extends Command
{
    /** Fichiers PHP légitimes dans public/ */
    private const ALLOWED_PUBLIC_PHP = ['index.php'];

    /**
     * Empreintes SHA-256 des secrets ayant été publiés (historique Git du .env).
     * Une empreinte ne permet pas de retrouver le secret : elle peut être versionnée.
     */
    private const LEAKED_SECRET_SHA256 = [
        // 'empreinte_sha256', (calcul : voir la section Sécurité du README)
    ];

    public function __construct(
        #[Autowire('%kernel.environment%')] private readonly string $environment,
        #[Autowire('%kernel.debug%')] private readonly bool $debug,
        #[Autowire('%kernel.secret%')] private readonly string $secret,
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
        #[Autowire('%uploads_dir%')] private readonly string $uploadsDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Contrôle de sécurité');

        $checks = [
            $this->check('APP_ENV vaut "prod"', 'prod' === $this->environment, sprintf('valeur actuelle : "%s"', $this->environment)),
            $this->check('APP_DEBUG désactivé', !$this->debug, 'mettre APP_DEBUG=0 dans .env.local'),
            $this->check('APP_SECRET défini (32 caractères minimum)', strlen($this->secret) >= 32, 'générer : php -r \'echo bin2hex(random_bytes(32));\''),
            $this->check('APP_SECRET différent de celui publié sur GitHub', !$this->isCommittedSecret(), 'le secret utilisé est celui du .env versionné : en générer un nouveau dans .env.local'),
            $this->check('Outils de dev absents (composer install --no-dev)', !class_exists('Symfony\Bundle\WebProfilerBundle\WebProfilerBundle'), 'relancer : composer install --no-dev --optimize-autoloader'),
            $this->check('Pièces jointes stockées hors de public/', !str_starts_with(realpath($this->uploadsDir) ?: $this->uploadsDir, $this->projectDir . '/public'), sprintf('uploads_dir = %s', $this->uploadsDir)),
            $this->check('Aucun ancien dossier public/uploads', !is_dir($this->projectDir . '/public/uploads'), 'déplacer son contenu dans var/uploads puis le supprimer'),
        ];

        $unexpectedPhp = $this->unexpectedPublicPhpFiles();
        $checks[] = $this->check(
            'Aucun fichier PHP inattendu dans public/',
            [] === $unexpectedPhp,
            'à examiner puis supprimer : ' . implode(', ', $unexpectedPhp)
        );

        $io->table(['Contrôle', 'Résultat', 'Action'], $checks);

        $failures = count(array_filter($checks, fn (array $c) => '✗ ÉCHEC' === $c[1]));
        if ($failures > 0) {
            $io->error(sprintf('%d contrôle(s) en échec.', $failures));

            return Command::FAILURE;
        }

        $io->success('Configuration conforme.');

        return Command::SUCCESS;
    }

    private function check(string $label, bool $ok, string $action): array
    {
        return [$label, $ok ? '✓ OK' : '✗ ÉCHEC', $ok ? '' : $action];
    }

    /** Le secret en service est-il celui écrit dans le .env versionné (donc public) ? */
    private function isCommittedSecret(): bool
    {
        if (in_array(hash('sha256', $this->secret), self::LEAKED_SECRET_SHA256, true)) {
            return true;
        }

        $envFile = $this->projectDir . '/.env';
        if (!is_file($envFile)) {
            return false;
        }

        $committed = (new Dotenv())->parse((string) file_get_contents($envFile), $envFile)['APP_SECRET'] ?? '';

        return '' !== $committed && hash_equals($committed, $this->secret);
    }

    /** @return string[] */
    private function unexpectedPublicPhpFiles(): array
    {
        $publicDir = $this->projectDir . '/public';
        if (!is_dir($publicDir)) {
            return [];
        }

        $files    = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($publicDir, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if (!$file->isFile() || !preg_match('/\.(php\d*|phtml|phar|phps)$/i', $file->getFilename())) {
                continue;
            }
            $relative = ltrim(substr($file->getPathname(), strlen($publicDir)), '/');
            if (!in_array($relative, self::ALLOWED_PUBLIC_PHP, true)) {
                $files[] = 'public/' . $relative;
            }
        }

        return $files;
    }
}

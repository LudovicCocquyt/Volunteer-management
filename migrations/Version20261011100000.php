<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Étape 1/2 : création de la table availability et reprise des données.
 *
 * - Chaque élément du JSON subscriptions.availabilities devient une ligne availability.
 * - Chaque affectation de subscriptions_plans renseigne availability.plan_id
 *   (la disponibilité est créée si elle manquait dans le JSON).
 * - Un bénévole ne peut avoir qu'une affectation par horaire : en cas de doublon,
 *   le créneau le plus ancien (plus petit id) est conservé.
 *
 * Les anciennes données (JSON et table subscriptions_plans) ne sont PAS supprimées ici :
 * c'est l'étape 2 (Version20261011100100), à lancer une fois la reprise vérifiée.
 */
final class Version20261011100000 extends AbstractMigration
{
    // MariaDB valide automatiquement chaque instruction DDL (CREATE/ALTER/DROP)
    public function isTransactional(): bool
    {
        return false;
    }

    public function getDescription(): string
    {
        return 'Disponibilités : table availability + reprise du JSON et des affectations';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE availability (
            id INT AUTO_INCREMENT NOT NULL,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            subscription_id INT NOT NULL,
            plan_id INT DEFAULT NULL,
            INDEX IDX_3FB7A2BF9A1887DC (subscription_id),
            INDEX IDX_3FB7A2BFE899029B (plan_id),
            UNIQUE INDEX availability_slot_unique (subscription_id, start_date, end_date),
            PRIMARY KEY (id)
        ) DEFAULT CHARACTER SET utf8mb4 ENGINE = InnoDB');
        $this->addSql('ALTER TABLE availability ADD CONSTRAINT FK_3FB7A2BF9A1887DC FOREIGN KEY (subscription_id) REFERENCES subscriptions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE availability ADD CONSTRAINT FK_3FB7A2BFE899029B FOREIGN KEY (plan_id) REFERENCES plans (id) ON DELETE SET NULL');

        // 1. Disponibilités déclarées (JSON)
        $this->addSql("INSERT IGNORE INTO availability (subscription_id, plan_id, start_date, end_date)
            SELECT s.id, NULL,
                   CAST(REPLACE(LEFT(j.start_date, 19), 'T', ' ') AS DATETIME),
                   CAST(REPLACE(LEFT(j.end_date, 19), 'T', ' ') AS DATETIME)
            FROM subscriptions s,
                 JSON_TABLE(s.availabilities, '$[*]' COLUMNS (
                     start_date VARCHAR(40) PATH '$.startDate',
                     end_date   VARCHAR(40) PATH '$.endDate'
                 )) j
            WHERE s.availabilities IS NOT NULL
              AND JSON_VALID(s.availabilities)
              AND j.start_date IS NOT NULL
              AND j.end_date IS NOT NULL");

        // 2. Affectations sans disponibilité correspondante dans le JSON : on crée la disponibilité
        $this->addSql('INSERT IGNORE INTO availability (subscription_id, plan_id, start_date, end_date)
            SELECT sp.subscriptions_id, NULL, p.start_date, p.end_date
            FROM subscriptions_plans sp
            INNER JOIN plans p ON p.id = sp.plans_id');

        // 3. Rattachement des affectations (un créneau par horaire et par bénévole)
        $this->addSql('UPDATE availability a
            INNER JOIN (
                SELECT sp.subscriptions_id AS subscription_id, p.start_date, p.end_date, MIN(p.id) AS plan_id
                FROM subscriptions_plans sp
                INNER JOIN plans p ON p.id = sp.plans_id
                GROUP BY sp.subscriptions_id, p.start_date, p.end_date
            ) x ON x.subscription_id = a.subscription_id
               AND x.start_date = a.start_date
               AND x.end_date = a.end_date
            SET a.plan_id = x.plan_id');
    }

    public function postUp(Schema $schema): void
    {
        // Rapport de contrôle affiché dans la console
        $json = (int) $this->connection->fetchOne("SELECT COALESCE(SUM(JSON_LENGTH(availabilities)), 0) FROM subscriptions WHERE availabilities IS NOT NULL AND JSON_VALID(availabilities)");
        $links = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM subscriptions_plans');
        $slots = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM availability');
        $assigned = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM availability WHERE plan_id IS NOT NULL');

        $this->write(sprintf(
            '<info>Reprise : %d disponibilités dans l\'ancien JSON, %d affectations dans subscriptions_plans -> %d disponibilités dont %d affectées.</info>',
            $json,
            $links,
            $slots,
            $assigned
        ));
        if ($assigned < $links) {
            $this->write(sprintf(
                '<comment>%d affectation(s) en double sur un même horaire pour un même bénévole : seule la plus ancienne a été conservée.</comment>',
                $links - $assigned
            ));
        }
    }

    public function down(Schema $schema): void
    {
        // Les anciennes données sont intactes tant que l'étape 2 n'a pas été jouée
        $this->addSql('DROP TABLE availability');
    }
}

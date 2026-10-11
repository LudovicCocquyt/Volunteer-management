<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Étape 2/2 : suppression des anciennes données (JSON subscriptions.availabilities
 * et table subscriptions_plans), devenues inutiles.
 *
 * Irréversible : à lancer seulement après avoir vérifié la reprise de l'étape 1,
 * et avec une sauvegarde de la base.
 */
final class Version20261011100100 extends AbstractMigration
{
    // MariaDB valide automatiquement chaque instruction DDL (CREATE/ALTER/DROP)
    public function isTransactional(): bool
    {
        return false;
    }

    public function getDescription(): string
    {
        return 'Disponibilités : suppression du JSON et de la table subscriptions_plans';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP TABLE subscriptions_plans');
        $this->addSql('ALTER TABLE subscriptions DROP availabilities');
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Restaurer la sauvegarde de la base pour revenir en arrière.');
    }
}

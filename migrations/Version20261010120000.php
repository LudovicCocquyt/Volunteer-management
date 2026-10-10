<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Mise en place des rôles : tous les comptes créés avant cette version
 * deviennent super admin (ils avaient jusqu'ici tous les droits).
 * Les comptes créés ensuite depuis l'interface sont organisateurs par défaut.
 */
final class Version20261010120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Promotion de tous les comptes existants en ROLE_SUPER_ADMIN';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(sprintf(
            'UPDATE %s SET roles = \'["ROLE_SUPER_ADMIN"]\'',
            $this->platform->quoteSingleIdentifier('user')
        ));
    }

    public function down(Schema $schema): void
    {
        // Avant cette migration, aucun compte n'avait de rôle stocké (ROLE_USER implicite)
        $this->addSql(sprintf(
            'UPDATE %s SET roles = \'[]\'',
            $this->platform->quoteSingleIdentifier('user')
        ));
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250912131159 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events ADD display_start_at BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE events ADD display_location BOOLEAN DEFAULT true NOT NULL');
        $this->addSql('ALTER TABLE events ADD scheduling_auto BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE events DROP display_start_at');
        $this->addSql('ALTER TABLE events DROP display_location');
        $this->addSql('ALTER TABLE events DROP scheduling_auto');
    }
}

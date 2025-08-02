<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250802115222 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events ALTER location DROP NOT NULL');
        $this->addSql('ALTER TABLE plans ADD activity_name VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE plans SET activity_name = activities.name FROM activities WHERE plans.activity_id = activities.id');
        $this->addSql('ALTER TABLE plans ALTER COLUMN activity_name SET NOT NULL');
        $this->addSql('ALTER TABLE plans DROP CONSTRAINT fk_356798d181c06096'); // Verify if this is the correct constraint to drop
        $this->addSql('DROP INDEX idx_356798d181c06096'); // Verify if this is the correct index to drop
        $this->addSql('ALTER TABLE plans DROP COLUMN activity_id');

        $this->addSql('DELETE FROM activities'); // delete all activities
        $this->addSql('DROP SEQUENCE activities_id_seq'); // delete the sequence

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE plans DROP activity_name');
        // $this->addSql('ALTER TABLE events ALTER location SET NOT NULL');
    }
}

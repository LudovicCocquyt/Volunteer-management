<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260213150140 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE attachment_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE attachment (id INT NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE events_attachment (events_id INT NOT NULL, attachment_id INT NOT NULL, PRIMARY KEY(events_id, attachment_id))');
        $this->addSql('CREATE INDEX IDX_ACA66C3A9D6A1065 ON events_attachment (events_id)');
        $this->addSql('CREATE INDEX IDX_ACA66C3A464E68B ON events_attachment (attachment_id)');
        $this->addSql('ALTER TABLE events_attachment ADD CONSTRAINT FK_ACA66C3A9D6A1065 FOREIGN KEY (events_id) REFERENCES events (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE events_attachment ADD CONSTRAINT FK_ACA66C3A464E68B FOREIGN KEY (attachment_id) REFERENCES attachment (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE attachment_id_seq CASCADE');
        $this->addSql('ALTER TABLE events_attachment DROP CONSTRAINT FK_ACA66C3A9D6A1065');
        $this->addSql('ALTER TABLE events_attachment DROP CONSTRAINT FK_ACA66C3A464E68B');
        $this->addSql('DROP TABLE attachment');
        $this->addSql('DROP TABLE events_attachment');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251112155159 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events ADD message_email TEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE events ALTER published SET DEFAULT false');
        $this->addSql('ALTER TABLE events ALTER archived SET DEFAULT false');
        $this->addSql('ALTER TABLE events ALTER display_location SET DEFAULT false');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE events DROP message_email');
        $this->addSql('ALTER TABLE events ALTER display_location SET DEFAULT true');
        $this->addSql('ALTER TABLE events ALTER published DROP DEFAULT');
        $this->addSql('ALTER TABLE events ALTER archived DROP DEFAULT');
    }
}

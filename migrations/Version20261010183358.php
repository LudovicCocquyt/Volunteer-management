<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261010183358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE attachment (id INT AUTO_INCREMENT NOT NULL, filename VARCHAR(255) NOT NULL, original_name VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE events (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, start_at DATETIME NOT NULL, display_start_at TINYINT(1) DEFAULT 1 NOT NULL, location VARCHAR(255) DEFAULT NULL, display_location TINYINT(1) DEFAULT 0 NOT NULL, published TINYINT(1) DEFAULT 0 NOT NULL, archived TINYINT(1) DEFAULT 0 NOT NULL, description LONGTEXT DEFAULT NULL, scheduling_auto TINYINT(1) DEFAULT 0 NOT NULL, display_people_name TINYINT(1) DEFAULT 0 NOT NULL, display_comment_for_subscription TINYINT(1) DEFAULT 0 NOT NULL, start_calendar TIME DEFAULT NULL, end_calendar TIME DEFAULT NULL, manager_notification VARCHAR(255) DEFAULT NULL, message_email LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE events_attachment (events_id INT NOT NULL, attachment_id INT NOT NULL, INDEX IDX_ACA66C3A9D6A1065 (events_id), INDEX IDX_ACA66C3A464E68B (attachment_id), PRIMARY KEY(events_id, attachment_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE plans (id INT AUTO_INCREMENT NOT NULL, event_id INT NOT NULL, start_date DATETIME NOT NULL, end_date DATETIME NOT NULL, nb_pers INT DEFAULT NULL, activity_name VARCHAR(255) NOT NULL, information VARCHAR(10) DEFAULT NULL, INDEX IDX_356798D171F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subscriptions (id INT AUTO_INCREMENT NOT NULL, event_id INT DEFAULT NULL, firstname VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(255) NOT NULL, child_name VARCHAR(255) NOT NULL, class VARCHAR(255) NOT NULL, availabilities JSON DEFAULT NULL COMMENT \'(DC2Type:json)\', comment LONGTEXT DEFAULT NULL, INDEX IDX_4778A0171F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subscriptions_plans (subscriptions_id INT NOT NULL, plans_id INT NOT NULL, INDEX IDX_7EB43F22688E3B5D (subscriptions_id), INDEX IDX_7EB43F2280446EEB (plans_id), PRIMARY KEY(subscriptions_id, plans_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, firstname VARCHAR(255) NOT NULL, lastname VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE events_attachment ADD CONSTRAINT FK_ACA66C3A9D6A1065 FOREIGN KEY (events_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE events_attachment ADD CONSTRAINT FK_ACA66C3A464E68B FOREIGN KEY (attachment_id) REFERENCES attachment (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE plans ADD CONSTRAINT FK_356798D171F7E88B FOREIGN KEY (event_id) REFERENCES events (id)');
        $this->addSql('ALTER TABLE subscriptions ADD CONSTRAINT FK_4778A0171F7E88B FOREIGN KEY (event_id) REFERENCES events (id)');
        $this->addSql('ALTER TABLE subscriptions_plans ADD CONSTRAINT FK_7EB43F22688E3B5D FOREIGN KEY (subscriptions_id) REFERENCES subscriptions (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE subscriptions_plans ADD CONSTRAINT FK_7EB43F2280446EEB FOREIGN KEY (plans_id) REFERENCES plans (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE events_attachment DROP FOREIGN KEY FK_ACA66C3A9D6A1065');
        $this->addSql('ALTER TABLE events_attachment DROP FOREIGN KEY FK_ACA66C3A464E68B');
        $this->addSql('ALTER TABLE plans DROP FOREIGN KEY FK_356798D171F7E88B');
        $this->addSql('ALTER TABLE subscriptions DROP FOREIGN KEY FK_4778A0171F7E88B');
        $this->addSql('ALTER TABLE subscriptions_plans DROP FOREIGN KEY FK_7EB43F22688E3B5D');
        $this->addSql('ALTER TABLE subscriptions_plans DROP FOREIGN KEY FK_7EB43F2280446EEB');
        $this->addSql('DROP TABLE attachment');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE events_attachment');
        $this->addSql('DROP TABLE plans');
        $this->addSql('DROP TABLE subscriptions');
        $this->addSql('DROP TABLE subscriptions_plans');
        $this->addSql('DROP TABLE `user`');
    }
}

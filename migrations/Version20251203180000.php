<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251203180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add premium subscription system';
    }

    public function up(Schema $schema): void
    {
        // Add isPremium and description to livre table
        $this->addSql('ALTER TABLE livre ADD is_premium TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE livre ADD description LONGTEXT DEFAULT NULL');

        // Create subscription table
        $this->addSql('CREATE TABLE subscription (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            type VARCHAR(50) NOT NULL,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            active TINYINT(1) NOT NULL,
            price NUMERIC(10, 2) NOT NULL,
            statut VARCHAR(50) NOT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_A3C664D3A76ED395 (user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D3A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D3A76ED395');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('ALTER TABLE livre DROP is_premium');
        $this->addSql('ALTER TABLE livre DROP description');
    }
}

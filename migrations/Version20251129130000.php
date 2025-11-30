<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251129130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix missing columns in livre and user tables';
    }

    public function up(Schema $schema): void
    {
        // Add datapub column to livre if it doesn't exist
        $this->addSql('ALTER TABLE livre ADD datapub DATE DEFAULT NULL');
        
        // Add is_verified column to user if it doesn't exist
        $this->addSql('ALTER TABLE user ADD is_verified TINYINT(1) NOT NULL DEFAULT 0');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE livre DROP COLUMN datapub');
        $this->addSql('ALTER TABLE user DROP COLUMN is_verified');
    }
}


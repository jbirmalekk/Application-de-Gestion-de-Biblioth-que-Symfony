<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter les colonnes premium à la table user
 */
final class Version20251205200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add is_premium and premium_expires_at columns to user table';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si les colonnes existent avant de les ajouter
        $this->addSql('ALTER TABLE user 
            ADD COLUMN IF NOT EXISTS is_premium TINYINT(1) NOT NULL DEFAULT 0,
            ADD COLUMN IF NOT EXISTS premium_expires_at DATETIME DEFAULT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user 
            DROP COLUMN IF EXISTS is_premium,
            DROP COLUMN IF EXISTS premium_expires_at'
        );
    }
}

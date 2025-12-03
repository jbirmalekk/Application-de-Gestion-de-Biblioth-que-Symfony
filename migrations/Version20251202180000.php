<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251202180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create coupons table with all required fields';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE coupons (
            id INT AUTO_INCREMENT NOT NULL,
            code VARCHAR(50) NOT NULL,
            type VARCHAR(20) NOT NULL,
            valeur NUMERIC(10, 2) NOT NULL,
            montant_minimum NUMERIC(10, 2) NULL,
            date_expiration DATETIME NULL,
            usage_max INT NULL,
            usage_actuel INT NOT NULL DEFAULT 0,
            actif TINYINT(1) NOT NULL DEFAULT 1,
            description LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY UNIQ_COUPON_CODE (code)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS coupons');
    }
}

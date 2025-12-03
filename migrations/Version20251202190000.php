<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251202190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create wishlist table for user favorites';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE wishlist (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            livre_id INT NOT NULL,
            added_at DATETIME NOT NULL,
            notify_when_available TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            UNIQUE KEY UNIQ_USER_LIVRE (user_id, livre_id),
            FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
            FOREIGN KEY (livre_id) REFERENCES livre(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS wishlist');
    }
}

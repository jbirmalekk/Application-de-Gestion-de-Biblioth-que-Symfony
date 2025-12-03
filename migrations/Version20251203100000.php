<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251203100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create notification table for in-app notifications';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE notification (
            id INT AUTO_INCREMENT NOT NULL,
            user_id INT NOT NULL,
            livre_id INT DEFAULT NULL,
            message VARCHAR(255) NOT NULL,
            type VARCHAR(50) NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            INDEX IDX_NOTIFICATION_USER (user_id),
            INDEX IDX_NOTIFICATION_LIVRE (livre_id),
            CONSTRAINT FK_NOTIFICATION_USER FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
            CONSTRAINT FK_NOTIFICATION_LIVRE FOREIGN KEY (livre_id) REFERENCES livre (id) ON DELETE SET NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS notification');
    }
}



<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251202210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create avis table for book reviews';
    }

    public function up(Schema $schema): void
    {
        // Check if table already exists to avoid duplication
        if (!$schema->hasTable('avis')) {
            $this->addSql('CREATE TABLE avis (
                id INT AUTO_INCREMENT NOT NULL,
                livre_id INT NOT NULL,
                user_id INT NOT NULL,
                note INT NOT NULL,
                texte LONGTEXT NOT NULL,
                etoiles INT NOT NULL,
                approuve TINYINT(1) NOT NULL DEFAULT 0,
                date_creation DATETIME NOT NULL,
                PRIMARY KEY (id),
                INDEX IDX_avis_livre (livre_id),
                INDEX IDX_avis_user (user_id),
                FOREIGN KEY (livre_id) REFERENCES livre(id) ON DELETE CASCADE,
                FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS avis');
    }
}


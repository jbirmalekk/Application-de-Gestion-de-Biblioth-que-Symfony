<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration manuelle pour créer la table avis
 */
final class Version20251202200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create avis table for book reviews';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE avis (
            id INT AUTO_INCREMENT NOT NULL,
            livre_id INT NOT NULL,
            user_id INT NOT NULL,
            note INT NOT NULL,
            texte LONGTEXT NOT NULL,
            etoiles INT NOT NULL,
            approuve TINYINT(1) DEFAULT 0 NOT NULL,
            date_creation DATETIME NOT NULL,
            PRIMARY KEY(id),
            INDEX IDX_F3D8F66C96D5220B (livre_id),
            INDEX IDX_F3D8F66CA76ED395 (user_id),
            CONSTRAINT FK_F3D8F66C96D5220B FOREIGN KEY (livre_id) REFERENCES livre (id) ON DELETE CASCADE,
            CONSTRAINT FK_F3D8F66CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE avis');
    }
}

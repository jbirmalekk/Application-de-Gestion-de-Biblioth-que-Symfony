<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251129150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make description column nullable in categorie table';
    }

    public function up(Schema $schema): void
    {
        // Rendre la colonne description nullable
        $this->addSql('ALTER TABLE categorie MODIFY description LONGTEXT NULL');
    }

    public function down(Schema $schema): void
    {
        // Rendre la colonne description NOT NULL
        $this->addSql('ALTER TABLE categorie MODIFY description LONGTEXT NOT NULL');
    }
}

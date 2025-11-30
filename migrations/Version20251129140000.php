<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251129140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove duplicate datpub column from livre table';
    }

    public function up(Schema $schema): void
    {
        // Supprimer la colonne datpub en doublon
        $this->addSql('ALTER TABLE livre DROP COLUMN datpub');
    }

    public function down(Schema $schema): void
    {
        // Recréer la colonne si on revient en arrière
        $this->addSql('ALTER TABLE livre ADD datpub DATE DEFAULT NULL');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251202170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add delivery address fields to commande table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande ADD COLUMN adresse_livraison VARCHAR(255) NULL');
        $this->addSql('ALTER TABLE commande ADD COLUMN code_postal VARCHAR(10) NULL');
        $this->addSql('ALTER TABLE commande ADD COLUMN ville VARCHAR(100) NULL');
        $this->addSql('ALTER TABLE commande ADD COLUMN pays VARCHAR(100) NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE commande DROP COLUMN adresse_livraison');
        $this->addSql('ALTER TABLE commande DROP COLUMN code_postal');
        $this->addSql('ALTER TABLE commande DROP COLUMN ville');
        $this->addSql('ALTER TABLE commande DROP COLUMN pays');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251203120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add methode_paiement column to commande table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE commande ADD methode_paiement VARCHAR(50) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE commande DROP COLUMN methode_paiement");
    }
}



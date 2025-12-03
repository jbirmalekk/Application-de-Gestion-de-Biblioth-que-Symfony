<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251202163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Register existing Commande and CommandeItem tables with Doctrine';
    }

    public function up(Schema $schema): void
    {
        // Tables already exist in database from phpMyAdmin
        // This migration marks them as known to Doctrine
    }

    public function down(Schema $schema): void
    {
        // Nothing to revert
    }
}

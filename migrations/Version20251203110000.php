<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251203110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add pdf_path column to livre table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE livre ADD pdf_path VARCHAR(255) DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE livre DROP COLUMN pdf_path");
    }
}



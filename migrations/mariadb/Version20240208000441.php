<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240208000441 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE purchase_order ADD status_code VARCHAR(255) NOT NULL, DROP po_status');
        $this->addSql('ALTER TABLE purchase_order ADD CONSTRAINT FK_21E210B24F139D0C FOREIGN KEY (status_code) REFERENCES status (status_code)');
        $this->addSql('CREATE INDEX IDX_21E210B24F139D0C ON purchase_order (status_code)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE purchase_order DROP FOREIGN KEY FK_21E210B24F139D0C');
        $this->addSql('DROP INDEX IDX_21E210B24F139D0C ON purchase_order');
        $this->addSql('ALTER TABLE purchase_order ADD po_status VARCHAR(1) NOT NULL, DROP status_code');
    }
}

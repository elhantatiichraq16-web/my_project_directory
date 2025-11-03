<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251017093501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create ligne_facture table for invoice line items.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE ligne_facture (id INT AUTO_INCREMENT NOT NULL, facture_id INT NOT NULL, reference VARCHAR(255) DEFAULT NULL,  VARCHAR(255) NOT NULL, quantite DOUBLE PRECISION NOT NULL, prix_ht DOUBLE PRECISION NOT NULL, tva DOUBLE PRECISION NOT NULL, total_ttc DOUBLE PRECISION NOT NULL, INDEX IDX_LIGNE_FACTURE_FACTURE (facture_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ligne_facture ADD CONSTRAINT FK_LIGNE_FACTURE_FACTURE FOREIGN KEY (facture_id) REFERENCES facture (id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE ligne_facture DROP FOREIGN KEY FK_LIGNE_FACTURE_FACTURE');
        $this->addSql('DROP TABLE ligne_facture');
    }
}

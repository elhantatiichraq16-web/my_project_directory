<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251009161454 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE facture (id INT AUTO_INCREMENT NOT NULL, tier_id INT NOT NULL, reference VARCHAR(255) NOT NULL, objet VARCHAR(255) DEFAULT NULL, devise VARCHAR(10) NOT NULL, etat VARCHAR(50) NOT NULL, notes LONGTEXT DEFAULT NULL, date_creation DATETIME NOT NULL, date_echeance DATETIME DEFAULT NULL, INDEX IDX_FE866410A354F9DC (tier_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE facture ADD CONSTRAINT FK_FE866410A354F9DC FOREIGN KEY (tier_id) REFERENCES tiers (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture DROP FOREIGN KEY FK_FE866410A354F9DC');
        $this->addSql('DROP TABLE facture');
    }
}

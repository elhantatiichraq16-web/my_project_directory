<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251020165603 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE facture_param (id INT AUTO_INCREMENT NOT NULL, facture_id INT DEFAULT NULL, cle_id INT DEFAULT NULL, param_global VARCHAR(255) NOT NULL, INDEX IDX_36CEF43E7F2DEE08 (facture_id), INDEX IDX_36CEF43EF6985D08 (cle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE facture_param ADD CONSTRAINT FK_36CEF43E7F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id)');
        $this->addSql('ALTER TABLE facture_param ADD CONSTRAINT FK_36CEF43EF6985D08 FOREIGN KEY (cle_id) REFERENCES facture (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture_param DROP FOREIGN KEY FK_36CEF43E7F2DEE08');
        $this->addSql('ALTER TABLE facture_param DROP FOREIGN KEY FK_36CEF43EF6985D08');
        $this->addSql('DROP TABLE facture_param');
    }
}

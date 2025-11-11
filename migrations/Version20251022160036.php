<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251022160036 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture CHANGE utilisateur_id utilisateur_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE facture ADD CONSTRAINT FK_FE866410FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_FE866410FB88E14F ON facture (utilisateur_id)');
        $this->addSql('ALTER TABLE facture_param DROP FOREIGN KEY FK_36CEF43EF6985D08');
        $this->addSql('DROP INDEX IDX_36CEF43EF6985D08 ON facture_param');
        $this->addSql('ALTER TABLE facture_param ADD cle VARCHAR(50) NOT NULL, DROP cle_id, CHANGE facture_id facture_id INT NOT NULL, CHANGE param_global param_global VARCHAR(100) NOT NULL, CHANGE valeur valeur VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE params_ventes CHANGE etat etat VARCHAR(10) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture DROP FOREIGN KEY FK_FE866410FB88E14F');
        $this->addSql('DROP INDEX IDX_FE866410FB88E14F ON facture');
        $this->addSql('ALTER TABLE facture CHANGE utilisateur_id utilisateur_id INT NOT NULL');
        $this->addSql('ALTER TABLE facture_param ADD cle_id INT DEFAULT NULL, DROP cle, CHANGE facture_id facture_id INT DEFAULT NULL, CHANGE param_global param_global VARCHAR(255) NOT NULL, CHANGE valeur valeur LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE facture_param ADD CONSTRAINT FK_36CEF43EF6985D08 FOREIGN KEY (cle_id) REFERENCES facture (id)');
        $this->addSql('CREATE INDEX IDX_36CEF43EF6985D08 ON facture_param (cle_id)');
        $this->addSql('ALTER TABLE params_ventes CHANGE etat etat VARCHAR(10) DEFAULT \'off\'');
    }
}

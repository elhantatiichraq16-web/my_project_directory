<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251017115951 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture ADD date_modification DATETIME DEFAULT NULL, ADD remise_globale NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE ligne_facture DROP FOREIGN KEY FK_LIGNE_FACTURE_FACTURE');
        $this->addSql('DROP INDEX idx_ligne_facture_facture ON ligne_facture');
        $this->addSql('CREATE INDEX IDX_611F5A297F2DEE08 ON ligne_facture (facture_id)');
        $this->addSql('ALTER TABLE ligne_facture ADD CONSTRAINT FK_LIGNE_FACTURE_FACTURE FOREIGN KEY (facture_id) REFERENCES facture (id)');
        $this->addSql('ALTER TABLE produit ADD nom VARCHAR(255) NOT NULL, ADD description VARCHAR(255) DEFAULT NULL, ADD prix DOUBLE PRECISION NOT NULL, DROP stock, DROP nouveau_produit, DROP produit_par_categorie, DROP liste_des_produits_site, DROP nouveau_produit_site');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE facture DROP date_modification, DROP remise_globale');
        $this->addSql('ALTER TABLE ligne_facture DROP FOREIGN KEY FK_611F5A297F2DEE08');
        $this->addSql('DROP INDEX idx_611f5a297f2dee08 ON ligne_facture');
        $this->addSql('CREATE INDEX IDX_LIGNE_FACTURE_FACTURE ON ligne_facture (facture_id)');
        $this->addSql('ALTER TABLE ligne_facture ADD CONSTRAINT FK_611F5A297F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id)');
        $this->addSql('ALTER TABLE produit ADD nouveau_produit VARCHAR(255) NOT NULL, ADD produit_par_categorie VARCHAR(255) NOT NULL, ADD liste_des_produits_site VARCHAR(255) NOT NULL, ADD nouveau_produit_site VARCHAR(255) NOT NULL, DROP description, DROP prix, CHANGE nom stock VARCHAR(255) NOT NULL');
    }
}

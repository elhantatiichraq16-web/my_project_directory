<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251017094500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restore  legacy columns and ensure ligne_facture to facture foreign key exists.';
    }

    public function up(Schema $schema): void
    {
        if ($schema->hasTable('')) {
            $produit = $schema->getTable('produit');

            if ($produit->hasColumn('stock')) {
                $this->addSql('ALTER TABLE produit MODIFY stock INT NOT NULL DEFAULT 0');
            } else {
                $this->addSql('ALTER TABLE produit ADD stock INT NOT NULL DEFAULT 0');
            }

            if ($produit->hasColumn('nouveau_produit')) {
                $this->addSql('ALTER TABLE produit MODIFY nouveau_produit TINYINT(1) NOT NULL DEFAULT 0');
            } else {
                $this->addSql('ALTER TABLE produit ADD nouveau_produit TINYINT(1) NOT NULL DEFAULT 0');
            }

            if ($produit->hasColumn('produit_par_categorie')) {
                $this->addSql('ALTER TABLE produit MODIFY produit_par_categorie TINYINT(1) NOT NULL DEFAULT 0');
            } else {
                $this->addSql('ALTER TABLE produit ADD produit_par_categorie TINYINT(1) NOT NULL DEFAULT 0');
            }

            if ($produit->hasColumn('liste_des_produits_site')) {
                $this->addSql('ALTER TABLE produit MODIFY liste_des_produits_site TINYINT(1) NOT NULL DEFAULT 0');
            } else {
                $this->addSql('ALTER TABLE produit ADD liste_des_produits_site TINYINT(1) NOT NULL DEFAULT 0');
            }

            if ($produit->hasColumn('nouveau_produit_site')) {
                $this->addSql('ALTER TABLE produit MODIFY nouveau_produit_site TINYINT(1) NOT NULL DEFAULT 0');
            } else {
                $this->addSql('ALTER TABLE produit ADD nouveau_produit_site TINYINT(1) NOT NULL DEFAULT 0');
            }
        }

        if ($schema->hasTable('ligne_facture')) {
            $ligne = $schema->getTable('ligne_facture');

            if ($ligne->hasForeignKey('FK_LIGNE_FACTURE_FACTURE')) {
                $this->addSql('ALTER TABLE ligne_facture DROP FOREIGN KEY FK_LIGNE_FACTURE_FACTURE');
            }

            if (!$ligne->hasIndex('IDX_LIGNE_FACTURE_FACTURE')) {
                $this->addSql('CREATE INDEX IDX_LIGNE_FACTURE_FACTURE ON ligne_facture (facture_id)');
            }

            $this->addSql('ALTER TABLE ligne_facture ADD CONSTRAINT FK_LIGNE_FACTURE_FACTURE FOREIGN KEY (facture_id) REFERENCES facture (id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($schema->hasTable('ligne_facture')) {
            $ligne = $schema->getTable('ligne_facture');

            if ($ligne->hasForeignKey('FK_LIGNE_FACTURE_FACTURE')) {
                $this->addSql('ALTER TABLE ligne_facture DROP FOREIGN KEY FK_LIGNE_FACTURE_FACTURE');
            }

            if ($ligne->hasIndex('IDX_LIGNE_FACTURE_FACTURE')) {
                $this->addSql('DROP INDEX IDX_LIGNE_FACTURE_FACTURE ON ligne_facture');
            }
        }

        if ($schema->hasTable('produit')) {
            $produit = $schema->getTable('produit');

            if ($produit->hasColumn('nouveau_produit_site')) {
                $this->addSql('ALTER TABLE produit DROP COLUMN nouveau_produit_site');
            }

            if ($produit->hasColumn('liste_des_produits_site')) {
                $this->addSql('ALTER TABLE produit DROP COLUMN liste_des_produits_site');
            }

            if ($produit->hasColumn('produit_par_categorie')) {
                $this->addSql('ALTER TABLE produit DROP COLUMN produit_par_categorie');
            }

            if ($produit->hasColumn('nouveau_produit')) {
                $this->addSql('ALTER TABLE produit DROP COLUMN nouveau_produit');
            }

            if ($produit->hasColumn('stock')) {
                $this->addSql('ALTER TABLE produit DROP COLUMN stock');
            }
        }
    }
}

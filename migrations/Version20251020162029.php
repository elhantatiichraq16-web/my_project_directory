<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251020162029 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Modifie la colonne module dans params_ventes pour avoir la valeur par défaut "facture"';
    }

    public function up(Schema $schema): void
    {
        // Modifie uniquement la colonne 'module' pour avoir la valeur par défaut 'facture'
        $this->addSql("ALTER TABLE params_ventes MODIFY module VARCHAR(50) NOT NULL DEFAULT 'facture'");
    }

    public function down(Schema $schema): void
    {
        // Supprime la valeur par défaut en cas de rollback
        $this->addSql("ALTER TABLE params_ventes MODIFY module VARCHAR(50) NOT NULL");
    }
}


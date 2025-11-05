<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251105095027 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email_log ADD facture_id INT DEFAULT NULL, CHANGE subject subject VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE email_log ADD CONSTRAINT FK_6FB48837F2DEE08 FOREIGN KEY (facture_id) REFERENCES facture (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_6FB48837F2DEE08 ON email_log (facture_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email_log DROP FOREIGN KEY FK_6FB48837F2DEE08');
        $this->addSql('DROP INDEX IDX_6FB48837F2DEE08 ON email_log');
        $this->addSql('ALTER TABLE email_log DROP facture_id, CHANGE subject subject VARCHAR(255) DEFAULT NULL');
    }
}

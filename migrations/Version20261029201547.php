<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261029201547 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE link_opening (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, opened_at DATETIME NOT NULL, link_age_days INTEGER NOT NULL, link_id INTEGER NOT NULL, opened_by_id INTEGER NOT NULL, CONSTRAINT FK_73538E36ADA40271 FOREIGN KEY (link_id) REFERENCES link (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_73538E36AB159F5 FOREIGN KEY (opened_by_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_LINK_OPENING_OPENED_AT ON link_opening (opened_at)');
        $this->addSql('CREATE INDEX IDX_73538E36ADA40271 ON link_opening (link_id)');
        $this->addSql('CREATE INDEX IDX_73538E36AB159F5 ON link_opening (opened_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE link_opening');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261029201155 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE link ADD COLUMN announced_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__link AS SELECT id, url, normalized_url, context, posted_at, source_type, preview_status, title, description, image_url, site_name, seeded, posted_by_id FROM link');
        $this->addSql('DROP TABLE link');
        $this->addSql('CREATE TABLE link (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, url VARCHAR(2048) NOT NULL, normalized_url VARCHAR(2048) DEFAULT \'\' NOT NULL, context VARCHAR(500) DEFAULT NULL, posted_at DATETIME NOT NULL, source_type VARCHAR(20) DEFAULT \'article\' NOT NULL, preview_status VARCHAR(20) DEFAULT \'pending\' NOT NULL, title VARCHAR(255) DEFAULT NULL, description VARCHAR(500) DEFAULT NULL, image_url VARCHAR(2048) DEFAULT NULL, site_name VARCHAR(255) DEFAULT NULL, seeded BOOLEAN DEFAULT 0 NOT NULL, posted_by_id INTEGER NOT NULL, CONSTRAINT FK_36AC99F15A6D2235 FOREIGN KEY (posted_by_id) REFERENCES "user" (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO link (id, url, normalized_url, context, posted_at, source_type, preview_status, title, description, image_url, site_name, seeded, posted_by_id) SELECT id, url, normalized_url, context, posted_at, source_type, preview_status, title, description, image_url, site_name, seeded, posted_by_id FROM __temp__link');
        $this->addSql('DROP TABLE __temp__link');
        $this->addSql('CREATE INDEX IDX_LINK_POSTED_AT ON link (posted_at)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_LINK_NORMALIZED_URL ON link (normalized_url)');
        $this->addSql('CREATE INDEX IDX_36AC99F15A6D2235 ON link (posted_by_id)');
    }
}

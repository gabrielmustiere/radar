<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261030141413 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE source (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, feed_url VARCHAR(2048) NOT NULL, name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, removed_at DATETIME DEFAULT NULL, last_fetched_at DATETIME DEFAULT NULL, last_error VARCHAR(255) DEFAULT NULL, last_error_at DATETIME DEFAULT NULL)');
        $this->addSql('CREATE INDEX IDX_SOURCE_REMOVED_AT ON source (removed_at)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SOURCE_FEED_URL ON source (feed_url)');
        $this->addSql('CREATE TABLE source_category (source_id INTEGER NOT NULL, category_id INTEGER NOT NULL, PRIMARY KEY (source_id, category_id), CONSTRAINT FK_456C2F13953C1C61 FOREIGN KEY (source_id) REFERENCES source (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_456C2F1312469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_456C2F13953C1C61 ON source_category (source_id)');
        $this->addSql('CREATE INDEX IDX_456C2F1312469DE2 ON source_category (category_id)');
        $this->addSql('CREATE TABLE source_item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, item_key VARCHAR(2048) NOT NULL, seen_at DATETIME NOT NULL, source_id INTEGER NOT NULL, CONSTRAINT FK_1E91A01D953C1C61 FOREIGN KEY (source_id) REFERENCES source (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_SOURCE_ITEM ON source_item (source_id, item_key)');
        $this->addSql('CREATE INDEX IDX_1E91A01D953C1C61 ON source_item (source_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE source');
        $this->addSql('DROP TABLE source_category');
        $this->addSql('DROP TABLE source_item');
    }
}

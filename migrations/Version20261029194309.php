<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261029194309 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(60) NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CATEGORY_NAME ON category (name)');
        $this->addSql('CREATE TABLE link (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, url VARCHAR(2048) NOT NULL, context VARCHAR(500) DEFAULT NULL, posted_at DATETIME NOT NULL, posted_by_id INTEGER NOT NULL, CONSTRAINT FK_36AC99F15A6D2235 FOREIGN KEY (posted_by_id) REFERENCES "user" (id) ON DELETE RESTRICT NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_LINK_POSTED_AT ON link (posted_at)');
        $this->addSql('CREATE INDEX IDX_36AC99F15A6D2235 ON link (posted_by_id)');
        $this->addSql('CREATE TABLE link_category (link_id INTEGER NOT NULL, category_id INTEGER NOT NULL, PRIMARY KEY (link_id, category_id), CONSTRAINT FK_CBE67908ADA40271 FOREIGN KEY (link_id) REFERENCES link (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_CBE6790812469DE2 FOREIGN KEY (category_id) REFERENCES category (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_CBE67908ADA40271 ON link_category (link_id)');
        $this->addSql('CREATE INDEX IDX_CBE6790812469DE2 ON link_category (category_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE link');
        $this->addSql('DROP TABLE link_category');
    }
}

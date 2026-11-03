<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261103132120 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Équipe sur les comptes et les catégories, statut admin et état actif portés par le compte, fin de admin_email';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category ADD COLUMN team VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN team VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN admin BOOLEAN DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE user ADD COLUMN active BOOLEAN DEFAULT 1 NOT NULL');
        $this->addSql('UPDATE "user" SET admin = 1 WHERE LOWER(email) IN (SELECT email FROM admin_email)');

        // Un email de la liste sans compte perd son statut (décision du pitch) : l'exploitant le lit au déploiement.
        $abandoned = $this->connection->fetchFirstColumn(
            'SELECT email FROM admin_email WHERE email NOT IN (SELECT LOWER(email) FROM "user") ORDER BY email',
        );
        foreach ($abandoned as $email) {
            $this->write(sprintf('Admin abandonné, sans compte : %s', (string) $email));
        }

        $this->addSql('DROP TABLE admin_email');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE admin_email (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL COLLATE "BINARY", created_at DATETIME NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ADMIN_EMAIL ON admin_email (email)');
        $this->addSql('INSERT INTO admin_email (email, created_at) SELECT LOWER(email), CURRENT_TIMESTAMP FROM "user" WHERE admin = 1');
        $this->addSql('CREATE TEMPORARY TABLE __temp__category AS SELECT id, name, created_at FROM category');
        $this->addSql('DROP TABLE category');
        $this->addSql('CREATE TABLE category (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(60) NOT NULL, created_at DATETIME NOT NULL)');
        $this->addSql('INSERT INTO category (id, name, created_at) SELECT id, name, created_at FROM __temp__category');
        $this->addSql('DROP TABLE __temp__category');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CATEGORY_NAME ON category (name)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__user AS SELECT id, email, roles, password, extension_key_hash, extension_key_created_at, extension_key_last_used_at FROM "user"');
        $this->addSql('DROP TABLE "user"');
        $this->addSql('CREATE TABLE "user" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, extension_key_hash VARCHAR(64) DEFAULT NULL, extension_key_created_at DATETIME DEFAULT NULL, extension_key_last_used_at DATETIME DEFAULT NULL)');
        $this->addSql('INSERT INTO "user" (id, email, roles, password, extension_key_hash, extension_key_created_at, extension_key_last_used_at) SELECT id, email, roles, password, extension_key_hash, extension_key_created_at, extension_key_last_used_at FROM __temp__user');
        $this->addSql('DROP TABLE __temp__user');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL ON "user" (email)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_EXTENSION_KEY_HASH ON "user" (extension_key_hash)');
    }
}

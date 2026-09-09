<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260909134845 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE app_user (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, email VARCHAR(180) NOT NULL, first_name VARCHAR(120) NOT NULL, last_name VARCHAR(120) NOT NULL, roles CLOB NOT NULL, password VARCHAR(255) NOT NULL, company_id INTEGER DEFAULT NULL, CONSTRAINT FK_88BDF3E9979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_USER_EMAIL ON app_user (email)');
        $this->addSql('CREATE INDEX IDX_88BDF3E9979B1AD6 ON app_user (company_id)');
        $this->addSql('CREATE TABLE company (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, siret VARCHAR(14) NOT NULL, billing_address_street VARCHAR(255) NOT NULL, billing_address_postal_code VARCHAR(20) NOT NULL, billing_address_city VARCHAR(120) NOT NULL, billing_address_country VARCHAR(2) NOT NULL, shipping_address_street VARCHAR(255) NOT NULL, shipping_address_postal_code VARCHAR(20) NOT NULL, shipping_address_city VARCHAR(120) NOT NULL, shipping_address_country VARCHAR(2) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_4FBF094F26E94372 ON company (siret)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE app_user');
        $this->addSql('DROP TABLE company');
    }
}

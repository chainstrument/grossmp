<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260909142457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE price_tier (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, min_quantity INTEGER NOT NULL, unit_price_amount INTEGER NOT NULL, unit_price_currency VARCHAR(3) NOT NULL, product_id INTEGER NOT NULL, company_id INTEGER DEFAULT NULL, CONSTRAINT FK_59CC65304584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_59CC6530979B1AD6 FOREIGN KEY (company_id) REFERENCES company (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_59CC65304584665A ON price_tier (product_id)');
        $this->addSql('CREATE INDEX IDX_59CC6530979B1AD6 ON price_tier (company_id)');
        $this->addSql('CREATE TABLE product (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, reference VARCHAR(32) NOT NULL, name VARCHAR(255) NOT NULL, description CLOB DEFAULT NULL, category VARCHAR(20) NOT NULL, stock_quantity INTEGER NOT NULL, base_price_amount INTEGER NOT NULL, base_price_currency VARCHAR(3) NOT NULL)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04ADAEA34913 ON product (reference)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE price_tier');
        $this->addSql('DROP TABLE product');
    }
}

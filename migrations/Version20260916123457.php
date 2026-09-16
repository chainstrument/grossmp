<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260916123457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE order_status_history (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, from_status VARCHAR(20) NOT NULL, to_status VARCHAR(20) NOT NULL, transition VARCHAR(40) NOT NULL, performed_at DATETIME NOT NULL, order_id INTEGER NOT NULL, performed_by_id INTEGER DEFAULT NULL, CONSTRAINT FK_471AD77E8D9F6D38 FOREIGN KEY (order_id) REFERENCES customer_order (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_471AD77E2E65C292 FOREIGN KEY (performed_by_id) REFERENCES app_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_471AD77E8D9F6D38 ON order_status_history (order_id)');
        $this->addSql('CREATE INDEX IDX_471AD77E2E65C292 ON order_status_history (performed_by_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE order_status_history');
    }
}

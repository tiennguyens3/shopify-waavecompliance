<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220531033344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category ADD shop_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE category ADD CONSTRAINT FK_64C19C14D16C4DD FOREIGN KEY (shop_id) REFERENCES shop (id)');
        $this->addSql('CREATE INDEX IDX_64C19C14D16C4DD ON category (shop_id)');
        $this->addSql('ALTER TABLE product ADD shop_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD4D16C4DD FOREIGN KEY (shop_id) REFERENCES shop (id)');
        $this->addSql('CREATE INDEX IDX_D34A04AD4D16C4DD ON product (shop_id)');
        $this->addSql('ALTER TABLE venue ADD shop_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE venue ADD CONSTRAINT FK_91911B0D4D16C4DD FOREIGN KEY (shop_id) REFERENCES shop (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_91911B0D4D16C4DD ON venue (shop_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE category DROP FOREIGN KEY FK_64C19C14D16C4DD');
        $this->addSql('DROP INDEX IDX_64C19C14D16C4DD ON category');
        $this->addSql('ALTER TABLE category DROP shop_id');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD4D16C4DD');
        $this->addSql('DROP INDEX IDX_D34A04AD4D16C4DD ON product');
        $this->addSql('ALTER TABLE product DROP shop_id');
        $this->addSql('ALTER TABLE venue DROP FOREIGN KEY FK_91911B0D4D16C4DD');
        $this->addSql('DROP INDEX UNIQ_91911B0D4D16C4DD ON venue');
        $this->addSql('ALTER TABLE venue DROP shop_id');
    }
}

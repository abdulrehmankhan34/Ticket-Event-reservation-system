<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260416105839 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organizer_profile ALTER approval_status DROP DEFAULT');
        $this->addSql('ALTER TABLE organizer_profile ALTER is_active DROP DEFAULT');
        $this->addSql('ALTER TABLE ticket_tier ADD sold_count INT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organizer_profile ALTER approval_status SET DEFAULT \'pending\'');
        $this->addSql('ALTER TABLE organizer_profile ALTER is_active SET DEFAULT true');
        $this->addSql('ALTER TABLE ticket_tier DROP sold_count');
    }
}

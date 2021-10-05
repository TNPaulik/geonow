<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181002101116 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_groupadmin DROP FOREIGN KEY FK_5B16E0F13D0AB538');
        $this->addSql('ALTER TABLE user_groupadmin DROP FOREIGN KEY FK_5B16E0F1A76ED395');
        $this->addSql('ALTER TABLE user_groupadmin ADD CONSTRAINT FK_5B16E0F13D0AB538 FOREIGN KEY (geogroup_id) REFERENCES geogroup (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_groupadmin ADD CONSTRAINT FK_5B16E0F1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE user_groupadmin DROP FOREIGN KEY FK_5B16E0F1A76ED395');
        $this->addSql('ALTER TABLE user_groupadmin DROP FOREIGN KEY FK_5B16E0F13D0AB538');
        $this->addSql('ALTER TABLE user_groupadmin ADD CONSTRAINT FK_5B16E0F1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_groupadmin ADD CONSTRAINT FK_5B16E0F13D0AB538 FOREIGN KEY (geogroup_id) REFERENCES geogroup (id)');
    }
}

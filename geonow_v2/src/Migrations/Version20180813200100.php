<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180813200100 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE location_geooption (location_id INT NOT NULL, geooption_id INT NOT NULL, INDEX IDX_9E6F1D7564D218E (location_id), INDEX IDX_9E6F1D7567BD2FAE (geooption_id), PRIMARY KEY(location_id, geooption_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE location_geooption ADD CONSTRAINT FK_9E6F1D7564D218E FOREIGN KEY (location_id) REFERENCES location (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE location_geooption ADD CONSTRAINT FK_9E6F1D7567BD2FAE FOREIGN KEY (geooption_id) REFERENCES geooption (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE location ADD geogroup_id INT DEFAULT NULL, ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CB3D0AB538 FOREIGN KEY (geogroup_id) REFERENCES geogroup (id)');
        $this->addSql('ALTER TABLE location ADD CONSTRAINT FK_5E9E89CBA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('CREATE INDEX IDX_5E9E89CB3D0AB538 ON location (geogroup_id)');
        $this->addSql('CREATE INDEX IDX_5E9E89CBA76ED395 ON location (user_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE location_geooption');
        $this->addSql('ALTER TABLE location DROP FOREIGN KEY FK_5E9E89CB3D0AB538');
        $this->addSql('ALTER TABLE location DROP FOREIGN KEY FK_5E9E89CBA76ED395');
        $this->addSql('DROP INDEX IDX_5E9E89CB3D0AB538 ON location');
        $this->addSql('DROP INDEX IDX_5E9E89CBA76ED395 ON location');
        $this->addSql('ALTER TABLE location DROP geogroup_id, DROP user_id');
    }
}

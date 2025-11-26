<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20180813194706 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE geogroup (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, INDEX IDX_34C21BDEA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE geogroup ADD CONSTRAINT FK_34C21BDEA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE geooption ADD geogroup_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE geooption ADD CONSTRAINT FK_D0BACB033D0AB538 FOREIGN KEY (geogroup_id) REFERENCES geogroup (id)');
        $this->addSql('CREATE INDEX IDX_D0BACB033D0AB538 ON geooption (geogroup_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE geooption DROP FOREIGN KEY FK_D0BACB033D0AB538');
        $this->addSql('DROP TABLE geogroup');
        $this->addSql('DROP INDEX IDX_D0BACB033D0AB538 ON geooption');
        $this->addSql('ALTER TABLE geooption DROP geogroup_id');
    }
}

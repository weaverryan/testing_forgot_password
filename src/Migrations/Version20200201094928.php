<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Contract\MigrationTrait;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200201094928 extends AbstractMigration
{
    use MigrationTrait;

    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        $this->checkSchema();

        $this->addSql(<<<EOT
CREATE TABLE user (
    id INT AUTO_INCREMENT NOT NULL,
    email VARCHAR(180) NOT NULL,
    roles JSON NOT NULL,
    password VARCHAR(255) NOT NULL,
    UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
EOT
);
        $this->addSql(<<<EOT
CREATE TABLE password_reset_request (
    id INT AUTO_INCREMENT NOT NULL,
    user_id INT DEFAULT NULL,
    selector VARCHAR(100) NOT NULL,
    hashed_token VARCHAR(100) NOT NULL,
    requested_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    INDEX IDX_C5D0A95AA76ED395 (user_id),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
EOT
);
        $this->addSql(<<<EOT
ALTER TABLE password_reset_request ADD CONSTRAINT FK_C5D0A95AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)
EOT
);
    }

    public function down(Schema $schema) : void
    {
        $this->checkSchema();

        $this->addSql('ALTER TABLE password_reset_request DROP FOREIGN KEY FK_C5D0A95AA76ED395');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE password_reset_request');
    }
}

<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Contract\MigrationTrait;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200204182552 extends AbstractMigration
{
    use MigrationTrait;

    public function getDescription() : string
    {
        return 'DateTime objects are now DateTimeImmutable objects';
    }

    public function up(Schema $schema) : void
    {
        $this->checkSchema();

        $this->addSql(<<<EOT
ALTER TABLE password_reset_request
    CHANGE requested_at requested_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    CHANGE expires_at expires_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'
EOT
);
    }

    public function down(Schema $schema) : void
    {
        $this->checkSchema();

        $this->addSql(<<<EOT
ALTER TABLE password_reset_request
    CHANGE requested_at requested_at DATETIME NOT NULL,
    CHANGE expires_at expires_at DATETIME NOT NULL
EOT
);
    }
}

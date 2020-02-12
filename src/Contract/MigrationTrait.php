<?php

declare(strict_types=1);

namespace App\Contract;

trait MigrationTrait
{
    /**
     * Check schema is MySQL or abort.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function checkSchema(): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on \'mysql\'.'
        );
    }
}

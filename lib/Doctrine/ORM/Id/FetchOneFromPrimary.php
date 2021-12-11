<?php

declare(strict_types=1);

namespace Doctrine\ORM\Id;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Connections\PrimaryReadReplicaConnection;
use Doctrine\DBAL\ForwardCompatibility\Result as ForwardCompatibilityResult;
use Doctrine\DBAL\Result;

/**
 * @internal
 */
trait FetchOneFromPrimary
{
    /**
     * @return string|int|false
     */
    private function fetchOneFromPrimary(Connection $connection, string $sql)
    {
        // Using `query` to force usage of the master server in PrimaryReadReplicaConnection
        $result = $connection instanceof PrimaryReadReplicaConnection
            ? $connection->query($sql)
            : $connection->executeQuery($sql);
        if (! $result instanceof Result) {
            $result = ForwardCompatibilityResult::ensure($result);
        }

        return $result->fetchOne();
    }
}

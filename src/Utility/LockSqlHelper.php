<?php

declare(strict_types=1);

namespace Doctrine\ORM\Utility;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\DB2Platform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;

/** @internal */
trait LockSqlHelper
{
    private function getReadLockSQL(AbstractPlatform $platform): string
    {
        if ($platform instanceof AbstractMySQLPlatform || $platform instanceof MySQLPlatform) {
            return 'LOCK IN SHARE MODE';
        }

        if ($platform instanceof PostgreSQLPlatform) {
            return 'FOR SHARE';
        }

        return $this->getWriteLockSQL($platform);
    }

    private function getWriteLockSQL(AbstractPlatform $platform): string
    {
        if ($platform instanceof DB2Platform) {
            return 'WITH RR USE AND KEEP UPDATE LOCKS';
        }

        if ($platform instanceof SqlitePlatform) {
            return '';
        }

        if ($platform instanceof SQLServerPlatform) {
            return '';
        }

        return 'FOR UPDATE';
    }
}

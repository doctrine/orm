<?php

declare(strict_types=1);

namespace Doctrine\ORM\Utility;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\DB2Platform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;

/** @internal */
trait LockSqlHelper
{
    private function getReadLockSQL(AbstractPlatform $platform): string
    {
        return match (true) {
            $platform instanceof AbstractMySQLPlatform => 'LOCK IN SHARE MODE',
            $platform instanceof PostgreSQLPlatform => 'FOR SHARE',
            default => $this->getWriteLockSQL($platform),
        };
    }

    private function getWriteLockSQL(AbstractPlatform $platform): string
    {
        return match (true) {
            $platform instanceof DB2Platform => 'WITH RR USE AND KEEP UPDATE LOCKS',
            $platform instanceof SQLitePlatform,
            $platform instanceof SQLServerPlatform => '',
            default => 'FOR UPDATE',
        };
    }
}

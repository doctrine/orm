<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\EncryptedType;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SqlitePlatform;
use Doctrine\DBAL\Types\BlobType;

use function sprintf;

final class EncryptedType extends BlobType
{
    public const NAME = 'encrypted';

    public function getName(): string
    {
        return self::NAME;
    }

    private function getSecret(): string
    {
        return 'secret_key';
    }

    public function convertToDatabaseValueSQL($sqlExpr, AbstractPlatform $platform): string
    {
        if ($platform instanceof MySQLPlatform) {
            return sprintf('AES_ENCRYPT(%s, \'%s\')', $sqlExpr, $this->getSecret());
        }

        if ($platform instanceof SqlitePlatform) {
            return sprintf('CONCAT(%s, \'%s\')', $sqlExpr, $this->getSecret());
        }

        return $sqlExpr;
    }

    public function convertToPHPValueSQL($sqlExpr, $platform): string
    {
        if ($platform instanceof MySQLPlatform) {
            return sprintf('AES_DECRYPT(%s, \'%s\')', $sqlExpr, $this->getSecret());
        }

        if ($platform instanceof SqlitePlatform) {
            return sprintf('REPLACE(%s, \'%s\', \'\')', $sqlExpr, $this->getSecret());
        }

        return $sqlExpr;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): string
    {
        return $value;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform): bool
    {
        return true;
    }

    public function canRequireSQLConversion(): bool
    {
        return true;
    }
}

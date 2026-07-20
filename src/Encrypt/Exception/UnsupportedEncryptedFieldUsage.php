<?php

declare(strict_types=1);

namespace Doctrine\ORM\Encrypt\Exception;

use Doctrine\ORM\Exception\ORMException;
use LogicException;

use function sprintf;

final class UnsupportedEncryptedFieldUsage extends LogicException implements ORMException
{
    public static function unsupportedOperator(string $className, string $fieldName, string $operator): self
    {
        return new self(sprintf(
            '%s::$%s is encrypted; filtering by an encrypted field only supports equality operators (=, <>, IN, NIN), got "%s".',
            $className,
            $fieldName,
            $operator,
        ));
    }

    public static function missingQueryType(string $className, string $fieldName): self
    {
        return new self(sprintf(
            '%s::$%s is encrypted; filtering by an encrypted field without queryType set is not supported.',
            $className,
            $fieldName,
        ));
    }

    public static function notDeterministic(string $className, string $fieldName): self
    {
        return new self(sprintf(
            '%s::$%s is encrypted; filtering by an encrypted field using non deterministic cipher is not supported.',
            $className,
            $fieldName,
        ));
    }

    public static function literal(string $className, string $fieldName): self
    {
        return new self(sprintf(
            '%s::$%s is encrypted; comparing or assigning a DQL literal is not supported, bind a parameter instead.',
            $className,
            $fieldName,
        ));
    }

    public static function orderBy(string $className, string $fieldName): self
    {
        return new self(sprintf(
            '%s::$%s is encrypted; ordering by an encrypted field is not supported.',
            $className,
            $fieldName,
        ));
    }
}

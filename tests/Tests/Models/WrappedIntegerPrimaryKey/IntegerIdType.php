<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\WrappedIntegerPrimaryKey;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\Tests\Mocks\CompatibilityType;
use LogicException;

final class IntegerIdType extends Type
{
    use CompatibilityType;

    public const NAME = 'integer_id';

    public function convertToPHPValue(
        mixed $value,
        AbstractPlatform $platform,
    ): IntegerId|null {
        if ($value === null) {
            return null;
        }

        if ($value instanceof IntegerId) {
            return $value;
        }

        return new IntegerId($value);
    }

    public function convertToDatabaseValue(
        mixed $value,
        AbstractPlatform $platform,
    ): int|null {
        if ($value === null) {
            return null;
        } elseif ($value instanceof IntegerId) {
            return $value->getValue();
        } else {
            throw new LogicException('Unexpected value: ' . $value);
        }
    }

    public function getSQLDeclaration(
        array $column,
        AbstractPlatform $platform,
    ): string {
        return $platform->getIntegerTypeDeclarationSQL(['unsigned' => true]);
    }

    private function doGetBindingType(): ParameterType|int
    {
        return ParameterType::INTEGER;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}

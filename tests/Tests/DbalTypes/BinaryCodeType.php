<?php

declare(strict_types=1);

namespace Doctrine\Tests\DbalTypes;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\Tests\Mocks\CompatibilityType;

use function bin2hex;
use function hex2bin;
use function is_resource;
use function stream_get_contents;
use function strlen;

/**
 * Stores a {@see BinaryCode} as the 4 bytes it encodes. Like Symfony's UID types, it also
 * accepts the string representation of the value object, in both directions.
 */
final class BinaryCodeType extends Type
{
    use CompatibilityType;

    public const NAME = 'binary_code';

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): string|null
    {
        return $value === null ? null : (string) hex2bin((string) $value);
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): BinaryCode|null
    {
        // DBAL 3 may hydrate binary columns as a stream
        if (is_resource($value)) {
            $value = (string) stream_get_contents($value);
        }

        if ($value === null || $value instanceof BinaryCode) {
            return $value;
        }

        return new BinaryCode(strlen($value) === 4 ? bin2hex($value) : $value);
    }

    /**
     * {@inheritDoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getBinaryTypeDeclarationSQL(['length' => 4, 'fixed' => true]);
    }

    private function doGetBindingType(): ParameterType|int
    {
        return ParameterType::BINARY;
    }

    public function getName(): string
    {
        return self::NAME;
    }
}

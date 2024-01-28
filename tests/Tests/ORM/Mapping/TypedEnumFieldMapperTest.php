<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\DefaultTypedFieldMapper;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Tests\Models\Enums\FaultySwitch;
use Doctrine\Tests\OrmTestCase;
use ReflectionClass;

/**
 * @requires PHP >= 8.1
 */
class TypedEnumFieldMapperTest extends OrmTestCase
{
    private static function defaultTypedFieldMapper(): DefaultTypedFieldMapper
    {
        return new DefaultTypedFieldMapper();
    }

    public function testNotBackedEnumThrows(): void
    {
        $reflectionClass = new ReflectionClass(FaultySwitch::class);

        $this->expectException(MappingException::class);
        $this->expectExceptionMessage(
            'Attempting to map a non-backed enum type Doctrine\Tests\Models\Enums\SwitchStatus in entity Doctrine\Tests\Models\Enums\FaultySwitch::$status. Please use backed enums only'
        );

        self::defaultTypedFieldMapper()->validateAndComplete(['fieldName' => 'status'], $reflectionClass->getProperty('status'));
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\DefaultTypedFieldMapper;
use Doctrine\Tests\OrmTestCase;
use ReflectionClass;

class DefaultTypedFieldMapperTest extends OrmTestCase
{
    public function testItTakesAllowsNullIntoAccount(): void
    {
        $mapper          = new DefaultTypedFieldMapper();
        $reflectionClass = new ReflectionClass(DefaultTypedFieldExample::class);

        $mapping  = $mapper->validateAndComplete(['fieldName' => 'name'], $reflectionClass->getProperty('name'));
        $mapping2 = $mapper->validateAndComplete(['fieldName' => 'surname'], $reflectionClass->getProperty('surname'));

        $this->assertEquals(['fieldName' => 'name', 'nullable' => true, 'type' => 'string'], $mapping);
        $this->assertEquals(['fieldName' => 'surname', 'nullable' => false, 'type' => 'string'], $mapping2);
    }
}

class DefaultTypedFieldExample
{
    public string|null $name;
    public string $surname;
}

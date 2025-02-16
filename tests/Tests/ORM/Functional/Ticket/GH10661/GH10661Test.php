<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH10661;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\Tests\OrmTestCase;

final class GH10661Test extends OrmTestCase
{
    /** @var EntityManagerInterface */
    private $em;

    protected function setUp(): void
    {
        $this->em = $this->getTestEntityManager();
    }

    public function testMetadataFieldTypeNotCoherentWithEntityPropertyType(): void
    {
        $class = $this->em->getClassMetadata(InvalidEntity::class);
        $ce    = $this->bootstrapValidator()->validateClass($class);

        self::assertSame(
            ["The field 'Doctrine\Tests\ORM\Functional\Ticket\GH10661\InvalidEntity#property1' has the property type 'float' that differs from the metadata field type 'string' returned by the 'decimal' DBAL type."],
            $ce,
        );
    }

    public function testPropertyTypeErrorsCanBeSilenced(): void
    {
        $class = $this->em->getClassMetadata(InvalidEntity::class);
        $ce    = $this->bootstrapValidator(false)->validateClass($class);

        self::assertSame([], $ce);
    }

    public function testMetadataFieldTypeNotCoherentWithEntityPropertyTypeWithInheritance(): void
    {
        $class = $this->em->getClassMetadata(InvalidChildEntity::class);
        $ce    = $this->bootstrapValidator()->validateClass($class);

        self::assertSame(
            [
                "The field 'Doctrine\Tests\ORM\Functional\Ticket\GH10661\InvalidChildEntity#property1' has the property type 'float' that differs from the metadata field type 'string' returned by the 'decimal' DBAL type.",
                "The field 'Doctrine\Tests\ORM\Functional\Ticket\GH10661\InvalidChildEntity#property2' has the property type 'int' that differs from the metadata field type 'string' returned by the 'string' DBAL type.",
                "The field 'Doctrine\Tests\ORM\Functional\Ticket\GH10661\InvalidChildEntity#anotherProperty' has the property type 'string' that differs from the metadata field type 'bool' returned by the 'boolean' DBAL type.",
            ],
            $ce,
        );
    }

    private function bootstrapValidator(bool $validatePropertyTypes = true): SchemaValidator
    {
        return new SchemaValidator($this->em, $validatePropertyTypes);
    }
}

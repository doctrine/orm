<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH10661;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\Tests\OrmTestCase;

/**
 * @requires PHP >= 7.4
 */
final class GH10661Test extends OrmTestCase
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var SchemaValidator */
    private $validator;

    protected function setUp(): void
    {
        $this->em        = $this->getTestEntityManager();
        $this->validator = new SchemaValidator($this->em);
    }

    public function testMetadataFieldTypeNotCoherentWithEntityPropertyType(): void
    {
        $class = $this->em->getClassMetadata(InvalidEntity::class);
        $ce    = $this->validator->validateClass($class);

        self::assertEquals(
            ["The field 'Doctrine\Tests\ORM\Functional\Ticket\GH10661\InvalidEntity#property1' has the property type 'float' that differs from the metadata field type 'string' returned by the 'decimal' DBAL type."],
            $ce
        );
    }

    public function testMetadataFieldTypeNotCoherentWithEntityPropertyTypeWithInheritance(): void
    {
        $class = $this->em->getClassMetadata(InvalidChildEntity::class);
        $ce    = $this->validator->validateClass($class);

        self::assertEquals(
            [
                "The field 'Doctrine\Tests\ORM\Functional\Ticket\GH10661\InvalidChildEntity#property1' has the property type 'float' that differs from the metadata field type 'string' returned by the 'decimal' DBAL type.",
                "The field 'Doctrine\Tests\ORM\Functional\Ticket\GH10661\InvalidChildEntity#property2' has the property type 'int' that differs from the metadata field type 'string' returned by the 'string' DBAL type.",
                "The field 'Doctrine\Tests\ORM\Functional\Ticket\GH10661\InvalidChildEntity#anotherProperty' has the property type 'string' that differs from the metadata field type 'bool' returned by the 'boolean' DBAL type.",
            ],
            $ce
        );
    }
}

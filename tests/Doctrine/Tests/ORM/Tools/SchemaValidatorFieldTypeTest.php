<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\Tests\OrmTestCase;

/** @requires php 70400 */
final class SchemaValidatorFieldTypeTest extends OrmTestCase
{
    private EntityManagerInterface $em;
    private SchemaValidator $validator;

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
            ["The field 'Doctrine\Tests\ORM\Tools\InvalidEntity#property1' has the property type 'int' that differs from the metadata field type 'bool'."],
            $ce
        );
    }

    public function testMetadataFieldTypeNotCoherentWithEntityPropertyTypeWithInheritance(): void
    {
        $class = $this->em->getClassMetadata(InvalidChildEntity::class);
        $ce    = $this->validator->validateClass($class);

        self::assertEquals(
            [
                "The field 'Doctrine\Tests\ORM\Tools\InvalidChildEntity#property1' has the property type 'int' that differs from the metadata field type 'bool'.",
                "The field 'Doctrine\Tests\ORM\Tools\InvalidChildEntity#property2' has the property type 'int' that differs from the metadata field type 'string'.",
                "The field 'Doctrine\Tests\ORM\Tools\InvalidChildEntity#anotherProperty' has the property type 'string' that differs from the metadata field type 'bool'.",
            ],
            $ce
        );
    }
}

/** @Entity */
class InvalidEntity
{
    /**
     * @var int
     * @Id
     * @Column
     */
    protected $key;

    /**
     * @Column(type="boolean")
     */
    protected int $property1;
}

/** @Entity */
class InvalidChildEntity extends InvalidEntity
{
    /** @Column(type="string") */
    protected int $property2;

    /**
     * @Column(type="boolean")
     */
    private string $anotherProperty;
}

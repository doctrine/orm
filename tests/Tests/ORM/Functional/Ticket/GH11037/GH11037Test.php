<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11037;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaValidator;
use Doctrine\Tests\OrmTestCase;

final class GH11037Test extends OrmTestCase
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

    public function testMetadataFieldTypeCoherentWithEntityPropertyType(): void
    {
        $class = $this->em->getClassMetadata(ValidEntityWithTypedEnum::class);
        $ce    = $this->validator->validateClass($class);

        self::assertEquals([], $ce);
    }

    public function testMetadataFieldTypeNotCoherentWithEntityPropertyType(): void
    {
        $class = $this->em->getClassMetadata(InvalidEntityWithTypedEnum::class);
        $ce    = $this->validator->validateClass($class);

        self::assertEquals(
            [
                "The field 'Doctrine\Tests\ORM\Functional\Ticket\GH11037\InvalidEntityWithTypedEnum#status1' has the property type 'Doctrine\Tests\ORM\Functional\Ticket\GH11037\StringEntityStatus' with a backing type of 'string' that differs from the metadata field type 'int'.",
                "The field 'Doctrine\Tests\ORM\Functional\Ticket\GH11037\InvalidEntityWithTypedEnum#status2' has the property type 'Doctrine\Tests\ORM\Functional\Ticket\GH11037\IntEntityStatus' that differs from the metadata enumType 'Doctrine\Tests\ORM\Functional\Ticket\GH11037\StringEntityStatus'.",
                "The field 'Doctrine\Tests\ORM\Functional\Ticket\GH11037\InvalidEntityWithTypedEnum#status3' has the metadata enumType 'Doctrine\Tests\ORM\Functional\Ticket\GH11037\StringEntityStatus' with a backing type of 'string' that differs from the metadata field type 'int'.",
            ],
            $ce,
        );
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

class DDC3303Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(DDC3303Employee::class);
    }

    /**
     * When using an embedded field in an inheritance, private properties should also be inherited.
     */
    #[Group('GH-4097')]
    #[Group('GH-4277')]
    #[Group('GH-5867')]
    public function testEmbeddedObjectsAreAlsoInherited(): void
    {
        $employee = new DDC3303Employee(
            'John Doe',
            new DDC3303Address('Somewhere', 123, 'Over the rainbow'),
            'Doctrine Inc',
        );

        $this->_em->persist($employee);
        $this->_em->flush();
        $this->_em->clear();

        self::assertEquals($employee, $this->_em->find(DDC3303Employee::class, 'John Doe'));
    }
}

#[MappedSuperclass]
abstract class DDC3303Person
{
    public function __construct(
        #[Id]
        #[GeneratedValue(strategy: 'NONE')]
        #[Column(type: 'string', length: 255)]
        private string $name,
        #[Embedded(class: 'DDC3303Address')]
        private DDC3303Address $address,
    ) {
    }
}

#[Embeddable]
class DDC3303Address
{
    public function __construct(
        #[Column(type: 'string', length: 255)]
        private string $street,
        #[Column(type: 'integer')]
        private int $number,
        #[Column(type: 'string', length: 255)]
        private string $city,
    ) {
    }
}

#[Table(name: 'ddc3303_employee')]
#[Entity]
class DDC3303Employee extends DDC3303Person
{
    public function __construct(
        string $name,
        DDC3303Address $address,
        #[Column(type: 'string', length: 255)]
        private string $company,
    ) {
        parent::__construct($name, $address);
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

class Ticket4646InstanceOfTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            PersonTicket4646::class,
            EmployeeTicket4646::class,
        );
    }

    public function testInstanceOf(): void
    {
        $this->_em->persist(new PersonTicket4646());
        $this->_em->persist(new EmployeeTicket4646());
        $this->_em->flush();

        $dql    = 'SELECT p FROM Doctrine\Tests\ORM\Functional\Ticket\PersonTicket4646 p
                WHERE p INSTANCE OF Doctrine\Tests\ORM\Functional\Ticket\PersonTicket4646';
        $query  = $this->_em->createQuery($dql);
        $result = $query->getResult();

        self::assertCount(2, $result);
        self::assertContainsOnlyInstancesOf(PersonTicket4646::class, $result);
    }
}

#[Table(name: 'instance_of_test_person')]
#[Entity]
#[InheritanceType(value: 'JOINED')]
#[DiscriminatorColumn(name: 'kind', type: 'string')]
#[DiscriminatorMap(value: ['person' => 'Doctrine\Tests\ORM\Functional\Ticket\PersonTicket4646', 'employee' => 'Doctrine\Tests\ORM\Functional\Ticket\EmployeeTicket4646'])]
class PersonTicket4646
{
    #[Id]
    #[GeneratedValue]
    #[Column(type: 'integer')]
    private int $id;

    public function getId(): int|null
    {
        return $this->id;
    }
}

#[Table(name: 'instance_of_test_employee')]
#[Entity]
class EmployeeTicket4646 extends PersonTicket4646
{
}

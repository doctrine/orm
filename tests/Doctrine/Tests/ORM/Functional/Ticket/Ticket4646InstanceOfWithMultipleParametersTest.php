<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class Ticket4646InstanceOfWithMultipleParametersTest extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->schemaTool->createSchema([
            $this->em->getClassMetadata(PersonTicket4646Multiple::class),
            $this->em->getClassMetadata(EmployeeTicket4646Multiple::class),
            $this->em->getClassMetadata(ManagerTicket4646Multiple::class),
            $this->em->getClassMetadata(InternTicket4646Multiple::class),
        ]);
    }

    public function testInstanceOf() : void
    {
        $this->em->persist(new PersonTicket4646Multiple());
        $this->em->persist(new EmployeeTicket4646Multiple());
        $this->em->persist(new ManagerTicket4646Multiple());
        $this->em->persist(new InternTicket4646Multiple());
        $this->em->flush();

        $dql    = 'SELECT p FROM Doctrine\Tests\ORM\Functional\Ticket\PersonTicket4646Multiple p
                WHERE p INSTANCE OF (Doctrine\Tests\ORM\Functional\Ticket\EmployeeTicket4646Multiple, Doctrine\Tests\ORM\Functional\Ticket\InternTicket4646Multiple)';
        $query  = $this->em->createQuery($dql);
        $result = $query->getResult();

        self::assertCount(2, $result);
        self::assertContainsOnlyInstancesOf(PersonTicket4646Multiple::class, $result);
    }
}

/**
 * @ORM\Entity()
 * @ORM\Table(name="instance_of_test_multiple_person")
 * @ORM\InheritanceType(value="JOINED")
 * @ORM\DiscriminatorColumn(name="kind", type="string")
 * @ORM\DiscriminatorMap(value={
 *     "person": "Doctrine\Tests\ORM\Functional\Ticket\PersonTicket4646Multiple",
 *     "employee": "Doctrine\Tests\ORM\Functional\Ticket\EmployeeTicket4646Multiple",
 *     "manager": "Doctrine\Tests\ORM\Functional\Ticket\ManagerTicket4646Multiple",
 *     "intern": "Doctrine\Tests\ORM\Functional\Ticket\InternTicket4646Multiple"
 * })
 */
class PersonTicket4646Multiple
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    public function getId()
    {
        return $this->id;
    }
}

/**
 * @ORM\Entity()
 * @ORM\Table(name="instance_of_test_multiple_employee")
 */
class EmployeeTicket4646Multiple extends PersonTicket4646Multiple
{
}

/**
 * @ORM\Entity()
 * @ORM\Table(name="instance_of_test_multiple_manager")
 */
class ManagerTicket4646Multiple extends PersonTicket4646Multiple
{
}

/**
 * @ORM\Entity()
 * @ORM\Table(name="instance_of_test_multiple_intern")
 */
class InternTicket4646Multiple extends PersonTicket4646Multiple
{
}

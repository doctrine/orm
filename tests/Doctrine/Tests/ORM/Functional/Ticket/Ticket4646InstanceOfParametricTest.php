<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class Ticket4646InstanceOfParametricTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->schemaTool->createSchema([
            $this->em->getClassMetadata(PersonTicket4646Parametric::class),
            $this->em->getClassMetadata(EmployeeTicket4646Parametric::class),
        ]);
    }

    public function testInstanceOf(): void
    {
        $this->em->persist(new PersonTicket4646Parametric());
        $this->em->persist(new EmployeeTicket4646Parametric());
        $this->em->flush();

        $dql = 'SELECT p FROM Doctrine\Tests\ORM\Functional\Ticket\PersonTicket4646Parametric p
                WHERE p INSTANCE OF :parameter';
        $query = $this->em->createQuery($dql);

        $query->setParameter(
            'parameter',
            $this->em->getClassMetadata(PersonTicket4646Parametric::class)
        );

        $result = $query->getResult();

        self::assertCount(2, $result);
        self::assertContainsOnlyInstancesOf(PersonTicket4646Parametric::class, $result);
    }
}

/**
 * @ORM\Entity()
 * @ORM\Table(name="instance_of_parametric_person")
 * @ORM\InheritanceType(value="JOINED")
 * @ORM\DiscriminatorColumn(name="kind", type="string")
 * @ORM\DiscriminatorMap(value={
 *     "person": "Doctrine\Tests\ORM\Functional\Ticket\PersonTicket4646Parametric",
 *     "employee": "Doctrine\Tests\ORM\Functional\Ticket\EmployeeTicket4646Parametric"
 * })
 */
class PersonTicket4646Parametric
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    public function getId(): ?int
    {
        return $this->id;
    }
}

/**
 * @ORM\Entity()
 * @ORM\Table(name="instance_of_parametric_employee")
 */
class EmployeeTicket4646Parametric extends PersonTicket4646Parametric
{
}

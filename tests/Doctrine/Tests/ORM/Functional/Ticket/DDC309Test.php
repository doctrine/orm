<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC309Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC309Country::class),
            $this->em->getClassMetadata(DDC309User::class),
            ]
        );
    }

    public function testTwoIterateHydrations()
    {
        $c1 = new DDC309Country();
        $c2 = new DDC309Country();
        $u1 = new DDC309User();
        $u2 = new DDC309User();

        $this->em->persist($c1);
        $this->em->persist($c2);
        $this->em->persist($u1);
        $this->em->persist($u2);
        $this->em->flush();
        $this->em->clear();

        $q = $this->em->createQuery('SELECT c FROM Doctrine\Tests\ORM\Functional\Ticket\DDC309Country c')->iterate();
        $c = $q->next();

        self::assertEquals(1, $c[0]->id);

        $r = $this->em->createQuery('SELECT u FROM Doctrine\Tests\ORM\Functional\Ticket\DDC309User u')->iterate();
        $u = $r->next(); // This line breaks

        self::assertEquals(1, $u[0]->id);

        $c = $q->next();
        $u = $r->next();

        self::assertEquals(2, $c[0]->id);
        self::assertEquals(2, $u[0]->id);
    }
}

/**
 * @ORM\Entity
 */
class DDC309Country
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue
     */
    public $id;
}

/**
 * @ORM\Entity
 */
class DDC309User
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue
     */
    public $id;
}

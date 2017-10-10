<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;

class DDC309Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(
            [
            $this->_em->getClassMetadata(DDC309Country::class),
            $this->_em->getClassMetadata(DDC309User::class),
            ]
        );
    }

    public function testTwoIterateHydrations()
    {
        $c1 = new DDC309Country();
        $c2 = new DDC309Country();
        $u1 = new DDC309User();
        $u2 = new DDC309User();

        $this->_em->persist($c1);
        $this->_em->persist($c2);
        $this->_em->persist($u1);
        $this->_em->persist($u2);
        $this->_em->flush();
        $this->_em->clear();

        $q = $this->_em->createQuery('SELECT c FROM Doctrine\Tests\ORM\Functional\Ticket\DDC309Country c')->iterate();
        $c = $q->next();

        self::assertEquals(1, $c[0]->id);

        $r = $this->_em->createQuery('SELECT u FROM Doctrine\Tests\ORM\Functional\Ticket\DDC309User u')->iterate();
        $u = $r->next(); // This line breaks

        self::assertEquals(1, $u[0]->id);

        $c = $q->next();
        $u = $r->next();

        self::assertEquals(2, $c[0]->id);
        self::assertEquals(2, $u[0]->id);
    }
}

/**
 * @Entity
 */
class DDC309Country
{
    /**
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue
     */
    public $id;
}

/**
 * @Entity
 */
class DDC309User
{
    /**
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue
     */
    public $id;
}

<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC309Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC309Country'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC309User'),
        ));
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

        $this->assertEquals(1, $c[0]->id);

        $r = $this->_em->createQuery('SELECT u FROM Doctrine\Tests\ORM\Functional\Ticket\DDC309User u')->iterate();
        $u = $r->next(); // This line breaks

        $this->assertEquals(1, $u[0]->id);

        $c = $q->next();
        $u = $r->next();

        $this->assertEquals(2, $c[0]->id);
        $this->assertEquals(2, $u[0]->id);
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

<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

class DDC444Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->_schemaTool->createSchema(
            [
            $this->_em->getClassMetadata(DDC444User::class),
            ]
        );
    }

    public function testExplicitPolicy()
    {
        $classname = DDC444User::class;

        $u = new $classname;
        $u->name = "Initial value";

        $this->_em->persist($u);
        $this->_em->flush();
        $this->_em->clear();

        $q = $this->_em->createQuery("SELECT u FROM $classname u");
        $u = $q->getSingleResult();
        $this->assertEquals("Initial value", $u->name);

        $u->name = "Modified value";

        // This should be NOOP as the change hasn't been persisted
        $this->_em->flush();
        $this->_em->clear();


        $u = $this->_em->createQuery("SELECT u FROM $classname u");
        $u = $q->getSingleResult();

        $this->assertEquals("Initial value", $u->name);


        $u->name = "Modified value";
        $this->_em->persist($u);
        // Now we however persisted it, and this should have updated our friend
        $this->_em->flush();

        $q = $this->_em->createQuery("SELECT u FROM $classname u");
        $u = $q->getSingleResult();

        $this->assertEquals("Modified value", $u->name);
    }
}


/**
 * @Entity @Table(name="ddc444")
 * @ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class DDC444User
{
    /**
     * @Id @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(name="name", type="string")
     */
    public $name;
}

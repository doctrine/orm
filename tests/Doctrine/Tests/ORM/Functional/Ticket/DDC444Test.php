<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

class DDC444Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        //$this->em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC444User::class),
            ]
        );
    }

    public function testExplicitPolicy()
    {
        $classname = DDC444User::class;

        $u = new $classname;
        $u->name = "Initial value";

        $this->em->persist($u);
        $this->em->flush();
        $this->em->clear();

        $q = $this->em->createQuery("SELECT u FROM $classname u");
        $u = $q->getSingleResult();
        self::assertEquals("Initial value", $u->name);

        $u->name = "Modified value";

        // This should be NOOP as the change hasn't been persisted
        $this->em->flush();
        $this->em->clear();


        $u = $this->em->createQuery("SELECT u FROM $classname u");
        $u = $q->getSingleResult();

        self::assertEquals("Initial value", $u->name);


        $u->name = "Modified value";
        $this->em->persist($u);
        // Now we however persisted it, and this should have updated our friend
        $this->em->flush();

        $q = $this->em->createQuery("SELECT u FROM $classname u");
        $u = $q->getSingleResult();

        self::assertEquals("Modified value", $u->name);
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

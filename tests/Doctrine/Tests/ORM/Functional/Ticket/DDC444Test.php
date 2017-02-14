<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

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
 * @ORM\Entity @ORM\Table(name="ddc444")
 * @ORM\ChangeTrackingPolicy("DEFERRED_EXPLICIT")
 */
class DDC444User
{
    /**
     * @ORM\Id @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(name="name", type="string")
     */
    public $name;
}

<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;

/**
 * @group DDC-1655
 * @group DDC-1640
 * @group DDC-1556
 */
class DDC1655Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC1655Foo::class),
                $this->em->getClassMetadata(DDC1655Bar::class),
                $this->em->getClassMetadata(DDC1655Baz::class),
                ]
            );
        } catch(\Exception $e) {
            $this->fail($e->getMessage() . PHP_EOL . $e->getTraceAsString());
        }
    }

    protected function tearDown()
    {
        $conn = static::$sharedConn;

        // In case test is skipped, tearDown is called, but no setup may have run
        if (!$conn) {
            return;
        }

        $platform = $conn->getDatabasePlatform();

        $this->sqlLoggerStack->enabled = false;

        $conn->executeUpdate('DROP TABLE DDC1655Foo');
        $conn->executeUpdate('DROP TABLE DDC1655Baz');

        // Some drivers require sequence dropping (ie. PostgreSQL)
        if ($platform->prefersSequences()) {
            $conn->executeUpdate('DROP SEQUENCE DDC1655Foo_id_seq');
            $conn->executeUpdate('DROP SEQUENCE DDC1655Baz_id_seq');
        }

        $this->em->clear();
    }

    public function testPostLoadOneToManyInheritance()
    {
        $cm = $this->em->getClassMetadata(DDC1655Foo::class);
        self::assertEquals(["postLoad" => ["postLoad"]], $cm->lifecycleCallbacks);

        $cm = $this->em->getClassMetadata(DDC1655Bar::class);
        self::assertEquals(["postLoad" => ["postLoad", "postSubLoaded"]], $cm->lifecycleCallbacks);

        $baz = new DDC1655Baz();
        $foo = new DDC1655Foo();
        $foo->baz = $baz;
        $bar = new DDC1655Bar();
        $bar->baz = $baz;

        $this->em->persist($foo);
        $this->em->persist($bar);
        $this->em->persist($baz);
        $this->em->flush();
        $this->em->clear();

        $baz = $this->em->find(get_class($baz), $baz->id);
        foreach ($baz->foos as $foo) {
            self::assertEquals(1, $foo->loaded, "should have loaded callback counter incremented for " . get_class($foo));
        }
    }

    /**
     * Check that post load is not executed several times when the entity
     * is rehydrated again although its already known.
     */
    public function testPostLoadInheritanceChild()
    {
        $bar = new DDC1655Bar();

        $this->em->persist($bar);
        $this->em->flush();
        $this->em->clear();

        $bar = $this->em->find(get_class($bar), $bar->id);
        self::assertEquals(1, $bar->loaded);
        self::assertEquals(1, $bar->subLoaded);

        $bar = $this->em->find(get_class($bar), $bar->id);
        self::assertEquals(1, $bar->loaded);
        self::assertEquals(1, $bar->subLoaded);

        $dql = "SELECT b FROM " . __NAMESPACE__ . "\DDC1655Bar b WHERE b.id = ?1";
        $bar = $this->em->createQuery($dql)->setParameter(1, $bar->id)->getSingleResult();

        self::assertEquals(1, $bar->loaded);
        self::assertEquals(1, $bar->subLoaded);

        $this->em->refresh($bar);

        self::assertEquals(2, $bar->loaded);
        self::assertEquals(2, $bar->subLoaded);
    }
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorMap({
 *    "foo" = "DDC1655Foo",
 *    "bar" = "DDC1655Bar"
 * })
 * @ORM\HasLifecycleCallbacks
 */
class DDC1655Foo
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    public $id;

    public $loaded = 0;

    /**
     * @ORM\ManyToOne(targetEntity="DDC1655Baz", inversedBy="foos")
     */
    public $baz;

    /**
     * @ORM\PostLoad
     */
    public function postLoad()
    {
        $this->loaded++;
    }
}

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 */
class DDC1655Bar extends DDC1655Foo
{
    public $subLoaded;

    /**
     * @ORM\PostLoad
     */
    public function postSubLoaded()
    {
        $this->subLoaded++;
    }
}

/**
 * @ORM\Entity
 */
class DDC1655Baz
{
    /** @ORM\Id @ORM\GeneratedValue @ORM\Column(type="integer") */
    public $id;

    /**
     * @ORM\OneToMany(targetEntity="DDC1655Foo", mappedBy="baz")
     */
    public $foos = [];
}

<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

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
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorMap({
 *    "foo" = "DDC1655Foo",
 *    "bar" = "DDC1655Bar"
 * })
 * @HasLifecycleCallbacks
 */
class DDC1655Foo
{
    /** @Id @GeneratedValue @Column(type="integer") */
    public $id;

    public $loaded = 0;

    /**
     * @ManyToOne(targetEntity="DDC1655Baz", inversedBy="foos")
     */
    public $baz;

    /**
     * @PostLoad
     */
    public function postLoad()
    {
        $this->loaded++;
    }
}

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class DDC1655Bar extends DDC1655Foo
{
    public $subLoaded;

    /**
     * @PostLoad
     */
    public function postSubLoaded()
    {
        $this->subLoaded++;
    }
}

/**
 * @Entity
 */
class DDC1655Baz
{
    /** @Id @GeneratedValue @Column(type="integer") */
    public $id;

    /**
     * @OneToMany(targetEntity="DDC1655Foo", mappedBy="baz")
     */
    public $foos = [];
}

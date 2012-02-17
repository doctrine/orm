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
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1655Foo'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1655Bar'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC1655Baz'),
            ));
        } catch(\Exception $e) {

        }
    }

    public function testPostLoadOneToManyInheritance()
    {
        $cm = $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1655Foo');
        $this->assertEquals(array("postLoad" => array("postLoad")), $cm->lifecycleCallbacks);

        $cm = $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1655Bar');
        $this->assertEquals(array("postLoad" => array("postLoad", "postSubLoaded")), $cm->lifecycleCallbacks);

        $baz = new DDC1655Baz();
        $foo = new DDC1655Foo();
        $foo->baz = $baz;
        $bar = new DDC1655Bar();
        $bar->baz = $baz;

        $this->_em->persist($foo);
        $this->_em->persist($bar);
        $this->_em->persist($baz);
        $this->_em->flush();
        $this->_em->clear();

        $baz = $this->_em->find(get_class($baz), $baz->id);
        foreach ($baz->foos as $foo) {
            $this->assertEquals(1, $foo->loaded, "should have loaded callback counter incremented for " . get_class($foo));
        }
    }

    /**
     * Check that post load is not executed several times when the entity
     * is rehydrated again although its already known.
     */
    public function testPostLoadInheritanceChild()
    {
        $bar = new DDC1655Bar();

        $this->_em->persist($bar);
        $this->_em->flush();
        $this->_em->clear();

        $bar = $this->_em->find(get_class($bar), $bar->id);
        $this->assertEquals(1, $bar->loaded);
        $this->assertEquals(1, $bar->subLoaded);

        $bar = $this->_em->find(get_class($bar), $bar->id);
        $this->assertEquals(1, $bar->loaded);
        $this->assertEquals(1, $bar->subLoaded);

        $dql = "SELECT b FROM " . __NAMESPACE__ . "\DDC1655Bar b WHERE b.id = ?1";
        $bar = $this->_em->createQuery($dql)->setParameter(1, $bar->id)->getSingleResult();

        $this->assertEquals(1, $bar->loaded);
        $this->assertEquals(1, $bar->subLoaded);

        $this->_em->refresh($bar);

        $this->assertEquals(2, $bar->loaded);
        $this->assertEquals(2, $bar->subLoaded);
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
    public $foos = array();
}

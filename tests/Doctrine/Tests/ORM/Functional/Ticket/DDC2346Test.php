<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Logging\DebugStack;

/**
 * @group DDC-2346
 */
class DDC2346Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    /**
     * @var \Doctrine\DBAL\Logging\DebugStack
     */
    protected $logger;

    /**
     * {@inheritDoc}
     */
    protected function setup()
    {
        parent::setup();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2346Foo'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2346Bar'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2346Baz'),
        ));

        $this->logger = new DebugStack();
    }

    /**
     * Verifies that fetching a OneToMany association with fetch="EAGER" does not cause N+1 queries
     */
    public function testIssue()
    {
        $foo1        = new DDC2346Foo();
        $foo2        = new DDC2346Foo();

        $baz1        = new DDC2346Baz();
        $baz2        = new DDC2346Baz();

        $baz1->foo   = $foo1;
        $baz2->foo   = $foo2;

        $foo1->bars[] = $baz1;
        $foo1->bars[] = $baz2;

        $this->_em->persist($foo1);
        $this->_em->persist($foo2);
        $this->_em->persist($baz1);
        $this->_em->persist($baz2);

        $this->_em->flush();
        $this->_em->clear();

        $this->_em->getConnection()->getConfiguration()->setSQLLogger($this->logger);

        $fetchedBazs = $this->_em->getRepository(__NAMESPACE__ . '\\DDC2346Baz')->findAll();

        $this->assertCount(2, $fetchedBazs);
        $this->assertCount(2, $this->logger->queries, 'The total number of executed queries is 2, and not n+1');
    }
}

/** @Entity */
class DDC2346Foo
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /**
     * @var DDC2346Bar[]|\Doctrine\Common\Collections\Collection
     *
     * @OneToMany(targetEntity="DDC2346Bar", mappedBy="foo")
     */
    public $bars;

    /** Constructor */
    public function __construct() {
        $this->bars = new ArrayCollection();
    }
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"baz" = "DDC2346Baz"})
 */
class DDC2346Bar
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @ManyToOne(targetEntity="DDC2346Foo", inversedBy="bars", fetch="EAGER") */
    public $foo;
}


/**
 * @Entity
 */
class DDC2346Baz extends DDC2346Bar
{

}
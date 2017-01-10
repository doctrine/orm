<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\Query;

/**
 * @group DDC-371
 */
class DDC371Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        //$this->em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->schemaTool->createSchema(
            [
            $this->em->getClassMetadata(DDC371Parent::class),
            $this->em->getClassMetadata(DDC371Child::class)
            ]
        );
    }

    public function testIssue()
    {
        $parent = new DDC371Parent;
        $parent->data = 'parent';
        $parent->children = new \Doctrine\Common\Collections\ArrayCollection;

        $child = new DDC371Child;
        $child->data = 'child';

        $child->parent = $parent;
        $parent->children->add($child);

        $this->em->persist($parent);
        $this->em->persist($child);

        $this->em->flush();
        $this->em->clear();

        $children = $this->em->createQuery('select c,p from '.__NAMESPACE__.'\DDC371Child c '
                . 'left join c.parent p where c.id = 1 and p.id = 1')
                ->setHint(Query::HINT_REFRESH, true)
                ->getResult();

        self::assertEquals(1, count($children));
        self::assertNotInstanceOf(Proxy::class, $children[0]->parent);
        self::assertFalse($children[0]->parent->children->isInitialized());
        self::assertEquals(0, $children[0]->parent->children->unwrap()->count());
    }
}

/** @Entity */
class DDC371Child {
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column(type="string") */
    public $data;
    /** @ManyToOne(targetEntity="DDC371Parent", inversedBy="children") @JoinColumn(name="parentId") */
    public $parent;
}

/** @Entity */
class DDC371Parent {
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;
    /** @Column(type="string") */
    public $data;
    /** @OneToMany(targetEntity="DDC371Child", mappedBy="parent") */
    public $children;
}


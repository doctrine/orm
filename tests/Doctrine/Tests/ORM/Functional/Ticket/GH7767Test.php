<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\OrmFunctionalTestCase;
use function assert;

/**
 * @group GH7767
 */
class GH7767Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema([GH7767ParentEntity::class, GH7767ChildEntity::class]);

        $parent = new GH7767ParentEntity();
        $parent->addChild(200);
        $parent->addChild(100);
        $parent->addChild(300);

        $this->_em->persist($parent);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testMatchingRespectsCollectionOrdering() : void
    {
        $parent = $this->_em->find(GH7767ParentEntity::class, 1);
        assert($parent instanceof GH7767ParentEntity);

        $children = $parent->getChildren()->matching(Criteria::create());

        self::assertEquals(100, $children[0]->position);
        self::assertEquals(200, $children[1]->position);
        self::assertEquals(300, $children[2]->position);
    }

    public function testMatchingOverrulesCollectionOrdering() : void
    {
        $parent = $this->_em->find(GH7767ParentEntity::class, 1);
        assert($parent instanceof GH7767ParentEntity);

        $children = $parent->getChildren()->matching(Criteria::create()->orderBy(['position' => 'DESC']));

        self::assertEquals(300, $children[0]->position);
        self::assertEquals(200, $children[1]->position);
        self::assertEquals(100, $children[2]->position);
    }
}

/**
 * @Entity
 */
class GH7767ParentEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @OneToMany(targetEntity=GH7767ChildEntity::class, mappedBy="parent", fetch="EXTRA_LAZY", cascade={"persist"})
     * @OrderBy({"position" = "ASC"})
     */
    private $children;

    public function addChild(int $position) : void
    {
        $this->children[] = new GH7767ChildEntity($this, $position);
    }

    public function getChildren() : PersistentCollection
    {
        return $this->children;
    }
}

/**
 * @Entity
 */
class GH7767ChildEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /** @Column(type="integer") */
    public $position;

    /** @ManyToOne(targetEntity=GH7767ParentEntity::class, inversedBy="children") */
    private $parent;

    public function __construct(GH7767ParentEntity $parent, int $position)
    {
        $this->parent   = $parent;
        $this->position = $position;
    }
}

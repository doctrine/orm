<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\OrmFunctionalTestCase;
use function assert;

/**
 * @group GH7836
 */
class GH7836Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        $this->setUpEntitySchema([GH7836ParentEntity::class, GH7836ChildEntity::class]);

        $parent = new GH7836ParentEntity();
        $parent->addChild(100, 'foo');
        $parent->addChild(100, 'bar');
        $parent->addChild(200, 'baz');

        $this->_em->persist($parent);
        $this->_em->flush();
        $this->_em->clear();
    }

    public function testMatchingRespectsCollectionOrdering() : void
    {
        $parent = $this->_em->find(GH7836ParentEntity::class, 1);
        assert($parent instanceof GH7836ParentEntity);

        $children = $parent->getChildren()->matching(Criteria::create());

        self::assertSame(100, $children[0]->position);
        self::assertSame('bar', $children[0]->name);
        self::assertSame(100, $children[1]->position);
        self::assertSame('foo', $children[1]->name);
        self::assertSame(200, $children[2]->position);
        self::assertSame('baz', $children[2]->name);
    }

    public function testMatchingOverrulesCollectionOrdering() : void
    {
        $parent = $this->_em->find(GH7836ParentEntity::class, 1);
        assert($parent instanceof GH7836ParentEntity);

        $children = $parent->getChildren()->matching(Criteria::create()->orderBy(['position' => 'DESC', 'name' => 'ASC']));

        self::assertSame(200, $children[0]->position);
        self::assertSame('baz', $children[0]->name);
        self::assertSame(100, $children[1]->position);
        self::assertSame('bar', $children[1]->name);
        self::assertSame(100, $children[2]->position);
        self::assertSame('foo', $children[2]->name);
    }

    public function testMatchingKeepsOrderOfCriteriaOrderingKeys() : void
    {
        $parent = $this->_em->find(GH7836ParentEntity::class, 1);
        assert($parent instanceof GH7836ParentEntity);

        $children = $parent->getChildren()->matching(Criteria::create()->orderBy(['name' => 'ASC', 'position' => 'ASC']));

        self::assertSame(100, $children[0]->position);
        self::assertSame('bar', $children[0]->name);
        self::assertSame(200, $children[1]->position);
        self::assertSame('baz', $children[1]->name);
        self::assertSame(100, $children[2]->position);
        self::assertSame('foo', $children[2]->name);
    }
}

/**
 * @Entity
 */
class GH7836ParentEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @OneToMany(targetEntity=GH7836ChildEntity::class, mappedBy="parent", fetch="EXTRA_LAZY", cascade={"persist"})
     * @OrderBy({"position" = "ASC", "name" = "ASC"})
     */
    private $children;

    public function addChild(int $position, string $name) : void
    {
        $this->children[] = new GH7836ChildEntity($this, $position, $name);
    }

    public function getChildren() : PersistentCollection
    {
        return $this->children;
    }
}

/**
 * @Entity
 */
class GH7836ChildEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /** @Column(type="integer") */
    public $position;

    /** @Column(type="string") */
    public $name;

    /** @ManyToOne(targetEntity=GH7836ParentEntity::class, inversedBy="children") */
    private $parent;

    public function __construct(GH7836ParentEntity $parent, int $position, string $name)
    {
        $this->parent   = $parent;
        $this->position = $position;
        $this->name     = $name;
    }
}

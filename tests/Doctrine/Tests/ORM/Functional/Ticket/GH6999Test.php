<?php
declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

final class GH6999Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp() : void
    {
        $this->enableSecondLevelCache();
        parent::setUp();

        $this->setUpEntitySchema([GH6999Parent::class, GH6999Child::class]);
    }

    /**
     * @group 6999
     */
    public function testCollectionChange() : void
    {
        $parent = new GH6999Parent();
        $children = [];

        for ($i = 0; $i < 3; $i++) {
            $child = new GH6999Child();
            $child->parent = $parent;

            $children[] = $child;

            $this->_em->persist($child);
        }

        $parent->children = new ArrayCollection($children);

        $this->_em->persist($parent);
        $this->_em->flush();
        $this->_em->clear();

        /** @var GH6999Parent $parent */
        $parent = $this->_em->find(GH6999Parent::class, $parent->id);

        $this->assertEquals(3, $parent->children->count());

        $parent->children = clone $parent->children;

        $this->assertEquals(3, $parent->children->count());

        $this->_em->persist($parent);
        $this->_em->flush();
        $this->_em->clear();

        /** @var GH6999Parent $parent */
        $parent = $this->_em->find(GH6999Parent::class, $parent->id);

        $this->assertEquals(3, $parent->children->count(), 'The number of children should stay the same. As it did in 2.5.');
    }
}

/**
 * @Entity()
 */
class GH6999Parent
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * @var GH6999Child[]|Collection
     *
     * @OneToMany(targetEntity="GH6999Child", orphanRemoval=true, mappedBy="parent")
     */
    public $children;
}

/**
 * @Entity()
 */
class GH6999Child
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * @var GH6999Parent
     *
     * @ManyToOne(targetEntity="GH6999Parent", inversedBy="children")
     */
    public $parent;
}

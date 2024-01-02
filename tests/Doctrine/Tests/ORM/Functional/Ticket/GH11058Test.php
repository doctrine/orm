<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH11058Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH11058Parent::class,
            GH11058Child::class,
        ]);
    }

    public function testChildrenInsertedInOrderOfPersistCalls1WhenParentPersistedLast(): void
    {
        [$parent, $child1, $child2] = $this->createParentWithTwoChildEntities();

        $this->_em->persist($child1);
        $this->_em->persist($child2);
        $this->_em->persist($parent);
        $this->_em->flush();

        self::assertTrue($child1->id < $child2->id);
    }

    public function testChildrenInsertedInOrderOfPersistCalls2WhenParentPersistedLast(): void
    {
        [$parent, $child1, $child2] = $this->createParentWithTwoChildEntities();

        $this->_em->persist($child2);
        $this->_em->persist($child1);
        $this->_em->persist($parent);
        $this->_em->flush();

        self::assertTrue($child2->id < $child1->id);
    }

    public function testChildrenInsertedInOrderOfPersistCalls1WhenParentPersistedFirst(): void
    {
        [$parent, $child1, $child2] = $this->createParentWithTwoChildEntities();

        $this->_em->persist($parent);
        $this->_em->persist($child1);
        $this->_em->persist($child2);
        $this->_em->flush();

        self::assertTrue($child1->id < $child2->id);
    }

    public function testChildrenInsertedInOrderOfPersistCalls2WhenParentPersistedFirst(): void
    {
        [$parent, $child1, $child2] = $this->createParentWithTwoChildEntities();

        $this->_em->persist($parent);
        $this->_em->persist($child2);
        $this->_em->persist($child1);
        $this->_em->flush();

        self::assertTrue($child2->id < $child1->id);
    }

    private function createParentWithTwoChildEntities(): array
    {
        $parent = new GH11058Parent();
        $child1 = new GH11058Child();
        $child2 = new GH11058Child();

        $parent->addChild($child1);
        $parent->addChild($child2);

        return [$parent, $child1, $child2];
    }
}

#[ORM\Entity]
class GH11058Parent
{
    /** @var int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public $id;

    /** @var Collection<int, GH11058Child> */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: GH11058Child::class)]
    public Collection $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    public function addChild(GH11058Child $child): void
    {
        if (! $this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }
    }
}

#[ORM\Entity]
class GH11058Child
{
    /** @var int */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public $id;

    /** @var GH11058Parent */
    #[ORM\ManyToOne(inversedBy: 'children', targetEntity: GH11058Parent::class)]
    public $parent;

    public function setParent(GH11058Parent $parent): void
    {
        $this->parent = $parent;
        $parent->addChild($this);
    }
}

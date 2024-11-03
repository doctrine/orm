<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC599Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DDC599Item::class,
            DDC599Subitem::class,
            DDC599Child::class,
        );
    }

    public function testCascadeRemoveOnInheritanceHierarchy(): void
    {
        $item          = new DDC599Subitem();
        $item->elem    = 'foo';
        $child         = new DDC599Child();
        $child->parent = $item;
        $item->getChildren()->add($child);
        $this->_em->persist($item);
        $this->_em->persist($child);
        $this->_em->flush();
        $this->_em->clear();

        $item = $this->_em->find(DDC599Item::class, $item->id);

        $this->_em->remove($item);
        $this->_em->flush(); // Should not fail

        self::assertFalse($this->_em->contains($item));
        $children = $item->getChildren();
        self::assertFalse($this->_em->contains($children[0]));

        $this->_em->clear();

        $item2       = new DDC599Subitem();
        $item2->elem = 'bar';
        $this->_em->persist($item2);
        $this->_em->flush();

        $child2         = new DDC599Child();
        $child2->parent = $item2;
        $item2->getChildren()->add($child2);
        $this->_em->persist($child2);
        $this->_em->flush();

        $this->_em->remove($item2);
        $this->_em->flush(); // should not fail

        self::assertFalse($this->_em->contains($item));
        $children = $item->getChildren();
        self::assertFalse($this->_em->contains($children[0]));
    }

    public function testCascadeRemoveOnChildren(): void
    {
        $class = $this->_em->getClassMetadata(DDC599Subitem::class);

        self::assertArrayHasKey('children', $class->associationMappings);
        self::assertTrue($class->associationMappings['children']->isCascadeRemove());
    }
}

#[Entity]
#[InheritanceType('SINGLE_TABLE')]
#[DiscriminatorColumn(name: 'type', type: 'integer')]
#[DiscriminatorMap(['0' => 'DDC599Item', '1' => 'DDC599Subitem'])]
class DDC599Item
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;

    /** @phpstan-var Collection<int, DDC599Child> */
    #[OneToMany(targetEntity: 'DDC599Child', mappedBy: 'parent', cascade: ['remove'])]
    protected $children;

    public function __construct()
    {
        $this->children = new ArrayCollection();
    }

    /** @phpstan-return Collection<int, DDC599Child> */
    public function getChildren(): Collection
    {
        return $this->children;
    }
}

#[Entity]
class DDC599Subitem extends DDC599Item
{
    /** @var string */
    #[Column(type: 'string', length: 255)]
    public $elem;
}

#[Entity]
class DDC599Child
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;

    /** @var DDC599Item */
    #[ManyToOne(targetEntity: 'DDC599Item', inversedBy: 'children')]
    #[JoinColumn(name: 'parentId', referencedColumnName: 'id')]
    public $parent;
}

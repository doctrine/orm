<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

#[Group('GH-9579')]
class GH9579Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH9579Container::class,
            GH9579Item::class,
            GH9579Part::class,
        );

        $container = new GH9579Container();

        $item            = new GH9579Item();
        $item->container = $container;

        $container->currentItem = $item;

        $part       = new GH9579Part();
        $part->item = $item;

        $this->_em->persist($item);
        $this->_em->persist($container);
        $this->_em->persist($part);

        $this->_em->flush();
        $this->_em->clear();
    }

    #[Group('GH-9579')]
    public function testIssue(): void
    {
        $dql        = <<<'DQL'
SELECT container, currentItem, currentItemPart, item, itemPart
FROM Doctrine\Tests\ORM\Functional\Ticket\GH9579Container container
LEFT JOIN container.currentItem currentItem
LEFT JOIN currentItem.parts currentItemPart
LEFT JOIN container.items item 
LEFT JOIN item.parts itemPart
DQL;
        $containers = $this->_em->createQuery($dql)->execute();
        self::assertCount(1, $containers);
        self::assertCount(1, $containers[0]->items);
        self::assertCount(1, $containers[0]->items[0]->parts);
    }
}

#[Table(name: 'GH9579_containers')]
#[Entity]
class GH9579Container
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var Collection<int, GH9579Item> */
    #[OneToMany(targetEntity: 'GH9579Item', mappedBy: 'container')]
    public $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    /** @var GH9579Item */
    #[OneToOne(targetEntity: 'GH9579Item')]
    #[JoinColumn(name: 'item_id', referencedColumnName: 'id')]
    public $currentItem;
}

#[Table(name: 'GH9579_items')]
#[Entity]
class GH9579Item
{
    public function __construct()
    {
        $this->parts = new ArrayCollection();
    }

    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var Collection<int, GH9579Part> */
    #[OneToMany(targetEntity: 'GH9579Part', mappedBy: 'item')]
    public $parts;

    /** @var GH9579Container */
    #[ManyToOne(targetEntity: 'GH9579Container', inversedBy: 'items')]
    #[JoinColumn(name: 'container_id', referencedColumnName: 'id')]
    public $container;
}

#[Table(name: 'GH9579_parts')]
#[Entity]
class GH9579Part
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public $id;

    /** @var GH9579Item */
    #[ManyToOne(targetEntity: 'GH9579Item', inversedBy: 'parts')]
    #[JoinColumn(name: 'item_id', referencedColumnName: 'id')]
    public $item;
}

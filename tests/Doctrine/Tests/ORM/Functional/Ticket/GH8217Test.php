<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH8217Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema(
            [
                GH8217Collection::class,
                GH8217CollectionItem::class,
            ]
        );
    }

    /** @group GH-8217 */
    public function testNoQueriesAfterSecondFlush(): void
    {
        $collection = new GH8217Collection();
        $collection->addItem(new GH8217CollectionItem($collection, 0));
        $collection->addItem(new GH8217CollectionItem($collection, 1));
        $this->_em->persist($collection);
        $this->_em->flush();

        $this->getQueryLog()->reset()->enable();
        $this->_em->flush();
        $this->assertQueryCount(0);
    }
}

/** @Entity */
class GH8217Collection
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @psalm-var Collection<int, GH8217CollectionItem>
     * @OneToMany(targetEntity="GH8217CollectionItem", mappedBy="collection",
     *     cascade={"persist", "remove"}, orphanRemoval=true)
     */
    public $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function addItem(GH8217CollectionItem $item): void
    {
        $this->items->add($item);
    }
}

/** @Entity */
class GH8217CollectionItem
{
    /**
     * @var GH8217Collection
     * @Id
     * @ManyToOne(targetEntity="GH8217Collection", inversedBy="items")
     * @JoinColumn(name="id", referencedColumnName="id")
     */
    public $collection;

    /**
     * @var int
     * @Id
     * @Column(type="integer", options={"unsigned": true})
     */
    public $collectionIndex;

    public function __construct(GH8217Collection $collection, int $collectionIndex)
    {
        $this->collection      = $collection;
        $this->collectionIndex = $collectionIndex;
    }
}

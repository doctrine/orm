<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

final class GH7458Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setup();

        $this->setUpEntitySchema(
            [
                GH7458Order::class,
                GH7458Item::class,
            ]
        );
    }

    /**
     * @group 7458
     */
    public function testManagedRelations() : void
    {
        $order             = new GH7458Order();
        $item              = new GH7458Item();
        $order->items      = [$item];
        $item->order       = $order;
        $item->description = uniqid();

        $this->em->persist($order);
        $this->em->persist($item);
        $this->em->flush();
        $this->em->clear();

        // Target properties for item
        $order = null;
        $description = uniqid();

        // Load instance into unit of work and modify properties
        $queriedItem1 = $this->em->getUnitOfWork()->getEntityPersister(GH7458Item::class)->load(['id' => $item->id]);
        $queriedItem1->order = $order;
        $queriedItem1->description = $description;

        // Load instance from unit of work cache
        $queriedItem2 = $this->em->getUnitOfWork()->getEntityPersister(GH7458Item::class)->load(['id' => $item->id]);

        // Should retrieve cached instance without re-hydrating properties
        self::assertSame($queriedItem1, $queriedItem2);
        self::assertSame($order, $queriedItem2->order);
        self::assertSame($description, $queriedItem2->description);
    }
}

/**
 * @ORM\Entity
 */
class GH7458Order
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
    /** @ORM\OneToMany(targetEntity=GH7458Item::class, mappedBy="order", fetch="EAGER") */
    public $items;
}

/**
 * @ORM\Entity
 */
class GH7458Item
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    public $id;
    /** @ORM\Column(type="string") */
    public $description;
    /** @ORM\ManyToOne(targetEntity=GH7458Order::class, inversedBy="items", fetch="EAGER") */
    public $order;
}

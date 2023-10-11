<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\Tests\OrmFunctionalTestCase;

class DDC512Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            DDC512Customer::class,
            DDC512OfferItem::class,
            DDC512Item::class,
        );
    }

    public function testIssue(): void
    {
        $customer1       = new DDC512Customer();
        $item            = new DDC512OfferItem();
        $customer1->item = $item;
        $this->_em->persist($customer1);

        $customer2 = new DDC512Customer();
        $this->_em->persist($customer2);

        $this->_em->flush();
        $this->_em->clear();

        $q      = $this->_em->createQuery('select u,i from ' . __NAMESPACE__ . '\\DDC512Customer u left join u.item i');
        $result = $q->getResult();

        self::assertCount(2, $result);
        self::assertInstanceOf(DDC512Customer::class, $result[0]);
        self::assertInstanceOf(DDC512Customer::class, $result[1]);
        if ($result[0]->id === $customer1->id) {
            self::assertInstanceOf(DDC512OfferItem::class, $result[0]->item);
            self::assertEquals($item->id, $result[0]->item->id);
            self::assertNull($result[1]->item);
        } else {
            self::assertInstanceOf(DDC512OfferItem::class, $result[1]->item);
            self::assertNull($result[0]->item);
        }
    }
}

#[Entity]
class DDC512Customer
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;

    /**
     * NOTE that we can currently not name the join column the same as the field
     * (item = item), this currently confuses Doctrine.
     *
     * @var DDC512OfferItem
     */
    #[OneToOne(targetEntity: 'DDC512OfferItem', cascade: ['remove', 'persist'])]
    #[JoinColumn(name: 'item_id', referencedColumnName: 'id')]
    public $item;
}

#[Entity]
class DDC512OfferItem extends DDC512Item
{
}

#[Entity]
#[InheritanceType('JOINED')]
#[DiscriminatorColumn(name: 'discr', type: 'string')]
#[DiscriminatorMap(['item' => 'DDC512Item', 'offerItem' => 'DDC512OfferItem'])]
class DDC512Item
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue(strategy: 'AUTO')]
    public $id;
}

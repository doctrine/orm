<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH10868Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH10868AcceptanceItem::class,
            GH10868Offer::class,
        ]);
    }

    public function testReferenceAndLazyLoadProxyAreTheSame(): void
    {
        $offer                 = new GH10868Offer();
        $acceptanceItem        = new GH10868AcceptanceItem();
        $acceptanceItem->offer = $offer;

        $this->_em->persist($offer);
        $this->_em->persist($acceptanceItem);
        $this->_em->flush();
        $this->_em->clear();

        $reference              = $this->_em->getReference(GH10868Offer::class, $offer->id);
        $acceptanceItemReloaded = $this->_em->find(GH10868AcceptanceItem::class, $acceptanceItem->id);

        self::assertSame($reference, $acceptanceItemReloaded->offer);
    }
}

/**
 * @ORM\Entity
 */
class GH10868AcceptanceItem
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var ?int
     */
    public $id;

    /**
     * @ORM\OneToOne(targetEntity="GH10868Offer")
     *
     * @var GH10868Offer
     */
    public $offer;
}

/**
 * @ORM\Entity
 */
class GH10868Offer
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var ?int
     */
    public $id;
}

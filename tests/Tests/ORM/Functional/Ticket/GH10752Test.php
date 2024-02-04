<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group GH10752
 */
class GH10752Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH10752Order::class,
            GH10752Promotion::class,
        ]);
    }

    public function testThrowExceptionWhenRemovingPromotionThatIsInUse(): void
    {
        if (! $this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            self::markTestSkipped('Platform does not support foreign keys.');
        }

        $order     = new GH10752Order();
        $promotion = new GH10752Promotion();

        $order->addPromotion($promotion);

        $this->_em->persist($order);
        $this->_em->persist($promotion);
        $this->_em->flush();

        $this->_em->remove($promotion);

        $this->expectException(ForeignKeyConstraintViolationException::class);
        $this->_em->flush();
    }

    public function testThrowExceptionWhenRemovingPromotionThatIsInUseAndOrderIsNotInMemory(): void
    {
        if (! $this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            self::markTestSkipped('Platform does not support foreign keys.');
        }

        $order     = new GH10752Order();
        $promotion = new GH10752Promotion();

        $order->addPromotion($promotion);

        $this->_em->persist($order);
        $this->_em->persist($promotion);
        $this->_em->flush();

        $this->_em->clear();

        $promotion = $this->_em->find(GH10752Promotion::class, $promotion->id);
        $this->_em->remove($promotion);

        $this->expectException(ForeignKeyConstraintViolationException::class);
        $this->_em->flush();
    }
}

/**
 * @ORM\Entity
 */
class GH10752Order
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $id = null;

    /**
     * @ORM\ManyToMany(targetEntity="GH10752Promotion", cascade={"persist"})
     * @ORM\JoinTable(name="order_promotion",
     *      joinColumns={@ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="promotion_id", referencedColumnName="id")}
     * )
     *
     * @var Collection
     */
    private $promotions;

    public function __construct()
    {
        $this->promotions = new ArrayCollection();
    }

    public function addPromotion(GH10752Promotion $promotion): void
    {
        if (! $this->promotions->contains($promotion)) {
            $this->promotions->add($promotion);
        }
    }
}

/**
 * @ORM\Entity
 */
class GH10752Promotion
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id = null;
}

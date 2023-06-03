<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @Group G10752H
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
        $order     = $this->createOrder();
        $promotion = $this->createPromotion();

        $order->addPromotion($promotion);

        $this->_em->persist($order);
        $this->_em->persist($promotion);
        $this->_em->flush();

        $this->_em->remove($promotion);

        $this->expectException(ForeignKeyConstraintViolationException::class);
        $this->_em->flush();
    }

    private function createOrder(): GH10752Order
    {
        return new GH10752Order();
    }

    private function createPromotion(): GH10752Promotion
    {
        return new GH10752Promotion();
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
     */
    private ?int $id = null;

    /**
     * @ORM\ManyToMany(targetEntity="GH10752Promotion", cascade={"persist"})
     * @ORM\JoinTable(name="order_promotion",
     *      joinColumns={@ORM\JoinColumn(name="order_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="promotion_id", referencedColumnName="id")}
     * )
     */
    private Collection $promotions;

    public function __construct()
    {
        $this->promotions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPromotions(): Collection
    {
        return $this->promotions;
    }

    public function addPromotion(GH10752Promotion $promotion): void
    {
        if (! $this->promotions->contains($promotion)) {
            $this->promotions->add($promotion);
        }
    }

    public function removePromotion(GH10752Promotion $promotion): void
    {
        if ($this->promotions->contains($promotion)) {
            $this->promotions->removeElement($promotion);
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
     */
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}

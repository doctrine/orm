<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH9376Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            GH9376GiftVariant::class,
            GH9376Product::class,
            GH9376Gift::class
        );
    }

    public function testRemoveCircularRelatedEntities(): void
    {
        if (! $this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            self::markTestSkipped('Platform does not support foreign keys.');
        }

        $product     = new GH9376Product();
        $gift        = new GH9376Gift($product);
        $giftVariant = new GH9376GiftVariant($gift);

        $this->_em->persist($product);
        $this->_em->persist($gift);
        $this->_em->persist($giftVariant);
        $this->_em->flush();
        $this->_em->clear();

        $persistedGiftVariant = $this->_em->find(GH9376GiftVariant::class, 1);
        $this->_em->remove($persistedGiftVariant);

        $persistedGift = $this->_em->find(GH9376Gift::class, 1);
        $this->_em->remove($persistedGift);

        $this->_em->flush();
        $this->_em->clear();

        self::assertEmpty($this->_em->getRepository(GH9376Gift::class)->findAll());
        self::assertEmpty($this->_em->getRepository(GH9376GiftVariant::class)->findAll());
    }
}

/**
 * @Entity
 */
class GH9376GiftVariant
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=GH9376Gift::class)
     * @ORM\JoinColumn(nullable=false)
     *
     * @var GH9376Gift
     */
    public $gift;

    public function __construct(GH9376Gift $gift)
    {
        $this->gift = $gift;
    }
}

/**
 * @Entity
 */
class GH9376Product
{
    /**
     * @var int
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;
}

/**
 * @Entity
 */
class GH9376Gift
{
    /**
     * @var int
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @ORM\ManyToOne(targetEntity=GH9376Product::class)
     * @ORM\JoinColumn(nullable=false)
     *
     * @var GH9376Product
     */
    public $product;

    public function __construct(GH9376Product $product)
    {
        $this->product = $product;
    }
}

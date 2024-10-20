<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\Models\ECommerce\ECommerceShipping;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests a unidirectional one-to-one association mapping (without inheritance).
 * Inverse side is not present.
 */
class OneToOneUnidirectionalAssociationTest extends OrmFunctionalTestCase
{
    private ECommerceProduct $product;

    private ECommerceShipping $shipping;

    protected function setUp(): void
    {
        $this->useModelSet('ecommerce');

        parent::setUp();

        $this->product = new ECommerceProduct();
        $this->product->setName('Doctrine 2 Manual');
        $this->shipping = new ECommerceShipping();
        $this->shipping->setDays('5');
    }

    public function testSavesAOneToOneAssociationWithCascadeSaveSet(): void
    {
        $this->product->setShipping($this->shipping);
        $this->_em->persist($this->product);
        $this->_em->flush();

        $this->assertForeignKeyIs($this->shipping->getId());
    }

    public function testRemovesOneToOneAssociation(): void
    {
        $this->product->setShipping($this->shipping);
        $this->_em->persist($this->product);
        $this->product->removeShipping();

        $this->_em->flush();

        $this->assertForeignKeyIs(null);
    }

    public function testEagerLoad(): void
    {
        $this->createFixture();

        $query   = $this->_em->createQuery('select p, s from Doctrine\Tests\Models\ECommerce\ECommerceProduct p left join p.shipping s');
        $result  = $query->getResult();
        $product = $result[0];

        self::assertInstanceOf(ECommerceShipping::class, $product->getShipping());
        self::assertEquals(1, $product->getShipping()->getDays());
    }

    public function testLazyLoadsObjects(): void
    {
        $this->createFixture();
        $metadata                                         = $this->_em->getClassMetadata(ECommerceProduct::class);
        $metadata->associationMappings['shipping']->fetch = ClassMetadata::FETCH_LAZY;

        $query   = $this->_em->createQuery('select p from Doctrine\Tests\Models\ECommerce\ECommerceProduct p');
        $result  = $query->getResult();
        $product = $result[0];

        self::assertInstanceOf(ECommerceShipping::class, $product->getShipping());
        self::assertEquals(1, $product->getShipping()->getDays());
    }

    public function testDoesNotLazyLoadObjectsIfConfigurationDoesNotAllowIt(): void
    {
        $this->createFixture();

        $query = $this->_em->createQuery('select p from Doctrine\Tests\Models\ECommerce\ECommerceProduct p');
        $query->setHint(Query::HINT_FORCE_PARTIAL_LOAD, true);

        $result  = $query->getResult();
        $product = $result[0];

        self::assertNull($product->getShipping());
    }

    protected function createFixture(): void
    {
        $product = new ECommerceProduct();
        $product->setName('Php manual');
        $shipping = new ECommerceShipping();
        $shipping->setDays('1');
        $product->setShipping($shipping);

        $this->_em->persist($product);

        $this->_em->flush();
        $this->_em->clear();
    }

    public function assertForeignKeyIs($value): void
    {
        $foreignKey = $this->_em->getConnection()->executeQuery(
            'SELECT shipping_id FROM ecommerce_products WHERE id=?',
            [$this->product->getId()],
        )->fetchOne();
        self::assertEquals($value, $foreignKey);
    }

    #[Group('DDC-762')]
    public function testNullForeignKey(): void
    {
        $product = new ECommerceProduct();
        $product->setName('Doctrine 2 Manual');

        $this->_em->persist($product);
        $this->_em->flush();

        $product = $this->_em->find($product::class, $product->getId());

        self::assertNull($product->getShipping());
    }
}

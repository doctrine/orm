<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Tests\Models\ECommerce\ECommerceFeature;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Tests a bidirectional one-to-one association mapping (without inheritance).
 */
class OneToManyBidirectionalAssociationTest extends OrmFunctionalTestCase
{
    private $product;
    private $firstFeature;
    private $secondFeature;

    protected function setUp()
    {
        $this->useModelSet('ecommerce');
        parent::setUp();
        $this->product = new ECommerceProduct();
        $this->product->setName('Doctrine Cookbook');
        $this->firstFeature = new ECommerceFeature();
        $this->firstFeature->setDescription('Model writing tutorial');
        $this->secondFeature = new ECommerceFeature();
        $this->secondFeature->setDescription('Annotations examples');
    }

    public function testSavesAOneToManyAssociationWithCascadeSaveSet() {
        $this->product->addFeature($this->firstFeature);
        $this->product->addFeature($this->secondFeature);
        $this->em->persist($this->product);
        $this->em->flush();

        self::assertFeatureForeignKeyIs($this->product->getId(), $this->firstFeature);
        self::assertFeatureForeignKeyIs($this->product->getId(), $this->secondFeature);
    }

    public function testSavesAnEmptyCollection()
    {
        $this->em->persist($this->product);
        $this->em->flush();

        self::assertEquals(0, count($this->product->getFeatures()));
    }

    public function testDoesNotSaveAnInverseSideSet() {
        $this->product->brokenAddFeature($this->firstFeature);
        $this->em->persist($this->product);
        $this->em->flush();

        self::assertFeatureForeignKeyIs(null, $this->firstFeature);
    }

    public function testRemovesOneToOneAssociation()
    {
        $this->product->addFeature($this->firstFeature);
        $this->product->addFeature($this->secondFeature);
        $this->em->persist($this->product);

        $this->product->removeFeature($this->firstFeature);
        $this->em->flush();

        self::assertFeatureForeignKeyIs(null, $this->firstFeature);
        self::assertFeatureForeignKeyIs($this->product->getId(), $this->secondFeature);
    }

    public function testEagerLoadsOneToManyAssociation()
    {
        $this->createFixture();
        $query = $this->em->createQuery('select p, f from Doctrine\Tests\Models\ECommerce\ECommerceProduct p join p.features f');
        $result = $query->getResult();
        $product = $result[0];

        $features = $product->getFeatures();

        self::assertInstanceOf(ECommerceFeature::class, $features[0]);
        self::assertNotInstanceOf(Proxy::class, $features[0]->getProduct());
        self::assertSame($product, $features[0]->getProduct());
        self::assertEquals('Model writing tutorial', $features[0]->getDescription());
        self::assertInstanceOf(ECommerceFeature::class, $features[1]);
        self::assertSame($product, $features[1]->getProduct());
        self::assertNotInstanceOf(Proxy::class, $features[1]->getProduct());
        self::assertEquals('Annotations examples', $features[1]->getDescription());
    }

    public function testLazyLoadsObjectsOnTheOwningSide()
    {
        $this->createFixture();

        $query = $this->em->createQuery('select p from Doctrine\Tests\Models\ECommerce\ECommerceProduct p');
        $result = $query->getResult();
        $product = $result[0];
        $features = $product->getFeatures();

        self::assertFalse($features->isInitialized());
        self::assertInstanceOf(ECommerceFeature::class, $features[0]);
        self::assertTrue($features->isInitialized());
        self::assertSame($product, $features[0]->getProduct());
        self::assertEquals('Model writing tutorial', $features[0]->getDescription());
        self::assertInstanceOf(ECommerceFeature::class, $features[1]);
        self::assertSame($product, $features[1]->getProduct());
        self::assertEquals('Annotations examples', $features[1]->getDescription());
    }

    public function testLazyLoadsObjectsOnTheInverseSide()
    {
        $this->createFixture();

        $query = $this->em->createQuery('select f from Doctrine\Tests\Models\ECommerce\ECommerceFeature f');
        $features = $query->getResult();

        $product = $features[0]->getProduct();
        self::assertInstanceOf(Proxy::class, $product);
        self::assertInstanceOf(ECommerceProduct::class, $product);
        self::assertFalse($product->__isInitialized__);
        self::assertSame('Doctrine Cookbook', $product->getName());
        self::assertTrue($product->__isInitialized__);
    }

    public function testLazyLoadsObjectsOnTheInverseSide2()
    {
        //$this->em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);
        $this->createFixture();

        $query = $this->em->createQuery('select f,p from Doctrine\Tests\Models\ECommerce\ECommerceFeature f join f.product p');
        $features = $query->getResult();

        $product = $features[0]->getProduct();
        self::assertNotInstanceOf(Proxy::class, $product);
        self::assertInstanceOf(ECommerceProduct::class, $product);
        self::assertSame('Doctrine Cookbook', $product->getName());

        self::assertFalse($product->getFeatures()->isInitialized());

        // This would trigger lazy-load
        //self::assertEquals(2, $product->getFeatures()->count());
        //self::assertTrue($product->getFeatures()->contains($features[0]));
        //self::assertTrue($product->getFeatures()->contains($features[1]));

        //$this->em->getConnection()->getConfiguration()->setSQLLogger(null);
    }

    public function testJoinFromOwningSide()
    {
        $query = $this->em->createQuery('select f,p from Doctrine\Tests\Models\ECommerce\ECommerceFeature f join f.product p');
        $features = $query->getResult();
        self::assertEquals(0, count($features));
    }

    /**
     * @group DDC-1637
     */
    public function testMatching()
    {
        $this->createFixture();

        $product  = $this->em->find(ECommerceProduct::class, $this->product->getId());
        $features = $product->getFeatures();

        $results = $features->matching(new Criteria(
            Criteria::expr()->eq('description', 'Model writing tutorial')
        ));

        self::assertInstanceOf(Collection::class, $results);
        self::assertEquals(1, count($results));

        $results = $features->matching(new Criteria());

        self::assertInstanceOf(Collection::class, $results);
        self::assertEquals(2, count($results));
    }

    /**
     * @group DDC-2340
     */
    public function testMatchingOnDirtyCollection()
    {
        $this->createFixture();

        $product  = $this->em->find(ECommerceProduct::class, $this->product->getId());

        $thirdFeature = new ECommerceFeature();
        $thirdFeature->setDescription('Model writing tutorial');

        $features = $product->getFeatures();
        $features->add($thirdFeature);

        $results = $features->matching(new Criteria(
            Criteria::expr()->eq('description', 'Model writing tutorial')
        ));

        self::assertEquals(2, count($results));
    }

    public function testMatchingBis()
    {
        $this->createFixture();

        $product  = $this->em->find(ECommerceProduct::class, $this->product->getId());
        $features = $product->getFeatures();

        $thirdFeature = new ECommerceFeature();
        $thirdFeature->setDescription('Third feature');
        $product->addFeature($thirdFeature);

        $results = $features->matching(new Criteria(
            Criteria::expr()->eq('description', 'Third feature')
        ));

        self::assertInstanceOf(Collection::class, $results);
        self::assertCount(1, $results);

        $results = $features->matching(new Criteria());

        self::assertInstanceOf(Collection::class, $results);
        self::assertCount(3, $results);
    }

    private function createFixture()
    {
        $this->product->addFeature($this->firstFeature);
        $this->product->addFeature($this->secondFeature);
        $this->em->persist($this->product);

        $this->em->flush();
        $this->em->clear();
    }

    public function assertFeatureForeignKeyIs($value, ECommerceFeature $feature) {
        $foreignKey = $this->em->getConnection()->executeQuery(
            'SELECT product_id FROM ecommerce_features WHERE id=?',
            [$feature->getId()]
        )->fetchColumn();
        self::assertEquals($value, $foreignKey);
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\ECommerce\ECommerceProduct;

/**
 * Tests a self referential many-to-many association mapping (from a model to the same model, without inheritance).
 * For simplicity the relation duplicates entries in the association table
 * to remain symmetrical.
 */
class ManyToManySelfReferentialAssociationTest extends AbstractManyToManyAssociationTestCase
{
    /** @var string */
    protected $firstField = 'product_id';

    /** @var string */
    protected $secondField = 'related_id';

    /** @var string */
    protected $table = 'ecommerce_products_related';

    /** @var ECommerceProduct */
    private $firstProduct;

    /** @var ECommerceProduct */
    private $secondProduct;

    /** @var ECommerceProduct */
    private $firstRelated;

    /** @var ECommerceProduct */
    private $secondRelated;

    protected function setUp(): void
    {
        $this->useModelSet('ecommerce');

        parent::setUp();

        $this->firstProduct  = new ECommerceProduct();
        $this->secondProduct = new ECommerceProduct();
        $this->firstRelated  = new ECommerceProduct();
        $this->firstRelated->setName('Business');
        $this->secondRelated = new ECommerceProduct();
        $this->secondRelated->setName('Home');
    }

    public function testSavesAManyToManyAssociationWithCascadeSaveSet(): void
    {
        $this->firstProduct->addRelated($this->firstRelated);
        $this->firstProduct->addRelated($this->secondRelated);
        $this->_em->persist($this->firstProduct);
        $this->_em->flush();

        $this->assertForeignKeysContain(
            $this->firstProduct->getId(),
            $this->firstRelated->getId()
        );
        $this->assertForeignKeysContain(
            $this->firstProduct->getId(),
            $this->secondRelated->getId()
        );
    }

    public function testRemovesAManyToManyAssociation(): void
    {
        $this->firstProduct->addRelated($this->firstRelated);
        $this->firstProduct->addRelated($this->secondRelated);
        $this->_em->persist($this->firstProduct);
        $this->firstProduct->removeRelated($this->firstRelated);

        $this->_em->flush();

        $this->assertForeignKeysNotContain(
            $this->firstProduct->getId(),
            $this->firstRelated->getId()
        );
        $this->assertForeignKeysContain(
            $this->firstProduct->getId(),
            $this->secondRelated->getId()
        );
    }

    public function testEagerLoadsOwningSide(): void
    {
        $this->createLoadingFixture();
        $products = $this->findProducts();
        $this->assertLoadingOfOwningSide($products);
    }

    public function testLazyLoadsOwningSide(): void
    {
        $this->createLoadingFixture();

        $metadata                                          = $this->_em->getClassMetadata(ECommerceProduct::class);
        $metadata->associationMappings['related']['fetch'] = ClassMetadata::FETCH_LAZY;

        $query    = $this->_em->createQuery('SELECT p FROM Doctrine\Tests\Models\ECommerce\ECommerceProduct p');
        $products = $query->getResult();
        $this->assertLoadingOfOwningSide($products);
    }

    /** @psalm-param list<ECommerceProduct> $products */
    public function assertLoadingOfOwningSide(array $products): void
    {
        [$firstProduct, $secondProduct] = $products;
        self::assertCount(2, $firstProduct->getRelated());
        self::assertCount(2, $secondProduct->getRelated());

        $categories      = $firstProduct->getRelated();
        $firstRelatedBy  = $categories[0]->getRelated();
        $secondRelatedBy = $categories[1]->getRelated();

        self::assertCount(2, $firstRelatedBy);
        self::assertCount(2, $secondRelatedBy);

        self::assertInstanceOf(ECommerceProduct::class, $firstRelatedBy[0]);
        self::assertInstanceOf(ECommerceProduct::class, $firstRelatedBy[1]);
        self::assertInstanceOf(ECommerceProduct::class, $secondRelatedBy[0]);
        self::assertInstanceOf(ECommerceProduct::class, $secondRelatedBy[1]);

        $this->assertCollectionEquals($firstRelatedBy, $secondRelatedBy);
    }

    protected function createLoadingFixture(): void
    {
        $this->firstProduct->addRelated($this->firstRelated);
        $this->firstProduct->addRelated($this->secondRelated);
        $this->secondProduct->addRelated($this->firstRelated);
        $this->secondProduct->addRelated($this->secondRelated);
        $this->_em->persist($this->firstProduct);
        $this->_em->persist($this->secondProduct);

        $this->_em->flush();
        $this->_em->clear();
    }

    /** @psalm-return list<ECommerceProduct> */
    protected function findProducts(): array
    {
        $query = $this->_em->createQuery('SELECT p, r FROM Doctrine\Tests\Models\ECommerce\ECommerceProduct p LEFT JOIN p.related r ORDER BY p.id, r.id');

        return $query->getResult();
    }
}

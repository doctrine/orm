<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\AivusTest\Category;
use Doctrine\Tests\Models\AivusTest\Product;

/**
 * Tests many-to-many association mapping with unique=true.
 */
class ManyToManyWithUniqueTest extends AbstractManyToManyAssociationTestCase
{
    protected function setUp()
    {
        $this->useModelSet('AivusTest');
        parent::setUp();
    }

    public function testMoveProductFromOneCategoryToAnother()
    {
        $product = new Product();
        $this->_em->persist($product);

        $category1 = new Category();
        $category1->addProduct($product);
        $this->_em->persist($category1);

        $category2 = new Category();
        $this->_em->persist($category2);

        $this->_em->flush();

        $category1Id = $category1->getId();
        $category2Id = $category2->getId();

        $this->_em->clear();

        $categoryRepository = $this->_em->getRepository(Category::class);
        $productRepository = $this->_em->getRepository(Product::class);

        /** @var Product $product */
        $product = $productRepository->findOneBy([]);

        /** @var Category $category2 */
        $category2 = $categoryRepository->find($category2Id);

        /** @var Category $category1 */
        $category1 = $categoryRepository->find($category1Id);

        $category1->removeProduct($product);
        $category2->addProduct($product);

        $this->_em->flush();
    }
}

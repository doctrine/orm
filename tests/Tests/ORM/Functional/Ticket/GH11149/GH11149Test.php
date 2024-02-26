<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11149;

use Doctrine\ORM\PersistentCollection;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH11149Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            Locale::class,
            EagerProduct::class,
            EagerProductTranslation::class,
            RegularProduct::class,
            RegularProductTranslation::class,
        ]);
    }

    public function testFetchDefaultModeWithIndexBy(): void
    {
        // Load entities into database
        $this->_em->persist($product = new RegularProduct(11149));
        $this->_em->persist($locale = new Locale('de_DE'));
        $this->_em->persist(new RegularProductTranslation($product, $locale));
        $this->_em->flush();
        $this->_em->clear();

        // Fetch entity from database
        $product = $this->_em->find(RegularProduct::class, 11149);

        // Assert associated entity is not loaded eagerly
        static::assertInstanceOf(RegularProduct::class, $product);
        static::assertInstanceOf(PersistentCollection::class, $product->translations);
        static::assertFalse($product->translations->isInitialized());
        static::assertCount(1, $product->translations);

        // Assert associated entity is indexed by given property
        $translation = $product->translations->get('de_DE');
        static::assertInstanceOf(RegularProductTranslation::class, $translation);
    }

    public function testFetchDefaultModeThroughEagerRepositoryCall(): void
    {
        // Load entities into database
        $this->_em->persist($product = new RegularProduct(11185));
        $this->_em->persist($locale = new Locale('nl_NL'));
        $this->_em->persist(new RegularProductTranslation($product, $locale));
        $this->_em->flush();
        $this->_em->clear();

        // Fetch entity from database
        $queryBuilder = $this->_em->createQueryBuilder();

        $queryBuilder
            ->select('product', 'translations')
            ->from(RegularProduct::class, 'product')
            ->leftJoin('product.translations', 'translations')
            ->where($queryBuilder->expr()->eq('product.id', ':product_id'));

        $product = $queryBuilder
            ->getQuery()
            ->setParameter('product_id', 11185)
            ->getSingleResult();

        // Assert associated entity is loaded eagerly
        static::assertInstanceOf(RegularProduct::class, $product);
        static::assertInstanceOf(PersistentCollection::class, $product->translations);
        static::assertTrue($product->translations->isInitialized());
        static::assertCount(1, $product->translations);

        // Assert associated entity is indexed by given property
        $translation = $product->translations->get('nl_NL');
        static::assertInstanceOf(RegularProductTranslation::class, $translation);
    }

    public function testFetchEagerModeWithIndexBy(): void
    {
        // Load entities into database
        $this->_em->persist($product = new EagerProduct(11149));
        $this->_em->persist($locale = new Locale('fr_FR'));
        $this->_em->persist(new EagerProductTranslation($product, $locale));
        $this->_em->flush();
        $this->_em->clear();

        // Fetch entity from database
        $product = $this->_em->find(EagerProduct::class, 11149);

        // Assert associated entity is loaded eagerly
        static::assertInstanceOf(EagerProduct::class, $product);
        static::assertInstanceOf(PersistentCollection::class, $product->translations);
        static::assertTrue($product->translations->isInitialized());
        static::assertCount(1, $product->translations);

        // Assert associated entity is indexed by given property
        $translation = $product->translations->get('fr_FR');
        static::assertInstanceOf(EagerProductTranslation::class, $translation);
    }
}

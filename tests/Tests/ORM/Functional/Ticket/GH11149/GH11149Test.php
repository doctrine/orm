<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11149;

use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

#[RequiresPhp('8.1')]
class GH11149Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            Locale::class,
            Product::class,
            ProductTranslation::class,
        ]);
    }

    public function testEagerLoadAssociationIndexedByEntity(): void
    {
        $this->_em->persist($product = new Product(11149));
        $this->_em->persist($locale = new Locale('fr_FR'));
        $this->_em->persist(new ProductTranslation($product, $locale));

        $this->_em->flush();
        $this->_em->clear();

        $product = $this->_em->find(Product::class, 11149);
        static::assertInstanceOf(Product::class, $product);
        static::assertCount(1, $product->translations);

        $translation = $product->translations->get('fr_FR');
        static::assertInstanceOf(ProductTranslation::class, $translation);
    }
}

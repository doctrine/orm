<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11149;

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

    public function testAssociationIndexedByEntity(): void
    {
        $this->_em->persist($product = new RegularProduct(11149));
        $this->_em->persist($locale = new Locale('de_DE'));
        $this->_em->persist(new RegularProductTranslation($product, $locale));

        $this->_em->flush();
        $this->_em->clear();

        $product = $this->_em->find(RegularProduct::class, 11149);
        static::assertInstanceOf(RegularProduct::class, $product);
        static::assertCount(1, $product->translations);

        $translation = $product->translations->get('de_DE');
        static::assertInstanceOf(RegularProductTranslation::class, $translation);
    }

    public function testEagerLoadAssociationIndexedByEntity(): void
    {
        $this->_em->persist($product = new EagerProduct(11149));
        $this->_em->persist($locale = new Locale('fr_FR'));
        $this->_em->persist(new EagerProductTranslation($product, $locale));

        $this->_em->flush();
        $this->_em->clear();

        $product = $this->_em->find(EagerProduct::class, 11149);
        static::assertInstanceOf(EagerProduct::class, $product);
        static::assertCount(1, $product->translations);

        $translation = $product->translations->get('fr_FR');
        static::assertInstanceOf(EagerProductTranslation::class, $translation);
    }
}

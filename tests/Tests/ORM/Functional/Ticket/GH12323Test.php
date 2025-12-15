<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;
use Throwable;

/** @see https://github.com/doctrine/orm/issues/12323 */
class GH12323Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(GH12323Product::class);
    }

    public function testExceptionHandlingInNestedFunction(): void
    {
        $this->createProducts(2);

        $caught = false;

        try {
            $this->processProductsWithException();
        } catch (Throwable) {
            $caught = true;
        }

        self::assertTrue($caught, 'Exception thrown in nested function using toIterable() should be catchable');
    }

    private function createProducts(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $product       = new GH12323Product();
            $product->name = 'Product ' . $i;
            $this->_em->persist($product);
        }

        $this->_em->flush();
        $this->_em->clear();
    }

    private function processProductsWithException(): void
    {
        $query    = $this->_em->createQuery('SELECT p FROM ' . GH12323Product::class . ' p');
        $products = $query->toIterable();

        foreach ($products as $product) {
            throw new Exception('EXCEPTION');
        }
    }
}

#[Entity]
class GH12323Product
{
    #[Column(type: 'integer')]
    #[Id]
    #[GeneratedValue(strategy: 'AUTO')]
    public int $id;

    #[Column(type: 'string', length: 255)]
    public string $name;
}

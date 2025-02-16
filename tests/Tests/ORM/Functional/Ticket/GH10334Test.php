<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\Models\GH10334\GH10334Foo;
use Doctrine\Tests\Models\GH10334\GH10334FooCollection;
use Doctrine\Tests\Models\GH10334\GH10334Product;
use Doctrine\Tests\Models\GH10334\GH10334ProductType;
use Doctrine\Tests\Models\GH10334\GH10334ProductTypeId;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH10334Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([GH10334FooCollection::class, GH10334Foo::class, GH10334ProductType::class, GH10334Product::class]);
    }

    public function testTicket(): void
    {
        $collection = new GH10334FooCollection();
        $foo        = new GH10334Foo($collection, GH10334ProductTypeId::Jean);
        $foo2       = new GH10334Foo($collection, GH10334ProductTypeId::Short);

        $this->_em->persist($collection);
        $this->_em->persist($foo);
        $this->_em->persist($foo2);

        $this->_em->flush();
        $this->_em->clear();

        $result = $this->_em
            ->getRepository(GH10334FooCollection::class)
            ->createQueryBuilder('collection')
            ->leftJoin('collection.foos', 'foo')->addSelect('foo')
            ->getQuery()
            ->getResult();

        $this->_em
            ->getRepository(GH10334FooCollection::class)
            ->createQueryBuilder('collection')
            ->leftJoin('collection.foos', 'foo')->addSelect('foo')
            ->getQuery()
            ->getResult();

        $this->assertCount(1, $result);
        $this->assertCount(2, $result[0]->getFoos());
    }

    public function testGetChildWithBackedEnumId(): void
    {
        $jean    = new GH10334ProductType(GH10334ProductTypeId::Jean, 23.5);
        $short   = new GH10334ProductType(GH10334ProductTypeId::Short, 45.2);
        $product = new GH10334Product('Extra Large Blue', $jean);

        $jean->addProduct($product);

        $this->_em->persist($jean);
        $this->_em->persist($short);
        $this->_em->persist($product);

        $this->_em->flush();
        $this->_em->clear();

        $entity = $this->_em->find(GH10334Product::class, 1);

        self::assertNotNull($entity);
        self::assertSame($entity->getProductType()->getId(), GH10334ProductTypeId::Jean);
    }
}

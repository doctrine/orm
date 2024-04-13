<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Persisters;

use Doctrine\Tests\Models\CollectionWithInheritance\Cat;
use Doctrine\Tests\Models\CollectionWithInheritance\Dog;
use Doctrine\Tests\Models\CollectionWithInheritance\Pet;
use Doctrine\Tests\Models\CollectionWithInheritance\PetStore;
use Doctrine\Tests\OrmFunctionalTestCase;

use function assert;

class OneToManyPersisterInheritedCollectionTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            PetStore::class,
            Pet::class,
            Cat::class,
            Dog::class
        );
    }

    public function test(): void
    {
        // Given
        $store = new PetStore();
        $this->_em->persist($store);

        // When: set cats and dogs
        $store->setDogs([new Dog($store)]);
        $store->setCats([new Cat($store)]);
        $this->_em->flush();
        $this->_em->clear();

        // When: update dogs
        $store = $this->refresh($store);
        $store->setDogs([new Dog($store)]);
        $this->_em->flush();
        $this->_em->clear();

        // Then: cats should not change
        $store = $this->refresh($store);
        $this->assertNotEmpty($store->getCats());
    }

    private function refresh(PetStore $store): PetStore
    {
        $store = $this->_em->find(PetStore::class, $store->id);
        assert($store instanceof PetStore);

        return $store;
    }
}

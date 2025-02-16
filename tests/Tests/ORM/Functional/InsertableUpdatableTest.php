<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Upsertable\Insertable;
use Doctrine\Tests\Models\Upsertable\Updatable;
use Doctrine\Tests\OrmFunctionalTestCase;

class InsertableUpdatableTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(Updatable::class, Insertable::class);
    }

    public function testNotInsertableIsFetchedFromDatabase(): void
    {
        $insertable                    = new Insertable();
        $insertable->insertableContent = 'abcdefg';

        $this->_em->persist($insertable);
        $this->_em->flush();

        // gets inserted from default value and fetches value from database
        self::assertEquals('1234', $insertable->nonInsertableContent);

        $insertable->nonInsertableContent = '5678';

        $this->_em->flush();
        $this->_em->clear();

        $insertable = $this->_em->find(Insertable::class, $insertable->id);

        // during UPDATE statement it is not ignored
        self::assertEquals('5678', $insertable->nonInsertableContent);
    }

    public function testNotUpdatableIsFetched(): void
    {
        $updatable                      = new Updatable();
        $updatable->updatableContent    = 'foo';
        $updatable->nonUpdatableContent = 'foo';

        $this->_em->persist($updatable);
        $this->_em->flush();

        $updatable->updatableContent    = 'bar';
        $updatable->nonUpdatableContent = 'baz';

        $this->_em->flush();

        self::assertEquals('foo', $updatable->nonUpdatableContent);

        $this->_em->clear();

        $cleanUpdatable = $this->_em->find(Updatable::class, $updatable->id);

        self::assertEquals('bar', $cleanUpdatable->updatableContent);
        self::assertEquals('foo', $cleanUpdatable->nonUpdatableContent);
    }
}

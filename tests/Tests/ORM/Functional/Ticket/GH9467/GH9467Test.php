<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH9467;

use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Depends;

class GH9467Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(
            JoinedInheritanceRoot::class,
            JoinedInheritanceChild::class,
            JoinedInheritanceWritableColumn::class,
            JoinedInheritanceNonWritableColumn::class,
            JoinedInheritanceNonInsertableColumn::class,
            JoinedInheritanceNonUpdatableColumn::class,
        );
    }

    public function testRootColumnsInsert(): int
    {
        $entity                           = new JoinedInheritanceChild();
        $entity->rootWritableContent      = 'foo';
        $entity->rootNonWritableContent   = 'foo';
        $entity->rootNonInsertableContent = 'foo';
        $entity->rootNonUpdatableContent  = 'foo';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check INSERT query cause set database values into non-insertable entity properties
        self::assertEquals('foo', $entity->rootWritableContent);
        self::assertEquals('dbDefault', $entity->rootNonWritableContent);
        self::assertEquals('dbDefault', $entity->rootNonInsertableContent);
        self::assertEquals('foo', $entity->rootNonUpdatableContent);

        // check other process get same state
        $this->_em->clear();
        $entity = $this->_em->find(JoinedInheritanceChild::class, $entity->id);
        self::assertInstanceOf(JoinedInheritanceChild::class, $entity);
        self::assertEquals('foo', $entity->rootWritableContent);
        self::assertEquals('dbDefault', $entity->rootNonWritableContent);
        self::assertEquals('dbDefault', $entity->rootNonInsertableContent);
        self::assertEquals('foo', $entity->rootNonUpdatableContent);

        return $entity->id;
    }

    #[Depends('testRootColumnsInsert')]
    public function testRootColumnsUpdate(int $entityId): void
    {
        $entity = $this->_em->find(JoinedInheritanceChild::class, $entityId);
        self::assertInstanceOf(JoinedInheritanceChild::class, $entity);

        // update exist entity
        $entity->rootWritableContent      = 'bar';
        $entity->rootNonInsertableContent = 'bar';
        $entity->rootNonWritableContent   = 'bar';
        $entity->rootNonUpdatableContent  = 'bar';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check UPDATE query cause set database values into non-insertable entity properties
        self::assertEquals('bar', $entity->rootWritableContent);
        self::assertEquals('dbDefault', $entity->rootNonWritableContent);
        self::assertEquals('bar', $entity->rootNonInsertableContent);
        self::assertEquals('foo', $entity->rootNonUpdatableContent);

        // check other process get same state
        $this->_em->clear();
        $entity = $this->_em->find(JoinedInheritanceChild::class, $entity->id);
        self::assertInstanceOf(JoinedInheritanceChild::class, $entity);
        self::assertEquals('bar', $entity->rootWritableContent);
        self::assertEquals('dbDefault', $entity->rootNonWritableContent);
        self::assertEquals('bar', $entity->rootNonInsertableContent);
        self::assertEquals('foo', $entity->rootNonUpdatableContent);
    }

    public function testChildWritableColumnInsert(): int
    {
        $entity                  = new JoinedInheritanceWritableColumn();
        $entity->writableContent = 'foo';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check INSERT query doesn't change insertable entity property
        self::assertEquals('foo', $entity->writableContent);

        // check other process get same state
        $this->_em->clear();
        $entity = $this->_em->find(JoinedInheritanceWritableColumn::class, $entity->id);
        self::assertInstanceOf(JoinedInheritanceWritableColumn::class, $entity);
        self::assertEquals('foo', $entity->writableContent);

        return $entity->id;
    }

    #[Depends('testChildWritableColumnInsert')]
    public function testChildWritableColumnUpdate(int $entityId): void
    {
        $entity = $this->_em->find(JoinedInheritanceWritableColumn::class, $entityId);
        self::assertInstanceOf(JoinedInheritanceWritableColumn::class, $entity);

        // update exist entity
        $entity->writableContent = 'bar';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check UPDATE query doesn't change updatable entity property
        self::assertEquals('bar', $entity->writableContent);

        // check other process get same state
        $this->_em->clear();
        $entity = $this->_em->find(JoinedInheritanceWritableColumn::class, $entity->id);
        self::assertInstanceOf(JoinedInheritanceWritableColumn::class, $entity);
        self::assertEquals('bar', $entity->writableContent);
    }

    public function testChildNonWritableColumnInsert(): int
    {
        $entity                     = new JoinedInheritanceNonWritableColumn();
        $entity->nonWritableContent = 'foo';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check INSERT query cause set database value into non-insertable entity property
        self::assertEquals('dbDefault', $entity->nonWritableContent);

        // check other process get same state
        $this->_em->clear();
        $entity = $this->_em->find(JoinedInheritanceNonWritableColumn::class, $entity->id);
        self::assertInstanceOf(JoinedInheritanceNonWritableColumn::class, $entity);
        self::assertEquals('dbDefault', $entity->nonWritableContent);

        return $entity->id;
    }

    #[Depends('testChildNonWritableColumnInsert')]
    public function testChildNonWritableColumnUpdate(int $entityId): void
    {
        $entity = $this->_em->find(JoinedInheritanceNonWritableColumn::class, $entityId);
        self::assertInstanceOf(JoinedInheritanceNonWritableColumn::class, $entity);

        // update exist entity
        $entity->nonWritableContent = 'bar';
        // change some property to ensure UPDATE query will be done
        self::assertNotEquals('bar', $entity->rootField);
        $entity->rootField = 'bar';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check UPDATE query cause set database value into non-updatable entity property
        self::assertEquals('dbDefault', $entity->nonWritableContent);

        // check other process get same state
        $this->_em->clear();
        $entity = $this->_em->find(JoinedInheritanceNonWritableColumn::class, $entity->id);
        self::assertInstanceOf(JoinedInheritanceNonWritableColumn::class, $entity);
        self::assertEquals('bar', $entity->rootField); // check that UPDATE query done
        self::assertEquals('dbDefault', $entity->nonWritableContent);
    }

    public function testChildNonInsertableColumnInsert(): int
    {
        $entity                       = new JoinedInheritanceNonInsertableColumn();
        $entity->nonInsertableContent = 'foo';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check INSERT query cause set database value into non-insertable entity property
        self::assertEquals('dbDefault', $entity->nonInsertableContent);

        // check other process get same state
        $this->_em->clear();
        $entity = $this->_em->find(JoinedInheritanceNonInsertableColumn::class, $entity->id);
        self::assertInstanceOf(JoinedInheritanceNonInsertableColumn::class, $entity);
        self::assertEquals('dbDefault', $entity->nonInsertableContent);

        return $entity->id;
    }

    #[Depends('testChildNonInsertableColumnInsert')]
    public function testChildNonInsertableColumnUpdate(int $entityId): void
    {
        $entity = $this->_em->find(JoinedInheritanceNonInsertableColumn::class, $entityId);
        self::assertInstanceOf(JoinedInheritanceNonInsertableColumn::class, $entity);

        // update exist entity
        $entity->nonInsertableContent = 'bar';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check UPDATE query doesn't change updatable entity property
        self::assertEquals('bar', $entity->nonInsertableContent);

        // check other process get same state
        $this->_em->clear();
        $entity = $this->_em->find(JoinedInheritanceNonInsertableColumn::class, $entity->id);
        self::assertInstanceOf(JoinedInheritanceNonInsertableColumn::class, $entity);
        self::assertEquals('bar', $entity->nonInsertableContent);
    }

    public function testChildNonUpdatableColumnInsert(): int
    {
        $entity                      = new JoinedInheritanceNonUpdatableColumn();
        $entity->nonUpdatableContent = 'foo';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check INSERT query doesn't change insertable entity property
        self::assertEquals('foo', $entity->nonUpdatableContent);

        // check other process get same state
        $this->_em->clear();
        $entity = $this->_em->find(JoinedInheritanceNonUpdatableColumn::class, $entity->id);
        self::assertInstanceOf(JoinedInheritanceNonUpdatableColumn::class, $entity);
        self::assertEquals('foo', $entity->nonUpdatableContent);

        return $entity->id;
    }

    #[Depends('testChildNonUpdatableColumnInsert')]
    public function testChildNonUpdatableColumnUpdate(int $entityId): void
    {
        $entity = $this->_em->find(JoinedInheritanceNonUpdatableColumn::class, $entityId);
        self::assertInstanceOf(JoinedInheritanceNonUpdatableColumn::class, $entity);
        self::assertEquals('foo', $entity->nonUpdatableContent);

        // update exist entity
        $entity->nonUpdatableContent = 'bar';
        // change some property to ensure UPDATE query will be done
        self::assertNotEquals('bar', $entity->rootField);
        $entity->rootField = 'bar';

        $this->_em->persist($entity);
        $this->_em->flush();

        // check UPDATE query cause set database value into non-updatable entity property
        self::assertEquals('foo', $entity->nonUpdatableContent);

        // check other process get same state
        $this->_em->clear();
        $entity = $this->_em->find(JoinedInheritanceNonUpdatableColumn::class, $entity->id);
        self::assertInstanceOf(JoinedInheritanceNonUpdatableColumn::class, $entity);
        self::assertEquals('bar', $entity->rootField); // check that UPDATE query done
        self::assertEquals('foo', $entity->nonUpdatableContent);
    }
}

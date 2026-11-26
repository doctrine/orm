<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\DbalTypes\CustomIdObject;
use Doctrine\Tests\DbalTypes\CustomIdObjectType;
use Doctrine\Tests\OrmFunctionalTestCase;

class DatabaseValueComparisonTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        self::registerDbalType(CustomIdObjectType::NAME, CustomIdObjectType::class);

        parent::setUp();

        $this->createSchemaForModels(DatabaseValueComparisonEntity::class);
    }

    public function testSameDayDifferentTimeDoesNotProduceChangeSet(): void
    {
        $entity       = $this->createEntity();
        $entity->date = new DateTimeImmutable('2020-01-01 10:00:00');

        $this->_em->persist($entity);
        $this->_em->flush();
        $id = $entity->id;
        $this->_em->clear();

        $entity = $this->_em->find(DatabaseValueComparisonEntity::class, $id);
        self::assertInstanceOf(DatabaseValueComparisonEntity::class, $entity);

        // The date type stores only the day, so a different time of day on the
        // same day is not a change.
        $entity->date = new DateTimeImmutable('2020-01-01 23:00:00');

        $uow = $this->_em->getUnitOfWork();
        $uow->computeChangeSets();

        self::assertArrayNotHasKey('date', $uow->getEntityChangeSet($entity));
    }

    public function testDifferentDateProducesChangeSet(): void
    {
        $entity       = $this->createEntity();
        $entity->date = new DateTimeImmutable('2020-01-01 10:00:00');

        $this->_em->persist($entity);
        $this->_em->flush();
        $id = $entity->id;
        $this->_em->clear();

        $entity = $this->_em->find(DatabaseValueComparisonEntity::class, $id);
        self::assertInstanceOf(DatabaseValueComparisonEntity::class, $entity);

        $entity->date = new DateTimeImmutable('2020-01-02 10:00:00');

        $uow = $this->_em->getUnitOfWork();
        $uow->computeChangeSets();

        self::assertArrayHasKey('date', $uow->getEntityChangeSet($entity));
    }

    public function testEqualDateTimeImmutableDoesNotProduceChangeSet(): void
    {
        $entity           = $this->createEntity();
        $entity->dateTime = new DateTimeImmutable('2020-01-01 10:00:00');

        $this->_em->persist($entity);
        $this->_em->flush();
        $id = $entity->id;
        $this->_em->clear();

        $entity = $this->_em->find(DatabaseValueComparisonEntity::class, $id);
        self::assertInstanceOf(DatabaseValueComparisonEntity::class, $entity);

        // Replacing the date with an equal instance is not a change.
        $entity->dateTime = new DateTimeImmutable('2020-01-01 10:00:00');

        $uow = $this->_em->getUnitOfWork();
        $uow->computeChangeSets();

        self::assertArrayNotHasKey('dateTime', $uow->getEntityChangeSet($entity));
    }

    public function testDifferentDateTimeImmutableProducesChangeSet(): void
    {
        $entity           = $this->createEntity();
        $entity->dateTime = new DateTimeImmutable('2020-01-01 10:00:00');

        $this->_em->persist($entity);
        $this->_em->flush();
        $id = $entity->id;
        $this->_em->clear();

        $entity = $this->_em->find(DatabaseValueComparisonEntity::class, $id);
        self::assertInstanceOf(DatabaseValueComparisonEntity::class, $entity);

        $entity->dateTime = new DateTimeImmutable('2020-01-01 11:00:00');

        $uow = $this->_em->getUnitOfWork();
        $uow->computeChangeSets();

        self::assertArrayHasKey('dateTime', $uow->getEntityChangeSet($entity));
    }

    public function testReplacingMutableDateTimeWithEqualInstanceProducesChangeSet(): void
    {
        $entity                  = $this->createEntity();
        $entity->mutableDateTime = new DateTime('2020-01-01 10:00:00');

        $this->_em->persist($entity);
        $this->_em->flush();
        $id = $entity->id;
        $this->_em->clear();

        $entity = $this->_em->find(DatabaseValueComparisonEntity::class, $id);
        self::assertInstanceOf(DatabaseValueComparisonEntity::class, $entity);

        // Mutable object values are compared by identity, as in 3.7.
        $entity->mutableDateTime = new DateTime('2020-01-01 10:00:00');

        $uow = $this->_em->getUnitOfWork();
        $uow->computeChangeSets();

        self::assertArrayHasKey('mutableDateTime', $uow->getEntityChangeSet($entity));
    }

    public function testReplacingValueObjectWithEqualInstanceProducesChangeSet(): void
    {
        $entity        = $this->createEntity();
        $entity->value = new CustomIdObject('10');

        $this->_em->persist($entity);
        $this->_em->flush();
        $id = $entity->id;
        $this->_em->clear();

        $entity = $this->_em->find(DatabaseValueComparisonEntity::class, $id);
        self::assertInstanceOf(DatabaseValueComparisonEntity::class, $entity);

        // Custom object values are compared by identity, as in 3.7.
        $entity->value = new CustomIdObject('10');

        $uow = $this->_em->getUnitOfWork();
        $uow->computeChangeSets();

        self::assertArrayHasKey('value', $uow->getEntityChangeSet($entity));
    }

    public function testDifferentValueObjectProducesChangeSet(): void
    {
        $entity        = $this->createEntity();
        $entity->value = new CustomIdObject('10');

        $this->_em->persist($entity);
        $this->_em->flush();
        $id = $entity->id;
        $this->_em->clear();

        $entity = $this->_em->find(DatabaseValueComparisonEntity::class, $id);
        self::assertInstanceOf(DatabaseValueComparisonEntity::class, $entity);

        $entity->value = new CustomIdObject('12');

        $uow = $this->_em->getUnitOfWork();
        $uow->computeChangeSets();

        self::assertArrayHasKey('value', $uow->getEntityChangeSet($entity));
    }

    public function testRecomputeSingleEntityChangeSetComparesImmutableDates(): void
    {
        $entity           = $this->createEntity();
        $entity->dateTime = new DateTimeImmutable('2020-01-01 10:00:00');

        $this->_em->persist($entity);
        $this->_em->flush();
        $id = $entity->id;
        $this->_em->clear();

        $entity = $this->_em->find(DatabaseValueComparisonEntity::class, $id);
        self::assertInstanceOf(DatabaseValueComparisonEntity::class, $entity);

        $uow = $this->_em->getUnitOfWork();
        $uow->computeChangeSet($this->_em->getClassMetadata($entity::class), $entity);

        $entity->dateTime = new DateTimeImmutable('2020-01-01 10:00:00');

        $uow->recomputeSingleEntityChangeSet($this->_em->getClassMetadata($entity::class), $entity);

        self::assertArrayNotHasKey('dateTime', $uow->getEntityChangeSet($entity));
    }

    private function createEntity(): DatabaseValueComparisonEntity
    {
        $entity                  = new DatabaseValueComparisonEntity();
        $entity->date            = new DateTimeImmutable('2000-01-01 00:00:00');
        $entity->dateTime        = new DateTimeImmutable('2000-01-01 00:00:00');
        $entity->mutableDateTime = new DateTime('2000-01-01 00:00:00');
        $entity->value           = new CustomIdObject('default');

        return $entity;
    }
}

#[ORM\Entity]
class DatabaseValueComparisonEntity
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    #[ORM\Column(type: 'date_immutable')]
    public DateTimeImmutable $date;

    #[ORM\Column(type: 'datetime_immutable')]
    public DateTimeImmutable $dateTime;

    #[ORM\Column(type: 'datetime')]
    public DateTime $mutableDateTime;

    #[ORM\Column(type: CustomIdObjectType::NAME, length: 255)]
    public CustomIdObject $value;
}

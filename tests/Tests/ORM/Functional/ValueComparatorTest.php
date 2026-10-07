<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use DateTime;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Comparison;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\ValueComparator;
use Doctrine\Tests\OrmFunctionalTestCase;

class ValueComparatorTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->createSchemaForModels(ValueComparatorEntity::class);
    }

    public function testMutableDateIsClonedInTheOriginalDataSnapshot(): void
    {
        $entity = $this->createAndReloadEntity();

        $originalData = $this->_em->getUnitOfWork()->getOriginalEntityData($entity);

        self::assertEquals($entity->mutableDate, $originalData['mutableDate']);
        self::assertNotSame($entity->mutableDate, $originalData['mutableDate']);
    }

    public function testInPlaceMutationOfMutableDateIsDetected(): void
    {
        $entity = $this->createAndReloadEntity();

        $entity->mutableDate->modify('+1 day');

        $this->_em->getUnitOfWork()->computeChangeSet($this->_em->getClassMetadata($entity::class), $entity);

        $changeSet = $this->_em->getUnitOfWork()->getEntityChangeSet($entity);
        self::assertArrayHasKey('mutableDate', $changeSet);
    }

    public function testUnchangedMutableDateIsNotDirty(): void
    {
        $entity = $this->createAndReloadEntity();

        $this->_em->getUnitOfWork()->computeChangeSet($this->_em->getClassMetadata($entity::class), $entity);

        self::assertSame([], $this->_em->getUnitOfWork()->getEntityChangeSet($entity));
    }

    public function testSameComparatorKeepsTheReferenceComparison(): void
    {
        $entity = $this->createAndReloadEntity();

        $originalData = $this->_em->getUnitOfWork()->getOriginalEntityData($entity);
        self::assertSame($entity->optOutDate, $originalData['optOutDate']);

        $entity->optOutDate->modify('+1 day');

        $this->_em->getUnitOfWork()->computeChangeSet($this->_em->getClassMetadata($entity::class), $entity);

        self::assertSame([], $this->_em->getUnitOfWork()->getEntityChangeSet($entity));
    }

    public function testCustomComparatorIsUsed(): void
    {
        $entity = $this->createAndReloadEntity();
        $class  = $this->_em->getClassMetadata($entity::class);

        $entity->customDate->modify('+1 hour');
        $this->_em->getUnitOfWork()->computeChangeSet($class, $entity);
        self::assertSame([], $this->_em->getUnitOfWork()->getEntityChangeSet($entity));

        $entity->customDate->modify('+1 day');
        $this->_em->getUnitOfWork()->computeChangeSet($class, $entity);
        self::assertArrayHasKey('customDate', $this->_em->getUnitOfWork()->getEntityChangeSet($entity));
    }

    public function testDefaultMetadataForMutableTypes(): void
    {
        $class = $this->_em->getClassMetadata(ValueComparatorEntity::class);

        self::assertSame(Comparison::EqualMutable, $class->fieldMappings['mutableDate']->comparator);
        self::assertSame(Comparison::EqualMutable, $class->fieldMappings['payload']->comparator);
        self::assertNull($class->fieldMappings['name']->comparator);
        self::assertSame(Comparison::Same, $class->fieldMappings['optOutDate']->comparator);
    }

    private function createAndReloadEntity(): ValueComparatorEntity
    {
        $entity              = new ValueComparatorEntity();
        $entity->name        = 'foo';
        $entity->mutableDate = new DateTime('2020-01-01 00:00:00');
        $entity->optOutDate  = new DateTime('2020-01-01 00:00:00');
        $entity->customDate  = new DateTime('2020-01-01 00:00:00');
        $entity->payload     = ['foo' => 'bar'];

        $this->_em->persist($entity);
        $this->_em->flush();
        $this->_em->clear();

        $reloaded = $this->_em->find(ValueComparatorEntity::class, $entity->id);
        self::assertInstanceOf(ValueComparatorEntity::class, $reloaded);

        return $reloaded;
    }
}

#[Entity]
#[Table(name: 'value_comparator_entity')]
class ValueComparatorEntity
{
    #[Id]
    #[Column(type: 'integer')]
    #[GeneratedValue]
    public int|null $id = null;

    #[Column(type: 'string')]
    public string $name = '';

    #[Column(type: 'datetime')]
    public DateTime $mutableDate;

    #[Column(type: 'datetime', comparator: Comparison::Same)]
    public DateTime $optOutDate;

    #[Column(type: 'datetime', comparator: new ValueComparatorEntityDateComparator())]
    public DateTime $customDate;

    #[Column(type: 'json')]
    public array $payload = [];
}

/**
 * Compares dates by day, ignoring the time of day.
 */
class ValueComparatorEntityDateComparator implements ValueComparator
{
    public function equals(mixed $first, mixed $second): bool
    {
        if (! $first instanceof DateTime || ! $second instanceof DateTime) {
            return $first === $second;
        }

        return $first->format('Y-m-d') === $second->format('Y-m-d');
    }

    public function isMutable(): bool
    {
        return true;
    }
}

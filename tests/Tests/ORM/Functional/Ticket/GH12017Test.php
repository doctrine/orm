<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

class GH12017Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpEntitySchema([
            GH12017EntityWithGeneratedFields::class,
            GH12017EntityWithMixedFlags::class,
        ]);
    }

    public function testGeneratedFieldsShouldNotBeDetectedAsChanges(): void
    {
        $entity       = new GH12017EntityWithGeneratedFields();
        $entity->name = 'Test Entity';

        $this->_em->persist($entity);
        $this->_em->flush();

        // Simulate database-generated values being fetched back via property accessors
        // This mimics the behavior of assignDefaultVersionAndUpsertableValues()
        $metadata = $this->_em->getClassMetadata(GH12017EntityWithGeneratedFields::class);
        $metadata->setFieldValue($entity, 'generatedField', new DateTimeImmutable());
        $metadata->setFieldValue($entity, 'computedField', 'computed-value-from-db');

        $uow = $this->_em->getUnitOfWork();
        $uow->computeChangeSets();

        self::assertFalse(
            $uow->isScheduledForUpdate($entity),
            'Entity with only generated field changes should not be scheduled for update',
        );

        $changeSet = $uow->getEntityChangeSet($entity);
        self::assertEmpty($changeSet, 'Changeset should not include generated fields');
    }

    public function testRecomputeSingleEntityChangeSetWithGeneratedFields(): void
    {
        $entity       = new GH12017EntityWithGeneratedFields();
        $entity->name = 'Test Entity';

        $this->_em->persist($entity);
        $this->_em->flush();

        // Simulate database-generated values being fetched back via property accessors
        $metadata = $this->_em->getClassMetadata(GH12017EntityWithGeneratedFields::class);
        $metadata->setFieldValue($entity, 'generatedField', new DateTimeImmutable());
        $metadata->setFieldValue($entity, 'computedField', 'computed-value-from-db');

        $uow   = $this->_em->getUnitOfWork();
        $class = $this->_em->getClassMetadata(GH12017EntityWithGeneratedFields::class);
        $uow->recomputeSingleEntityChangeSet($class, $entity);

        self::assertFalse(
            $uow->isScheduledForUpdate($entity),
            'Entity should not be scheduled for update after recomputeSingleEntityChangeSet',
        );

        $changeSet = $uow->getEntityChangeSet($entity);
        self::assertEmpty($changeSet, 'Changeset should be empty after recomputeSingleEntityChangeSet');
    }

    public function testNotInsertableFieldsShouldNotBeInChangesetForNewEntities(): void
    {
        $entity                 = new GH12017EntityWithGeneratedFields();
        $entity->name           = 'Test Entity';
        $entity->generatedField = new DateTimeImmutable();
        $entity->computedField  = 'manually-set-value';

        $this->_em->persist($entity);

        $uow   = $this->_em->getUnitOfWork();
        $class = $this->_em->getClassMetadata(GH12017EntityWithGeneratedFields::class);
        $uow->computeChangeSet($class, $entity);

        $changeSet = $uow->getEntityChangeSet($entity);

        self::assertArrayHasKey('name', $changeSet, 'Name should be in changeset');
        self::assertArrayNotHasKey('generatedField', $changeSet, 'Generated field should not be in changeset for new entity');
        self::assertArrayNotHasKey('computedField', $changeSet, 'Computed field should not be in changeset for new entity');
    }

    public function testMixedInsertableUpdatableFlags(): void
    {
        $entity                            = new GH12017EntityWithMixedFlags();
        $entity->name                      = 'Test Entity';
        $entity->notInsertableButUpdatable = new DateTimeImmutable('2024-01-01 10:00:00');
        $entity->insertableButNotUpdatable = new DateTimeImmutable('2024-01-01 11:00:00');

        $this->_em->persist($entity);

        $uow   = $this->_em->getUnitOfWork();
        $class = $this->_em->getClassMetadata(GH12017EntityWithMixedFlags::class);
        $uow->computeChangeSet($class, $entity);

        $changeSet = $uow->getEntityChangeSet($entity);

        self::assertArrayNotHasKey('notInsertableButUpdatable', $changeSet, 'Field with insertable:false should not be in changeset for new entity');
        self::assertArrayHasKey('insertableButNotUpdatable', $changeSet, 'Field with insertable:true should be in changeset for new entity');

        $this->_em->flush();

        $entity->notInsertableButUpdatable = new DateTimeImmutable('2024-02-01 10:00:00');
        $entity->insertableButNotUpdatable = new DateTimeImmutable('2024-02-01 11:00:00');

        $uow->computeChangeSets();
        $changeSet = $uow->getEntityChangeSet($entity);

        self::assertArrayHasKey('notInsertableButUpdatable', $changeSet, 'Field with updatable:true should be in changeset for managed entity');
        self::assertArrayNotHasKey('insertableButNotUpdatable', $changeSet, 'Field with updatable:false should not be in changeset for managed entity');
    }
}

#[ORM\Entity]
#[ORM\Table(name: 'gh12017_entity_with_generated_fields')]
class GH12017EntityWithGeneratedFields
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int|null $id = null;

    #[ORM\Column(type: 'string')]
    public string|null $name = null;

    #[ORM\Column(
        name: 'generated_field',
        type: 'datetime_immutable',
        nullable: true,
        insertable: false,
        updatable: false,
        generated: 'ALWAYS',
    )]
    public DateTimeImmutable|null $generatedField = null;

    #[ORM\Column(
        name: 'computed_field',
        type: 'string',
        nullable: true,
        insertable: false,
        updatable: false,
        generated: 'ALWAYS',
    )]
    public string|null $computedField = null;
}

#[ORM\Entity]
#[ORM\Table(name: 'gh12017_entity_with_mixed_flags')]
class GH12017EntityWithMixedFlags
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public int|null $id = null;

    #[ORM\Column(type: 'string')]
    public string|null $name = null;

    #[ORM\Column(
        name: 'not_insertable_but_updatable',
        type: 'datetime_immutable',
        nullable: true,
        insertable: false,
        updatable: true,
    )]
    public DateTimeImmutable|null $notInsertableButUpdatable = null;

    #[ORM\Column(
        name: 'insertable_but_not_updatable',
        type: 'datetime_immutable',
        insertable: true,
        updatable: false,
    )]
    public DateTimeImmutable|null $insertableButNotUpdatable = null;
}

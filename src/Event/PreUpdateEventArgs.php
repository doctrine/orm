<?php

declare(strict_types=1);

namespace Doctrine\ORM\Event;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use InvalidArgumentException;

use function get_debug_type;
use function sprintf;

/**
 * Class that holds event arguments for a preUpdate event.
 *
 * @extends LifecycleEventArgs<EntityManagerInterface>
 */
class PreUpdateEventArgs extends LifecycleEventArgs
{
    /** @var array<string, array{mixed, mixed}|PersistentCollection> */
    private array $entityChangeSet;

    /**
     * @param mixed[][] $changeSet
     * @phpstan-param array<string, array{mixed, mixed}|PersistentCollection> $changeSet
     */
    public function __construct(object $entity, EntityManagerInterface $em, array &$changeSet)
    {
        parent::__construct($entity, $em);

        $this->entityChangeSet = &$changeSet;
    }

    /**
     * Retrieves entity changeset.
     *
     * @return mixed[][]
     * @phpstan-return array<string, array{mixed, mixed}|PersistentCollection>
     */
    public function getEntityChangeSet(): array
    {
        return $this->entityChangeSet;
    }

    /**
     * Checks if field has a changeset.
     */
    public function hasChangedField(string $field): bool
    {
        return isset($this->entityChangeSet[$field]);
    }

    /**
     * Gets the old value of the changeset of the changed field.
     */
    public function getOldValue(string $field): mixed
    {
        $this->assertValidField($field);

        return $this->entityChangeSet[$field][0];
    }

    /**
     * Gets the new value of the changeset of the changed field.
     */
    public function getNewValue(string $field): mixed
    {
        $this->assertValidField($field);

        return $this->entityChangeSet[$field][1];
    }

    /**
     * Sets the new value of this field.
     */
    public function setNewValue(string $field, mixed $value): void
    {
        $this->assertValidField($field);

        $this->entityChangeSet[$field][1] = $value;
    }

    /**
     * Asserts the field exists in changeset.
     *
     * @throws InvalidArgumentException
     */
    private function assertValidField(string $field): void
    {
        if (! isset($this->entityChangeSet[$field])) {
            throw new InvalidArgumentException(sprintf(
                'Field "%s" is not a valid field of the entity "%s" in PreUpdateEventArgs.',
                $field,
                get_debug_type($this->getObject()),
            ));
        }
    }
}

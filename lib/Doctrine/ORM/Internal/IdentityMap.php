<?php

namespace Doctrine\ORM\Internal;

use Countable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\ORMInvalidArgumentException;

use function array_map;
use function array_sum;
use function get_class;
use function implode;
use function in_array;

class IdentityMap implements Countable
{
    /** @var EntityManagerInterface */
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Map of all identifiers of managed entities.
     * Keys are object ids (spl_object_hash).
     *
     * @var mixed[]
     * @psalm-var array<string, array<string, mixed>>
     */
    private $entityIdentifiers = [];

    /**
     * The identity map that holds references to all managed entities that have
     * an identity. The entities are grouped by their class name.
     * Since all classes in a hierarchy must share the same identifier set,
     * we always take the root class name of the hierarchy.
     *
     * @var mixed[]
     * @psalm-var array<class-string, array<string, object|null>>
     */
    private $identityMap = [];

    public function addEntityIdentifier(string $oid, $identifier): void
    {
        $this->entityIdentifiers[$oid] = $identifier;
    }

    /**
     * Clears the identity map.
     */
    public function clear(): void
    {
        $this->identityMap       =
        $this->entityIdentifiers = [];
    }

    /**
     * Calculates the size of the UnitOfWork. The size of the UnitOfWork is the
     * number of entities in the identity map.
     */
    public function count(): int
    {
        $countArray = array_map('count', $this->identityMap);

        return array_sum($countArray);
    }

    /**
     * @return mixed|mixed[]
     */
    public function getEntityIdentifier(string $oid)
    {
        return $this->entityIdentifiers[$oid] ?? null;
    }

    public function hasEntityIdentifier(string $oid): bool
    {
        return isset($this->entityIdentifiers[$oid]) && ! empty($this->entityIdentifiers[$oid]);
    }

    public function unsetEntityIdentifier(string $oid): void
    {
        unset($this->entityIdentifiers[$oid]);
    }

    /**
     * Registers an entity in the identity map.
     * Note that entities in a hierarchy are registered with the class name of
     * the root entity.
     *
     * @param object $entity The entity to register.
     *
     * @throws ORMInvalidArgumentException
     *
     * @ignore
     */
    public function addToIdentityMap(object $entity): void
    {
        $oid           = ObjectIdFetcher::fetchObjectId($entity);
        $classMetadata = $this->getClassMetadata($entity);
        $identifier    = $this->getEntityIdentifier($oid);

        if (empty($identifier) || in_array(null, $identifier, true)) {
            throw ORMInvalidArgumentException::entityWithoutIdentity($classMetadata->name, $entity);
        }

        $this->identityMap[$classMetadata->rootEntityName][$this->getIdHash($oid)] = $entity;
    }

    /**
     * Gets the identity map of the UnitOfWork.
     *
     * @psalm-return array<class-string, array<string, object|null>>
     */
    public function & getIdentityMap(): iterable
    {
        return $this->identityMap;
    }

    /**
     * Checks whether an entity is registered in the identity map.
     */
    public function isInIdentityMap(object $entity): bool
    {
        $oid = ObjectIdFetcher::fetchObjectId($entity);

        if (! $this->hasEntityIdentifier($oid)) {
            return false;
        }

        $classMetadata = $this->getClassMetadata($entity);
        $idHash        = $this->getIdHash($oid);

        return isset($this->identityMap[$classMetadata->rootEntityName][$idHash]);
    }

    /**
     * Removes an entity from the identity map.
     *
     * @throws ORMInvalidArgumentException
     *
     * @ignore
     */
    public function removeFromIdentityMap(object $entity): void
    {
        $classMetadata = $this->getClassMetadata($entity);
        $idHash        = $this->getIdHash(ObjectIdFetcher::fetchObjectId($entity));

        if ($idHash === '') {
            throw ORMInvalidArgumentException::entityHasNoIdentity($entity, 'remove from identity map');
        }

        unset($this->identityMap[$classMetadata->rootEntityName][$idHash]);
    }

    /**
     * @deprecated
     *
     * @use self::count()
     */
    public function size(): int
    {
        return $this->count();
    }

    /**
     * Tries to find an entity with the given identifier in the identity map of
     * this UnitOfWork.
     *
     * @param mixed  $id            The entity identifier to look for.
     * @param string $rootClassName The name of the root class of the mapped entity hierarchy.
     *
     * @return object|false Returns the entity with the specified identifier if it exists in
     *                      this UnitOfWork, FALSE otherwise.
     *
     * @todo: refactor return type to object|null
     * @psalm-param class-string $rootClassName
     */
    public function tryGetById($id, string $rootClassName)
    {
        $idHash = implode(' ', (array) $id);

        return $this->identityMap[$rootClassName][$idHash] ?? false;
    }

    /**
     * INTERNAL:
     * Tries to get an entity by its identifier hash. If no entity is found for
     * the given hash, FALSE is returned.
     *
     * @param mixed $idHash (must be possible to cast it to string)
     *
     * @return object|bool The found entity or FALSE.
     *
     * @todo: refactor return type to object|null
     * @ignore
     */
    public function tryGetByIdHash($idHash, string $rootClassName)
    {
        $stringIdHash = (string) $idHash;

        return $this->identityMap[$rootClassName][$stringIdHash] ?? false;
    }

    protected function getClassMetadata(object $entity): ClassMetadata
    {
        return $this->entityManager->getClassMetadata(get_class($entity));
    }

    protected function getIdHash(string $oid): string
    {
        return implode(' ', $this->entityIdentifiers[$oid]);
    }
}

<?php

namespace Doctrine\ORM\Internal;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\ORMInvalidArgumentException;

use function array_map;
use function array_sum;
use function get_class;
use function implode;
use function in_array;
use function spl_object_hash;

class IdentityMap
{
    /** @var EntityManagerInterface */
    private $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
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

    public function getEntityIdentifier(string $oid)
    {
        return $this->entityIdentifiers[$oid];
    }

    public function hasEntityIdentifier(string $oid): bool
    {
        return isset($this->entityIdentifiers[$oid]);
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
        $classMetadata = $this->getClassMetadata($entity);
        $identifier    = $this->entityIdentifiers[spl_object_hash($entity)];

        if (empty($identifier) || in_array(null, $identifier, true)) {
            throw ORMInvalidArgumentException::entityWithoutIdentity($classMetadata->name, $entity);
        }

        $idHash                                      = implode(' ', $identifier);
        $rootEntityName                              = $classMetadata->rootEntityName;
        $this->identityMap[$rootEntityName][$idHash] = $entity;
    }

    /**
     * Checks whether an identifier hash exists in the identity map.
     *
     * @ignore
     */
    public function containsIdHash(string $idHash, string $rootClassName): bool
    {
        return isset($this->identityMap[$rootClassName][$idHash]);
    }

    /**
     * Gets an entity in the identity map by its identifier hash.
     *
     * @ignore
     */
    public function getByIdHash(string $idHash, string $rootClassName): object
    {
        return $this->identityMap[$rootClassName][$idHash];
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
        $oid = spl_object_hash($entity);

        if (empty($this->entityIdentifiers[$oid])) {
            return false;
        }

        $classMetadata = $this->getClassMetadata($entity);
        $idHash        = implode(' ', $this->entityIdentifiers[$oid]);

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
        $oid           = spl_object_hash($entity);
        $classMetadata = $this->getClassMetadata($entity);
        $idHash        = implode(' ', $this->entityIdentifiers[$oid]);

        if ($idHash === '') {
            throw ORMInvalidArgumentException::entityHasNoIdentity($entity, 'remove from identity map');
        }

        $rootEntityName = $classMetadata->rootEntityName;

        unset($this->identityMap[$rootEntityName][$idHash]);
        $this->unsetEntityIdentifier($oid);
    }

    /**
     * Calculates the size of the UnitOfWork. The size of the UnitOfWork is the
     * number of entities in the identity map.
     */
    public function size(): int
    {
        $countArray = array_map('count', $this->identityMap);

        return array_sum($countArray);
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
        return $this->em->getClassMetadata(get_class($entity));
    }

    /**
     * Clears the identity map.
     */
    public function clear(): void
    {
        $this->identityMap       =
        $this->entityIdentifiers = [];
    }
}

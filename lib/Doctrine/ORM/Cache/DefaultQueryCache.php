<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\Cache;
use Doctrine\ORM\Cache\Exception\FeatureNotImplemented;
use Doctrine\ORM\Cache\Exception\NonCacheableEntity;
use Doctrine\ORM\Cache\Logging\CacheLogger;
use Doctrine\ORM\Cache\Persister\CachedPersister;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMetadata;
use Doctrine\ORM\Mapping\ToOneAssociationMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\ResultSetMapping;
use ProxyManager\Proxy\GhostObjectInterface;
use function array_map;
use function array_shift;
use function array_unshift;
use function count;
use function is_array;
use function key;
use function reset;

/**
 * Default query cache implementation.
 */
class DefaultQueryCache implements QueryCache
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var Region */
    private $region;

    /** @var QueryCacheValidator */
    private $validator;

    /** @var CacheLogger */
    protected $cacheLogger;

    /** @var mixed[] */
    private static $hints = [Query::HINT_CACHE_ENABLED => true];

    /**
     * @param EntityManagerInterface $em     The entity manager.
     * @param Region                 $region The query region.
     */
    public function __construct(EntityManagerInterface $em, Region $region)
    {
        $cacheConfig = $em->getConfiguration()->getSecondLevelCacheConfiguration();

        $this->em          = $em;
        $this->region      = $region;
        $this->cacheLogger = $cacheConfig->getCacheLogger();
        $this->validator   = $cacheConfig->getQueryValidator();
    }

    /**
     * {@inheritdoc}
     */
    public function get(QueryCacheKey $key, ResultSetMapping $rsm, array $hints = [])
    {
        if (! ($key->cacheMode & Cache::MODE_GET)) {
            return null;
        }

        $cacheEntry = $this->region->get($key);

        if (! $cacheEntry instanceof QueryCacheEntry) {
            return null;
        }

        if (! $this->validator->isValid($key, $cacheEntry)) {
            $this->region->evict($key);

            return null;
        }

        $result      = [];
        $entityName  = reset($rsm->aliasMap);
        $hasRelation = ! empty($rsm->relationMap);
        $unitOfWork  = $this->em->getUnitOfWork();
        $persister   = $unitOfWork->getEntityPersister($entityName);
        $region      = $persister->getCacheRegion();
        $regionName  = $region->getName();

        $cm = $this->em->getClassMetadata($entityName);

        $generateKeys = static function (array $entry) use ($cm) : EntityCacheKey {
            return new EntityCacheKey($cm->getRootClassName(), $entry['identifier']);
        };

        $cacheKeys = new CollectionCacheEntry(array_map($generateKeys, $cacheEntry->result));
        $entries   = $region->getMultiple($cacheKeys);

        // @TODO - move to cache hydration component
        foreach ($cacheEntry->result as $index => $entry) {
            $entityEntry = is_array($entries) ? ($entries[$index] ?? null) : null;

            if ($entityEntry === null) {
                if ($this->cacheLogger !== null) {
                    $this->cacheLogger->entityCacheMiss($regionName, $cacheKeys->identifiers[$index]);
                }

                return null;
            }

            if ($this->cacheLogger !== null) {
                $this->cacheLogger->entityCacheHit($regionName, $cacheKeys->identifiers[$index]);
            }

            if (! $hasRelation) {
                $result[$index] = $unitOfWork->createEntity(
                    $entityEntry->class,
                    $entityEntry->resolveAssociationEntries($this->em),
                    self::$hints
                );

                continue;
            }

            $data = $entityEntry->data;

            foreach ($entry['associations'] as $name => $assoc) {
                $assocPersister = $unitOfWork->getEntityPersister($assoc['targetEntity']);
                $assocRegion    = $assocPersister->getCacheRegion();
                $assocMetadata  = $this->em->getClassMetadata($assoc['targetEntity']);

                // *-to-one association
                if (isset($assoc['identifier'])) {
                    $assocKey = new EntityCacheKey($assocMetadata->getRootClassName(), $assoc['identifier']);

                    $assocEntry = $assocRegion->get($assocKey);
                    if ($assocEntry === null) {
                        if ($this->cacheLogger !== null) {
                            $this->cacheLogger->entityCacheMiss($assocRegion->getName(), $assocKey);
                        }

                        $unitOfWork->hydrationComplete();

                        return null;
                    }

                    $data[$name] = $unitOfWork->createEntity(
                        $assocEntry->class,
                        $assocEntry->resolveAssociationEntries($this->em),
                        self::$hints
                    );

                    if ($this->cacheLogger !== null) {
                        $this->cacheLogger->entityCacheHit($assocRegion->getName(), $assocKey);
                    }

                    continue;
                }

                if (! isset($assoc['list']) || empty($assoc['list'])) {
                    continue;
                }

                $generateKeys = static function ($id) use ($assocMetadata) : EntityCacheKey {
                    return new EntityCacheKey($assocMetadata->getRootClassName(), $id);
                };

                $assocKeys    = new CollectionCacheEntry(array_map($generateKeys, $assoc['list']));
                $assocEntries = $assocRegion->getMultiple($assocKeys);

                // *-to-many association
                $collection = [];

                foreach ($assoc['list'] as $assocIndex => $assocId) {
                    $assocEntry = is_array($assocEntries) ? ($assocEntries[$assocIndex] ?? null) : null;

                    if ($assocEntry === null) {
                        if ($this->cacheLogger !== null) {
                            $this->cacheLogger->entityCacheMiss($assocRegion->getName(), $assocKeys->identifiers[$assocIndex]);
                        }

                        $unitOfWork->hydrationComplete();

                        return null;
                    }

                    $collection[$assocIndex] = $unitOfWork->createEntity(
                        $assocEntry->class,
                        $assocEntry->resolveAssociationEntries($this->em),
                        self::$hints
                    );

                    if ($this->cacheLogger !== null) {
                        $this->cacheLogger->entityCacheHit($assocRegion->getName(), $assocKeys->identifiers[$assocIndex]);
                    }
                }

                $data[$name] = $collection;
            }

            foreach ($data as $fieldName => $unCachedAssociationData) {
                // In some scenarios, such as EAGER+ASSOCIATION+ID+CACHE, the
                // cache key information in `$cacheEntry` will not contain details
                // for fields that are associations.
                //
                // This means that `$data` keys for some associations that may
                // actually not be cached will not be converted to actual association
                // data, yet they contain L2 cache AssociationCacheEntry objects.
                //
                // We need to unwrap those associations into proxy references,
                // since we don't have actual data for them except for identifiers.
                if ($unCachedAssociationData instanceof AssociationCacheEntry) {
                    $data[$fieldName] = $this->em->getReference(
                        $unCachedAssociationData->class,
                        $unCachedAssociationData->identifier
                    );
                }
            }

            $result[$index] = $unitOfWork->createEntity($entityEntry->class, $data, self::$hints);
        }

        $unitOfWork->hydrationComplete();

        return $result;
    }

    /**
     * {@inheritdoc}
     *
     * @param mixed[] $hints
     */
    public function put(QueryCacheKey $key, ResultSetMapping $rsm, $result, array $hints = [])
    {
        if ($rsm->scalarMappings) {
            throw FeatureNotImplemented::scalarResults();
        }

        if (count($rsm->entityMappings) > 1) {
            throw FeatureNotImplemented::multipleRootEntities();
        }

        if (! $rsm->isSelect) {
            throw FeatureNotImplemented::nonSelectStatements();
        }

        if (isset($hints[Query::HINT_FORCE_PARTIAL_LOAD]) && $hints[Query::HINT_FORCE_PARTIAL_LOAD]) {
            throw FeatureNotImplemented::partialEntities();
        }

        if (! ($key->cacheMode & Cache::MODE_PUT)) {
            return false;
        }

        $data       = [];
        $entityName = reset($rsm->aliasMap);
        $rootAlias  = key($rsm->aliasMap);
        $unitOfWork = $this->em->getUnitOfWork();
        $persister  = $unitOfWork->getEntityPersister($entityName);

        if (! ($persister instanceof CachedPersister)) {
            throw NonCacheableEntity::fromEntity($entityName);
        }

        $region = $persister->getCacheRegion();

        foreach ($result as $index => $entity) {
            $identifier = $unitOfWork->getEntityIdentifier($entity);
            $entityKey  = new EntityCacheKey($entityName, $identifier);

            if (($key->cacheMode & Cache::MODE_REFRESH) || ! $region->contains($entityKey)) {
                // Cancel put result if entity put fail
                if (! $persister->storeEntityCache($entity, $entityKey)) {
                    return false;
                }
            }

            $data[$index]['identifier']   = $identifier;
            $data[$index]['associations'] = [];

            // @TODO - move to cache hydration components
            foreach ($rsm->relationMap as $alias => $name) {
                $parentAlias = $rsm->parentAliasMap[$alias];
                $parentClass = $rsm->aliasMap[$parentAlias];
                $metadata    = $this->em->getClassMetadata($parentClass);
                $association = $metadata->getProperty($name);
                $assocValue  = $this->getAssociationValue($rsm, $alias, $entity);

                if ($assocValue === null) {
                    continue;
                }

                // root entity association
                if ($rootAlias === $parentAlias) {
                    // Cancel put result if association put fail
                    $assocInfo = $this->storeAssociationCache($key, $association, $assocValue);
                    if ($assocInfo === null) {
                        return false;
                    }

                    $data[$index]['associations'][$name] = $assocInfo;

                    continue;
                }

                // store single nested association
                if (! is_array($assocValue)) {
                    // Cancel put result if association put fail
                    if ($this->storeAssociationCache($key, $association, $assocValue) === null) {
                        return false;
                    }

                    continue;
                }

                // store array of nested association
                foreach ($assocValue as $aVal) {
                    // Cancel put result if association put fail
                    if ($this->storeAssociationCache($key, $association, $aVal) === null) {
                        return false;
                    }
                }
            }
        }

        return $this->region->put($key, new QueryCacheEntry($data));
    }

    /**
     * @param mixed[] $assocValue
     *
     * @return mixed[]|null
     */
    private function storeAssociationCache(QueryCacheKey $key, AssociationMetadata $association, $assocValue)
    {
        $unitOfWork     = $this->em->getUnitOfWork();
        $assocPersister = $unitOfWork->getEntityPersister($association->getTargetEntity());
        $assocMetadata  = $assocPersister->getClassMetadata();
        $assocRegion    = $assocPersister->getCacheRegion();

        // Handle *-to-one associations
        if ($association instanceof ToOneAssociationMetadata) {
            $assocIdentifier = $unitOfWork->getEntityIdentifier($assocValue);
            $entityKey       = new EntityCacheKey($assocMetadata->getRootClassName(), $assocIdentifier);

            if ((! $assocValue instanceof GhostObjectInterface && ($key->cacheMode & Cache::MODE_REFRESH)) || ! $assocRegion->contains($entityKey)) {
                // Entity put fail
                if (! $assocPersister->storeEntityCache($assocValue, $entityKey)) {
                    return null;
                }
            }

            return [
                'targetEntity'  => $assocMetadata->getRootClassName(),
                'identifier'    => $assocIdentifier,
            ];
        }

        // Handle *-to-many associations
        $list = [];

        foreach ($assocValue as $assocItemIndex => $assocItem) {
            $assocIdentifier = $unitOfWork->getEntityIdentifier($assocItem);
            $entityKey       = new EntityCacheKey($assocMetadata->getRootClassName(), $assocIdentifier);

            if (($key->cacheMode & Cache::MODE_REFRESH) || ! $assocRegion->contains($entityKey)) {
                // Entity put fail
                if (! $assocPersister->storeEntityCache($assocItem, $entityKey)) {
                    return null;
                }
            }

            $list[$assocItemIndex] = $assocIdentifier;
        }

        return [
            'targetEntity' => $assocMetadata->getRootClassName(),
            'list'         => $list,
        ];
    }

    /**
     * @param string $assocAlias
     * @param object $entity
     *
     * @return mixed[]|object
     */
    private function getAssociationValue(ResultSetMapping $rsm, $assocAlias, $entity)
    {
        $path  = [];
        $alias = $assocAlias;

        while (isset($rsm->parentAliasMap[$alias])) {
            $parent = $rsm->parentAliasMap[$alias];
            $field  = $rsm->relationMap[$alias];
            $class  = $rsm->aliasMap[$parent];

            array_unshift($path, [
                'field'  => $field,
                'class'  => $class,
            ]);

            $alias = $parent;
        }

        return $this->getAssociationPathValue($entity, $path);
    }

    /**
     * @param mixed     $value
     * @param mixed[][] $path
     *
     * @return mixed[]|object|null
     */
    private function getAssociationPathValue($value, array $path)
    {
        $mapping     = array_shift($path);
        $metadata    = $this->em->getClassMetadata($mapping['class']);
        $association = $metadata->getProperty($mapping['field']);
        $value       = $association->getValue($value);

        if ($value === null) {
            return null;
        }

        if (empty($path)) {
            return $value;
        }

        // Handle *-to-one associations
        if ($association instanceof ToOneAssociationMetadata) {
            return $this->getAssociationPathValue($value, $path);
        }

        $values = [];

        foreach ($value as $item) {
            $values[] = $this->getAssociationPathValue($item, $path);
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function clear()
    {
        return $this->region->evictAll();
    }

    /**
     * {@inheritdoc}
     */
    public function getRegion()
    {
        return $this->region;
    }
}

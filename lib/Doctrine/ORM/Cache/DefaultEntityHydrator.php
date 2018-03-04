<?php

declare(strict_types=1);

namespace Doctrine\ORM\Cache;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\FetchMode;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\OneToOneAssociationMetadata;
use Doctrine\ORM\Mapping\ToOneAssociationMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Utility\StaticClassNameConverter;
use function array_merge;

/**
 * Default hydrator cache for entities
 */
class DefaultEntityHydrator implements EntityHydrator
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var UnitOfWork */
    private $uow;

    /** @var mixed[] */
    private static $hints = [Query::HINT_CACHE_ENABLED => true];

    /**
     * @param EntityManagerInterface $em The entity manager.
     */
    public function __construct(EntityManagerInterface $em)
    {
        $this->em  = $em;
        $this->uow = $em->getUnitOfWork();
    }

    /**
     * {@inheritdoc}
     */
    public function buildCacheEntry(ClassMetadata $metadata, EntityCacheKey $key, $entity)
    {
        $identifierFlattener = $this->em->getIdentifierFlattener();
        $persister           = $this->uow->getEntityPersister($metadata->getClassName());

        $data = $this->uow->getOriginalEntityData($entity);
        $data = array_merge($data, $persister->getIdentifier($entity)); // why update has no identifier values ?

        if ($metadata->isVersioned()) {
            $data[$metadata->versionProperty->getName()] = $metadata->versionProperty->getValue($entity);
        }

        foreach ($metadata->getDeclaredPropertiesIterator() as $name => $association) {
            if (! isset($data[$name]) || $association instanceof FieldMetadata) {
                continue;
            }

            if (! $association instanceof ToOneAssociationMetadata) {
                unset($data[$name]);

                continue;
            }

            $targetEntity        = $association->getTargetEntity();
            $targetClassMetadata = $this->em->getClassMetadata($targetEntity);
            $targetPersister     = $this->uow->getEntityPersister($targetEntity);

            if (! $association->getCache()) {
                $owningAssociation = ! $association->isOwningSide()
                    ? $targetClassMetadata->getProperty($association->getMappedBy())
                    : $association;
                $associationIds    = $identifierFlattener->flattenIdentifier(
                    $targetClassMetadata,
                    $targetPersister->getIdentifier($data[$name])
                );

                unset($data[$name]);

                foreach ($associationIds as $fieldName => $fieldValue) {
                    // $fieldName = "name"
                    // $fieldColumnName = "custom_name"
                    $property = $targetClassMetadata->getProperty($fieldName);

                    if ($property instanceof FieldMetadata) {
                        foreach ($owningAssociation->getJoinColumns() as $joinColumn) {
                            // $joinColumnName = "custom_name"
                            // $joinColumnReferencedColumnName = "other_side_of_assoc_column_name"
                            if ($joinColumn->getReferencedColumnName() !== $property->getColumnName()) {
                                continue;
                            }

                            $data[$joinColumn->getColumnName()] = $fieldValue;

                            break;
                        }

                        continue;
                    }

                    $targetAssociation = $targetClassMetadata->getProperty($fieldName);

                    foreach ($association->getJoinColumns() as $assocJoinColumn) {
                        foreach ($targetAssociation->getJoinColumns() as $targetAssocJoinColumn) {
                            if ($assocJoinColumn->getReferencedColumnName() !== $targetAssocJoinColumn->getColumnName()) {
                                continue;
                            }

                            $data[$assocJoinColumn->getColumnName()] = $fieldValue;
                        }
                    }
                }

                continue;
            }

            if (! $association->isPrimaryKey()) {
                $targetClass = StaticClassNameConverter::getClass($data[$name]);
                $targetId    = $this->uow->getEntityIdentifier($data[$name]);
                $data[$name] = new AssociationCacheEntry($targetClass, $targetId);

                continue;
            }

            // handle association identifier
            $targetId = $this->em->getIdentifierFlattener()->flattenIdentifier(
                $targetClassMetadata,
                $targetPersister->getIdentifier($data[$name])
            );

            $data[$name] = new AssociationCacheEntry($targetEntity, $targetId);
        }

        return new EntityCacheEntry($metadata->getClassName(), $data);
    }

    /**
     * {@inheritdoc}
     */
    public function loadCacheEntry(
        ClassMetadata $metadata,
        EntityCacheKey $key,
        EntityCacheEntry $entry,
        $entity = null
    ) {
        $data  = $entry->data;
        $hints = self::$hints;

        if ($entity !== null) {
            $hints[Query::HINT_REFRESH]        = true;
            $hints[Query::HINT_REFRESH_ENTITY] = $entity;
        }

        foreach ($metadata->getDeclaredPropertiesIterator() as $name => $association) {
            if ($association instanceof FieldMetadata || ! isset($data[$name]) || ! $association->getCache()) {
                continue;
            }

            $assocClass  = $data[$name]->class;
            $assocId     = $data[$name]->identifier;
            $isEagerLoad = (
                $association->getFetchMode() === FetchMode::EAGER ||
                ($association instanceof OneToOneAssociationMetadata && ! $association->isOwningSide())
            );

            if (! $isEagerLoad) {
                $data[$name] = $this->em->getReference($assocClass, $assocId);

                continue;
            }

            $targetEntity   = $association->getTargetEntity();
            $assocMetadata  = $this->em->getClassMetadata($targetEntity);
            $assocKey       = new EntityCacheKey($assocMetadata->getRootClassName(), $assocId);
            $assocPersister = $this->uow->getEntityPersister($targetEntity);
            $assocRegion    = $assocPersister->getCacheRegion();
            $assocEntry     = $assocRegion->get($assocKey);

            if ($assocEntry === null) {
                return null;
            }

            $data[$name] = $this->uow->createEntity(
                $assocEntry->class,
                $assocEntry->resolveAssociationEntries($this->em),
                $hints
            );
        }

        if ($entity !== null) {
            $this->uow->registerManaged($entity, $key->identifier, $data);
        }

        $result = $this->uow->createEntity($entry->class, $data, $hints);

        $this->uow->hydrationComplete();

        return $result;
    }
}

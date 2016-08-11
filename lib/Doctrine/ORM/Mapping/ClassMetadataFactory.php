<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Mapping;

use Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\DBAL\Platforms;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\LoadClassMetadataEventArgs;
use Doctrine\ORM\Event\OnClassMetadataNotFoundEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Sequencing;
use Doctrine\ORM\ORMException;
use ReflectionException;

/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping information of a class which describes how a class should be mapped
 * to a relational database.
 *
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class ClassMetadataFactory extends AbstractClassMetadataFactory
{
    /**
     * @var EntityManagerInterface|null
     */
    private $em;

    /**
     * @var \Doctrine\DBAL\Platforms\AbstractPlatform
     */
    private $targetPlatform;

    /**
     * @var \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
     */
    private $driver;

    /**
     * @var \Doctrine\Common\EventManager
     */
    private $evm;

    /**
     * @var array
     */
    private $embeddablesActiveNesting = [];

    /**
     * {@inheritDoc}
     */
    protected function loadMetadata($name)
    {
        $loaded = parent::loadMetadata($name);

        array_map([$this, 'resolveDiscriminatorValue'], array_map([$this, 'getMetadataFor'], $loaded));

        return $loaded;
    }

    /**
     * @param EntityManagerInterface $em
     */
    public function setEntityManager(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * {@inheritDoc}
     */
    protected function initialize()
    {
        $this->driver = $this->em->getConfiguration()->getMetadataDriverImpl();
        $this->evm = $this->em->getEventManager();
        $this->initialized = true;
    }

    /**
     * {@inheritDoc}
     */
    protected function onNotFoundMetadata($className)
    {
        if ( ! $this->evm->hasListeners(Events::onClassMetadataNotFound)) {
            return;
        }

        $eventArgs = new OnClassMetadataNotFoundEventArgs($className, $this->em);

        $this->evm->dispatchEvent(Events::onClassMetadataNotFound, $eventArgs);

        return $eventArgs->getFoundMetadata();
    }

    /**
     * {@inheritDoc}
     */
    protected function doLoadMetadata($class, $parent, $rootEntityFound, array $nonSuperclassParents)
    {
        /* @var $class ClassMetadata */
        /* @var $parent ClassMetadata */
        if ($parent) {
            if ($parent->isInheritanceTypeSingleTable()) {
                $class->setPrimaryTable($parent->table);
            }

            $this->addInheritedFields($class, $parent);
            $this->addInheritedRelations($class, $parent);
            $this->addInheritedEmbeddedClasses($class, $parent);

            $class->setInheritanceType($parent->inheritanceType);
            $class->setIdGeneratorType($parent->generatorType);
            $class->setIdentifier($parent->identifier);

            if ($parent->versionProperty) {
                $class->setVersionProperty($parent->versionProperty);
            }

            if ($parent->discriminatorColumn) {
                $class->setDiscriminatorColumn($parent->discriminatorColumn);
                $class->setDiscriminatorMap($parent->discriminatorMap);
            }

            $class->setLifecycleCallbacks($parent->lifecycleCallbacks);
            $class->setChangeTrackingPolicy($parent->changeTrackingPolicy);

            if ( ! empty($parent->customGeneratorDefinition) && empty($class->customGeneratorDefinition)) {
                $class->setCustomGeneratorDefinition($parent->customGeneratorDefinition);
            }

            if ($parent->isMappedSuperclass && empty($class->customRepositoryClassName)) {
                $class->setCustomRepositoryClass($parent->customRepositoryClassName);
            }
        }

        // Invoke driver
        try {
            $this->driver->loadMetadataForClass($class->getName(), $class);
        } catch (ReflectionException $e) {
            throw MappingException::reflectionFailure($class->getName(), $e);
        }

        // If this class has a parent the id generator strategy is inherited.
        // However this is only true if the hierarchy of parents contains the root entity,
        // if it consists of mapped superclasses these don't necessarily include the id field.
        if ($parent && $rootEntityFound) {
            $this->inheritIdGeneratorMapping($class, $parent);
        } else {
            $this->completeIdGeneratorMapping($class);
        }

        /*if ( ! $class->isMappedSuperclass) {
            foreach ($class->embeddedClasses as $property => $embeddableClass) {
                if (isset($embeddableClass['inherited'])) {
                    continue;
                }

                if ( ! (isset($embeddableClass['class']) && $embeddableClass['class'])) {
                    throw MappingException::missingEmbeddedClass($property);
                }

                if (isset($this->embeddablesActiveNesting[$embeddableClass['class']])) {
                    throw MappingException::infiniteEmbeddableNesting($class->name, $property);
                }

                $this->embeddablesActiveNesting[$class->name] = true;

                $embeddableMetadata = $this->getMetadataFor($embeddableClass['class']);

                if ($embeddableMetadata->isEmbeddedClass) {
                    $this->addNestedEmbeddedClasses($embeddableMetadata, $class, $property);
                }

                $identifier = $embeddableMetadata->getIdentifier();

                if (! empty($identifier)) {
                    $this->inheritIdGeneratorMapping($class, $embeddableMetadata);
                }

                $class->inlineEmbeddable($property, $embeddableMetadata);

                unset($this->embeddablesActiveNesting[$class->name]);
            }
        }*/

        if ($parent) {
            if ($parent->isInheritanceTypeSingleTable()) {
                $class->setPrimaryTable($parent->table);
            }

            $this->addInheritedIndexes($class, $parent);

            if ($parent->cache) {
                $class->cache = $parent->cache;
            }

            if ($parent->containsForeignIdentifier) {
                $class->containsForeignIdentifier = true;
            }

            if ( ! empty($parent->namedQueries)) {
                $this->addInheritedNamedQueries($class, $parent);
            }

            if ( ! empty($parent->namedNativeQueries)) {
                $this->addInheritedNamedNativeQueries($class, $parent);
            }

            if ( ! empty($parent->sqlResultSetMappings)) {
                $this->addInheritedSqlResultSetMappings($class, $parent);
            }

            if ( ! empty($parent->entityListeners) && empty($class->entityListeners)) {
                $class->entityListeners = $parent->entityListeners;
            }
        }

        $class->setParentClasses($nonSuperclassParents);

        if ($class->isRootEntity() && ! $class->isInheritanceTypeNone() && ! $class->discriminatorMap) {
            $this->addDefaultDiscriminatorMap($class);
        }

        $this->completeRuntimeMetadata($class, $parent);

        if ($this->evm->hasListeners(Events::loadClassMetadata)) {
            $eventArgs = new LoadClassMetadataEventArgs($class, $this->em);

            $this->evm->dispatchEvent(Events::loadClassMetadata, $eventArgs);
        }

        $this->validateRuntimeMetadata($class, $parent);
    }

    /**
     * @param ClassMetadata               $class
     * @param ClassMetadataInterface|null $parent
     *
     * @return void
     */
    protected function completeRuntimeMetadata($class, $parent)
    {
        if ( ! $parent) {
            return;
        }

        if ( ! $parent->isMappedSuperclass) {
            return;
        }

        if ($class->isMappedSuperclass) {
            return;
        }

        $tableName = $class->getTableName();

        // Resolve column table names
        foreach ($class->getProperties() as $property) {
            $property->setTableName($property->getTableName() ?? $tableName);
        }

        // Resolve association join column table names
        foreach ($class->associationMappings as $mapping) {
            if (isset($mapping['joinColumns'])) {
                foreach ($mapping['joinColumns'] as $joinColumn) {
                    $joinColumn->setTableName($joinColumn->getTableName() ?? $tableName);
                }
            }
        }

        // Resolve embedded table names
        /*foreach ($class->embeddedClasses as &$mapping) {
            if ( ! isset($mapping['tableName'])) {
                $mapping['tableName'] = $mapping['tableName'] ?? $tableName;
            }
        }*/
    }

    /**
     * Validate runtime metadata is correctly defined.
     *
     * @param ClassMetadata               $class
     * @param ClassMetadataInterface|null $parent
     *
     * @return void
     *
     * @throws MappingException
     */
    protected function validateRuntimeMetadata($class, $parent)
    {
        if ( ! $class->reflClass ) {
            // only validate if there is a reflection class instance
            return;
        }

        $class->validateIdentifier();
        $class->validateAssociations();
        $class->validateLifecycleCallbacks($this->getReflectionService());

        // verify inheritance
        if ( ! $class->isMappedSuperclass && ! $class->isInheritanceTypeNone()) {
            if ( ! $parent) {
                if (count($class->discriminatorMap) === 0) {
                    throw MappingException::missingDiscriminatorMap($class->name);
                }

                if ( ! $class->discriminatorColumn) {
                    throw MappingException::missingDiscriminatorColumn($class->name);
                }
            }
        } else if ($class->isMappedSuperclass && $class->name === $class->rootEntityName && (count($class->discriminatorMap) || $class->discriminatorColumn)) {
            // second condition is necessary for mapped superclasses in the middle of an inheritance hierarchy
            throw MappingException::noInheritanceOnMappedSuperClass($class->name);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function newClassMetadataInstance($className)
    {
        return new ClassMetadata($className, $this->em->getConfiguration()->getNamingStrategy());
    }

    /**
     * Populates the discriminator value of the given metadata (if not set) by iterating over discriminator
     * map classes and looking for a fitting one.
     *
     * @param ClassMetadata $metadata
     *
     * @return void
     *
     * @throws MappingException
     */
    private function resolveDiscriminatorValue(ClassMetadata $metadata)
    {
        if ($metadata->discriminatorValue || ! $metadata->discriminatorMap ||
            $metadata->isMappedSuperclass || ! $metadata->reflClass || $metadata->reflClass->isAbstract()) {
            return;
        }

        // minor optimization: avoid loading related metadata when not needed
        foreach ($metadata->discriminatorMap as $discriminatorValue => $discriminatorClass) {
            if ($discriminatorClass === $metadata->name) {
                $metadata->discriminatorValue = $discriminatorValue;

                return;
            }
        }

        // iterate over discriminator mappings and resolve actual referenced classes according to existing metadata
        foreach ($metadata->discriminatorMap as $discriminatorValue => $discriminatorClass) {
            if ($metadata->name === $this->getMetadataFor($discriminatorClass)->getName()) {
                $metadata->discriminatorValue = $discriminatorValue;

                return;
            }
        }

        throw MappingException::mappedClassNotPartOfDiscriminatorMap($metadata->name, $metadata->rootEntityName);
    }

    /**
     * Adds a default discriminator map if no one is given
     *
     * If an entity is of any inheritance type and does not contain a
     * discriminator map, then the map is generated automatically. This process
     * is expensive computation wise.
     *
     * The automatically generated discriminator map contains the lowercase short name of
     * each class as key.
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $class
     *
     * @throws MappingException
     */
    private function addDefaultDiscriminatorMap(ClassMetadata $class)
    {
        $allClasses = $this->driver->getAllClassNames();
        $fqcn       = $class->getName();
        $map        = [$this->getShortName($class->name) => $fqcn];
        $duplicates = [];

        foreach ($allClasses as $subClassCandidate) {
            if (is_subclass_of($subClassCandidate, $fqcn)) {
                $shortName = $this->getShortName($subClassCandidate);

                if (isset($map[$shortName])) {
                    $duplicates[] = $shortName;
                }

                $map[$shortName] = $subClassCandidate;
            }
        }

        if ($duplicates) {
            throw MappingException::duplicateDiscriminatorEntry($class->name, $duplicates, $map);
        }

        $class->setDiscriminatorMap($map);
    }

    /**
     * Gets the lower-case short name of a class.
     *
     * @param string $className
     *
     * @return string
     */
    private function getShortName($className)
    {
        if (strpos($className, "\\") === false) {
            return strtolower($className);
        }

        $parts = explode("\\", $className);

        return strtolower(end($parts));
    }

    /**
     * Adds inherited fields to the subclass mapping.
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $subClass
     * @param \Doctrine\ORM\Mapping\ClassMetadata $parentClass
     *
     * @return void
     */
    private function addInheritedFields(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        foreach ($parentClass->getProperties() as $property) {
            $subClass->addInheritedProperty($property);
        }

        foreach ($parentClass->reflFields as $name => $field) {
            $subClass->reflFields[$name] = $field;
        }
    }

    /**
     * Adds inherited association mappings to the subclass mapping.
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $subClass
     * @param \Doctrine\ORM\Mapping\ClassMetadata $parentClass
     *
     * @return void
     *
     * @throws MappingException
     */
    private function addInheritedRelations(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        $tableName = $subClass->getTableName();

        foreach ($parentClass->associationMappings as $field => $mapping) {
            if ($parentClass->isMappedSuperclass) {
                if ($mapping['type'] & ClassMetadata::TO_MANY && !$mapping['isOwningSide']) {
                    throw MappingException::illegalToManyAssociationOnMappedSuperclass($parentClass->name, $field);
                }

                $mapping['sourceEntity'] = $subClass->name;
            }

            // Resolve which table owns the join columns in a to-one mapping
            if (isset($mapping['joinColumns'])) {
                foreach ($mapping['joinColumns'] as $joinColumn) {
                    if ( ! $joinColumn->getTableName()) {
                        $joinColumn->setTableName(! $parentClass->isMappedSuperclass ? $tableName : null);
                    }
                }
            }

            if ( ! isset($mapping['inherited']) && ! $parentClass->isMappedSuperclass) {
                $mapping['inherited'] = $parentClass->name;
            }

            $subClass->addInheritedAssociationMapping($mapping);
        }
    }

    /**
     * Adds inherited embedded mappings to the subclass mapping.
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $subClass
     * @param \Doctrine\ORM\Mapping\ClassMetadata $parentClass
     *
     * @return void
     */
    private function addInheritedEmbeddedClasses(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        /*foreach ($parentClass->embeddedClasses as $field => $embeddedClass) {
            if ( ! isset($embeddedClass['tableName'])) {
                $embeddedClass['tableName'] = ! $parentClass->isMappedSuperclass ? $parentClass->getTableName() : null;
            }

            if ( ! isset($embeddedClass['inherited']) && ! $parentClass->isMappedSuperclass) {
                $embeddedClass['inherited'] = $parentClass->name;
            }

            $subClass->embeddedClasses[$field] = $embeddedClass;
        }*/
    }

    /**
     * Adds nested embedded classes metadata to a parent class.
     *
     * @param ClassMetadata $subClass    Sub embedded class metadata to add nested embedded classes metadata from.
     * @param ClassMetadata $parentClass Parent class to add nested embedded classes metadata to.
     * @param string        $prefix      Embedded classes' prefix to use for nested embedded classes field names.
     */
    private function addNestedEmbeddedClasses(ClassMetadata $subClass, ClassMetadata $parentClass, $prefix)
    {
        /*foreach ($subClass->embeddedClasses as $property => $embeddableClass) {
            if (isset($embeddableClass['inherited'])) {
                continue;
            }

            $embeddableMetadata = $this->getMetadataFor($embeddableClass['class']);

            $parentClass->mapEmbedded(
                [
                    'fieldName' => $prefix . '.' . $property,
                    'class' => $embeddableMetadata->name,
                    'columnPrefix' => $embeddableClass['columnPrefix'],
                    'declaredField' => $embeddableClass['declaredField']
                            ? $prefix . '.' . $embeddableClass['declaredField']
                            : $prefix,
                    'originalField' => $embeddableClass['originalField'] ?: $property,
                ]
            );
        }*/
    }

    /**
     * Copy the table indices from the parent class superclass to the child class
     *
     * @param ClassMetadata $subClass
     * @param ClassMetadata $parentClass
     *
     * @return void
     */
    private function addInheritedIndexes(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        if ( ! $parentClass->isMappedSuperclass) {
            return;
        }

        foreach (['uniqueConstraints', 'indexes'] as $indexType) {
            if ( ! isset($parentClass->table[$indexType])) {
                continue;
            }

            foreach ($parentClass->table[$indexType] as $indexName => $index) {
                if (isset($subClass->table[$indexType][$indexName])) {
                    continue; // Let the inheriting table override indices
                }

                $subClass->table[$indexType][$indexName] = $index;
            }
        }
    }

    /**
     * Adds inherited named queries to the subclass mapping.
     *
     * @since 2.2
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $subClass
     * @param \Doctrine\ORM\Mapping\ClassMetadata $parentClass
     *
     * @return void
     */
    private function addInheritedNamedQueries(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        foreach ($parentClass->namedQueries as $name => $query) {
            if (isset($subClass->namedQueries[$name])) {
                continue;
            }

            $subClass->addNamedQuery(
                [
                    'name'  => $query['name'],
                    'query' => $query['query']
                ]
            );
        }
    }

    /**
     * Adds inherited named native queries to the subclass mapping.
     *
     * @since 2.3
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $subClass
     * @param \Doctrine\ORM\Mapping\ClassMetadata $parentClass
     *
     * @return void
     */
    private function addInheritedNamedNativeQueries(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        foreach ($parentClass->namedNativeQueries as $name => $query) {
            if (isset($subClass->namedNativeQueries[$name])) {
                continue;
            }

            $subClass->addNamedNativeQuery(
                [
                    'name'             => $query['name'],
                    'query'            => $query['query'],
                    'isSelfClass'      => $query['isSelfClass'],
                    'resultSetMapping' => $query['resultSetMapping'],
                    'resultClass'      => $query['isSelfClass'] ? $subClass->name : $query['resultClass'],
                ]
            );
        }
    }

    /**
     * Adds inherited sql result set mappings to the subclass mapping.
     *
     * @since 2.3
     *
     * @param \Doctrine\ORM\Mapping\ClassMetadata $subClass
     * @param \Doctrine\ORM\Mapping\ClassMetadata $parentClass
     *
     * @return void
     */
    private function addInheritedSqlResultSetMappings(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        foreach ($parentClass->sqlResultSetMappings as $name => $mapping) {
            if (isset ($subClass->sqlResultSetMappings[$name])) {
                continue;
            }

            $entities = [];

            foreach ($mapping['entities'] as $entity) {
                $entities[] = [
                    'fields'              => $entity['fields'],
                    'isSelfClass'         => $entity['isSelfClass'],
                    'discriminatorColumn' => $entity['discriminatorColumn'],
                    'entityClass'         => $entity['isSelfClass'] ? $subClass->name : $entity['entityClass'],
                ];
            }

            $subClass->addSqlResultSetMapping(
                [
                    'name'     => $mapping['name'],
                    'columns'  => $mapping['columns'],
                    'entities' => $entities,
                ]
            );
        }
    }

    /**
     * Completes the ID generator mapping. If "auto" is specified we choose the generator
     * most appropriate for the targeted database platform.
     *
     * @param ClassMetadata $class
     *
     * @return void
     *
     * @throws ORMException
     */
    private function completeIdGeneratorMapping(ClassMetadata $class)
    {
        $idGenType = $class->generatorType;
        $platform  = $this->getTargetPlatform();

        if ($idGenType === ClassMetadata::GENERATOR_TYPE_AUTO) {
            $idGenType = $platform->prefersSequences()
                ? ClassMetadata::GENERATOR_TYPE_SEQUENCE
                : ($platform->prefersIdentityColumns()
                    ? ClassMetadata::GENERATOR_TYPE_IDENTITY
                    : ClassMetadata::GENERATOR_TYPE_TABLE
                );

            $class->setIdGeneratorType($idGenType);
        }

        // Create & assign an appropriate ID generator instance
        switch ($class->generatorType) {
            case ClassMetadata::GENERATOR_TYPE_IDENTITY:
                $sequenceName = null;
                $fieldName    = $class->identifier ? $class->getSingleIdentifierFieldName() : null;

                // Platforms that do not have native IDENTITY support need a sequence to emulate this behaviour.
                if ($platform->usesSequenceEmulatedIdentityColumns()) {
                    $columnName     = $class->getSingleIdentifierColumnName();
                    $sequencePrefix = $class->getSequencePrefix($platform);
                    $idSequenceName = $platform->getIdentitySequenceName($sequencePrefix, $columnName);
                    $sequenceName   = $platform->quoteIdentifier($platform->fixSchemaElementName($idSequenceName));
                }

                $generator = ($fieldName && $class->getProperty($fieldName)->getTypeName() === 'bigint')
                    ? new Sequencing\BigIntegerIdentityGenerator($sequenceName)
                    : new Sequencing\IdentityGenerator($sequenceName)
                ;

                $class->setIdGenerator($generator);

                break;

            case ClassMetadata::GENERATOR_TYPE_SEQUENCE:
                // If there is no sequence definition yet, create a default definition
                $definition = $class->sequenceGeneratorDefinition;

                if ( ! $definition) {
                    $sequenceName   = $class->getSequenceName($platform);

                    $definition = [
                        'sequenceName'   => $platform->fixSchemaElementName($sequenceName),
                        'allocationSize' => 1,
                        'initialValue'   => 1,
                    ];

                    $class->setSequenceGeneratorDefinition($definition);
                }

                $sequenceName      = $platform->quoteIdentifier($definition['sequenceName']);
                $sequenceGenerator = new Sequencing\SequenceGenerator($sequenceName, $definition['allocationSize']);

                $class->setIdGenerator($sequenceGenerator);
                break;

            case ClassMetadata::GENERATOR_TYPE_NONE:
                $class->setIdGenerator(new Sequencing\AssignedGenerator());
                break;

            case ClassMetadata::GENERATOR_TYPE_UUID:
                $class->setIdGenerator(new Sequencing\UuidGenerator());
                break;

            case ClassMetadata::GENERATOR_TYPE_TABLE:
                throw new ORMException("TableGenerator not yet implemented.");
                break;

            case ClassMetadata::GENERATOR_TYPE_CUSTOM:
                $definition = $class->customGeneratorDefinition;

                if ( ! class_exists($definition['class'])) {
                    throw new ORMException("Can't instantiate custom generator : " . $definition['class']);
                }

                $class->setIdGenerator(new $definition['class']);
                break;

            default:
                throw new ORMException("Unknown generator type: " . $class->generatorType);
        }
    }

    /**
     * Inherits the ID generator mapping from a parent class.
     *
     * @param ClassMetadata $class
     * @param ClassMetadata $parent
     */
    private function inheritIdGeneratorMapping(ClassMetadata $class, ClassMetadata $parent)
    {
        if ($parent->isIdGeneratorSequence()) {
            $class->setSequenceGeneratorDefinition($parent->sequenceGeneratorDefinition);
        } elseif ($parent->isIdGeneratorTable()) {
            $class->tableGeneratorDefinition = $parent->tableGeneratorDefinition;
        }

        if ($parent->generatorType) {
            $class->setIdGeneratorType($parent->generatorType);
        }

        if ($parent->idGenerator) {
            $class->setIdGenerator($parent->idGenerator);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function wakeupReflection(ClassMetadataInterface $class, ReflectionService $reflService)
    {
        /* @var $class ClassMetadata */
        $class->wakeupReflection($reflService);
    }

    /**
     * {@inheritDoc}
     */
    protected function initializeReflection(ClassMetadataInterface $class, ReflectionService $reflService)
    {
        /* @var $class ClassMetadata */
        $class->initializeReflection($reflService);
    }

    /**
     * {@inheritDoc}
     */
    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName)
    {
        return $this->em->getConfiguration()->getEntityNamespace($namespaceAlias) . '\\' . $simpleClassName;
    }

    /**
     * {@inheritDoc}
     */
    protected function getDriver()
    {
        return $this->driver;
    }

    /**
     * {@inheritDoc}
     */
    protected function isEntity(ClassMetadataInterface $class)
    {
        return isset($class->isMappedSuperclass) && $class->isMappedSuperclass === false;
    }

    /**
     * @return Platforms\AbstractPlatform
     */
    private function getTargetPlatform()
    {
        if (!$this->targetPlatform) {
            $this->targetPlatform = $this->em->getConnection()->getDatabasePlatform();
        }

        return $this->targetPlatform;
    }
}

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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Mapping;

use ReflectionException,
    Doctrine\ORM\ORMException,
    Doctrine\ORM\EntityManager,
    Doctrine\DBAL\Platforms,
    Doctrine\ORM\Events;

/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping informations of a class which describes how a class should be mapped
 * to a relational database.
 *
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class ClassMetadataFactory
{
    private $_em;
    /** The targeted database platform. */
    private $_targetPlatform;
    /** The used metadata driver. */
    private $_driver;
    /** The event manager instance */
    private $_evm;
    /** The used cache driver. */
    private $_cacheDriver;
    private $_loadedMetadata = array();
    private $_initialized = false;
    
    /**
     * Creates a new factory instance that uses the given metadata driver implementation.
     *
     * @param $driver  The metadata driver to use.
     */
    public function __construct(EntityManager $em)
    {
        $this->_em = $em;
    }

    /**
     * Sets the cache driver used by the factory to cache ClassMetadata instances.
     *
     * @param Doctrine\Common\Cache\Cache $cacheDriver
     */
    public function setCacheDriver($cacheDriver)
    {
        $this->_cacheDriver = $cacheDriver;
    }

    /**
     * Gets the cache driver used by the factory to cache ClassMetadata instances.
     *
     * @return Doctrine\Common\Cache\Cache
     */
    public function getCacheDriver()
    {
        return $this->_cacheDriver;
    }
    
    public function getLoadedMetadata()
    {
        return $this->_loadedMetadata;
    }
    
    /**
     * Forces the factory to load the metadata of all classes known to the underlying
     * mapping driver.
     * 
     * @return array The ClassMetadata instances of all mapped classes.
     */
    public function getAllMetadata()
    {
        if ( ! $this->_initialized) {
            $this->_initialize();
        }

        $metadata = array();
        foreach ($this->_driver->getAllClassNames() as $className) {
            $metadata[] = $this->getMetadataFor($className);
        }

        return $metadata;
    }

    /**
     * Lazy initialization of this stuff, especially the metadata driver,
     * since these are not needed at all when a metadata cache is active.
     */
    private function _initialize()
    {
        $this->_driver = $this->_em->getConfiguration()->getMetadataDriverImpl();
        $this->_targetPlatform = $this->_em->getConnection()->getDatabasePlatform();
        $this->_evm = $this->_em->getEventManager();
        $this->_initialized = true;
    }

    /**
     * Gets the class metadata descriptor for a class.
     *
     * @param string $className The name of the class.
     * @return Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getMetadataFor($className)
    {
        if ( ! isset($this->_loadedMetadata[$className])) {
            $realClassName = $className;

            // Check for namespace alias
            if (strpos($className, ':') !== false) {
                list($namespaceAlias, $simpleClassName) = explode(':', $className);
                $realClassName = $this->_em->getConfiguration()->getEntityNamespace($namespaceAlias) . '\\' . $simpleClassName;

                if (isset($this->_loadedMetadata[$realClassName])) {
                    // We do not have the alias name in the map, include it
                    $this->_loadedMetadata[$className] = $this->_loadedMetadata[$realClassName];

                    return $this->_loadedMetadata[$realClassName];
                }
            }

            if ($this->_cacheDriver) {
                if (($cached = $this->_cacheDriver->fetch("$realClassName\$CLASSMETADATA")) !== false) {
                    $this->_loadedMetadata[$realClassName] = $cached;
                } else {
                    foreach ($this->_loadMetadata($realClassName) as $loadedClassName) {
                        $this->_cacheDriver->save(
                            "$loadedClassName\$CLASSMETADATA", $this->_loadedMetadata[$loadedClassName], null
                        );
                    }
                }
            } else {
                $this->_loadMetadata($realClassName);
            }

            if ($className != $realClassName) {
                // We do not have the alias name in the map, include it
                $this->_loadedMetadata[$className] = $this->_loadedMetadata[$realClassName];
            }
        }

        return $this->_loadedMetadata[$className];
    }

    /**
     * Checks whether the factory has the metadata for a class loaded already.
     * 
     * @param string $className
     * @return boolean TRUE if the metadata of the class in question is already loaded, FALSE otherwise.
     */
    public function hasMetadataFor($className)
    {
        return isset($this->_loadedMetadata[$className]);
    }

    /**
     * Sets the metadata descriptor for a specific class.
     * 
     * NOTE: This is only useful in very special cases, like when generating proxy classes.
     *
     * @param string $className
     * @param ClassMetadata $class
     */
    public function setMetadataFor($className, $class)
    {
        $this->_loadedMetadata[$className] = $class;
    }

    /**
     * Get array of parent classes for the given entity class
     *
     * @param string $name
     * @return array $parentClasses
     */
    protected function _getParentClasses($name)
    {
        // Collect parent classes, ignoring transient (not-mapped) classes.
        $parentClasses = array();
        foreach (array_reverse(class_parents($name)) as $parentClass) {
            if ( ! $this->_driver->isTransient($parentClass)) {
                $parentClasses[] = $parentClass;
            }
        }
        return $parentClasses;
    }

    /**
     * Loads the metadata of the class in question and all it's ancestors whose metadata
     * is still not loaded.
     *
     * @param string $name The name of the class for which the metadata should get loaded.
     * @param array  $tables The metadata collection to which the loaded metadata is added.
     */
    protected function _loadMetadata($name)
    {
        if ( ! $this->_initialized) {
            $this->_initialize();
        }

        $loaded = array();

        $parentClasses = $this->_getParentClasses($name);
        $parentClasses[] = $name;

        // Move down the hierarchy of parent classes, starting from the topmost class
        $parent = null;
        $visited = array();
        foreach ($parentClasses as $className) {
            if (isset($this->_loadedMetadata[$className])) {
                $parent = $this->_loadedMetadata[$className];
                if ( ! $parent->isMappedSuperclass) {
                    array_unshift($visited, $className);
                }
                continue;
            }

            $class = $this->_newClassMetadataInstance($className);

            if ($parent) {
                $class->setInheritanceType($parent->inheritanceType);
                $class->setDiscriminatorColumn($parent->discriminatorColumn);
                $class->setIdGeneratorType($parent->generatorType);
                $this->_addInheritedFields($class, $parent);
                $this->_addInheritedRelations($class, $parent);
                $class->setIdentifier($parent->identifier);
                $class->setVersioned($parent->isVersioned);
                $class->setVersionField($parent->versionField);
                $class->setDiscriminatorMap($parent->discriminatorMap);
                $class->setLifecycleCallbacks($parent->lifecycleCallbacks);
            }

            // Invoke driver
            try {
                $this->_driver->loadMetadataForClass($className, $class);
            } catch(ReflectionException $e) { 
                throw MappingException::reflectionFailure($className, $e);
            }

            // Verify & complete identifier mapping
            if ( ! $class->identifier && ! $class->isMappedSuperclass) {
                throw MappingException::identifierRequired($className);
            }
            if ($parent && ! $parent->isMappedSuperclass) {
                if ($parent->isIdGeneratorSequence()) {
                    $class->setSequenceGeneratorDefinition($parent->sequenceGeneratorDefinition);
                } else if ($parent->isIdGeneratorTable()) {
                    $class->getTableGeneratorDefinition($parent->tableGeneratorDefinition);
                }
                if ($parent->generatorType) {
                    $class->setIdGeneratorType($parent->generatorType);
                }
                if ($parent->idGenerator) {
                    $class->setIdGenerator($parent->idGenerator);
                }
            } else {
                $this->_completeIdGeneratorMapping($class);
            }

            if ($parent && $parent->isInheritanceTypeSingleTable()) {
                $class->setPrimaryTable($parent->table);
            }

            $class->setParentClasses($visited);

            if ($this->_evm->hasListeners(Events::loadClassMetadata)) {
                $eventArgs = new \Doctrine\ORM\Event\LoadClassMetadataEventArgs($class);
                $this->_evm->dispatchEvent(Events::loadClassMetadata, $eventArgs);
            }

            $this->_loadedMetadata[$className] = $class;

            $parent = $class;

            if ( ! $class->isMappedSuperclass) {
                array_unshift($visited, $className);
            }

            $loaded[] = $className;
        }

        return $loaded;
    }

    /**
     * Creates a new ClassMetadata instance for the given class name.
     *
     * @param string $className
     * @return Doctrine\ORM\Mapping\ClassMetadata
     */
    protected function _newClassMetadataInstance($className)
    {
        return new ClassMetadata($className);
    }
    
    /**
     * Adds inherited fields to the subclass mapping.
     *
     * @param Doctrine\ORM\Mapping\ClassMetadata $subClass
     * @param Doctrine\ORM\Mapping\ClassMetadata $parentClass
     */
    private function _addInheritedFields(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        foreach ($parentClass->fieldMappings as $fieldName => $mapping) {
            if ( ! isset($mapping['inherited']) && ! $parentClass->isMappedSuperclass) {
                $mapping['inherited'] = $parentClass->name;
            }
            if ( ! isset($mapping['declared'])) {
                $mapping['declared'] = $parentClass->name;
            }
            $subClass->addInheritedFieldMapping($mapping);
        }
        foreach ($parentClass->reflFields as $name => $field) {
            $subClass->reflFields[$name] = $field;
        }
    }

    /**
     * Adds inherited association mappings to the subclass mapping.
     *
     * @param Doctrine\ORM\Mapping\ClassMetadata $subClass
     * @param Doctrine\ORM\Mapping\ClassMetadata $parentClass
     */
    private function _addInheritedRelations(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        foreach ($parentClass->associationMappings as $field => $mapping) {
            $subclassMapping = clone $mapping;
            if ( ! isset($mapping->inherited) && ! $parentClass->isMappedSuperclass) {
                $subclassMapping->inherited = $parentClass->name;
            }
            if ( ! isset($mapping->declared)) {
                $subclassMapping->declared = $parentClass->name;
            }
            $subClass->addInheritedAssociationMapping($subclassMapping);
        }
    }

    /**
     * Completes the ID generator mapping. If "auto" is specified we choose the generator
     * most appropriate for the targeted database platform.
     *
     * @param Doctrine\ORM\Mapping\ClassMetadata $class
     */
    private function _completeIdGeneratorMapping(ClassMetadataInfo $class)
    {
        $idGenType = $class->generatorType;
        if ($idGenType == ClassMetadata::GENERATOR_TYPE_AUTO) {
            if ($this->_targetPlatform->prefersSequences()) {
                $class->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_SEQUENCE);
            } else if ($this->_targetPlatform->prefersIdentityColumns()) {
                $class->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_IDENTITY);
            } else {
                $class->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_TABLE);
            }
        }

        // Create & assign an appropriate ID generator instance
        switch ($class->generatorType) {
            case ClassMetadata::GENERATOR_TYPE_IDENTITY:
                // For PostgreSQL IDENTITY (SERIAL) we need a sequence name. It defaults to
                // <table>_<column>_seq in PostgreSQL for SERIAL columns.
                // Not pretty but necessary and the simplest solution that currently works.
                $seqName = $this->_targetPlatform instanceof Platforms\PostgreSQLPlatform ?
                        $class->table['name'] . '_' . $class->columnNames[$class->identifier[0]] . '_seq' :
                        null;
                $class->setIdGenerator(new \Doctrine\ORM\Id\IdentityGenerator($seqName));
                break;
            case ClassMetadata::GENERATOR_TYPE_SEQUENCE:
                // If there is no sequence definition yet, create a default definition
                $definition = $class->sequenceGeneratorDefinition;
                if ( ! $definition) {
                    $sequenceName = $class->getTableName() . '_' . $class->getSingleIdentifierColumnName() . '_seq';
                    $definition['sequenceName'] = $this->_targetPlatform->fixSchemaElementName($sequenceName);
                    $definition['allocationSize'] = 10;
                    $definition['initialValue'] = 1;
                    $class->setSequenceGeneratorDefinition($definition);
                }
                $sequenceGenerator = new \Doctrine\ORM\Id\SequenceGenerator(
                    $definition['sequenceName'],
                    $definition['allocationSize']
                );
                $class->setIdGenerator($sequenceGenerator);
                break;
            case ClassMetadata::GENERATOR_TYPE_NONE:
                $class->setIdGenerator(new \Doctrine\ORM\Id\AssignedGenerator());
                break;
            case ClassMetadata::GENERATOR_TYPE_TABLE:
                throw new ORMException("TableGenerator not yet implemented.");
                break;
            default:
                throw new ORMException("Unknown generator type: " . $class->generatorType);
        }
    }
}

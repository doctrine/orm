<?php
/*
 *  $Id$
 *
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

use Doctrine\Common\DoctrineException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Events;

/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping informations of a class which describes how a class should be mapped
 * to a relational database.
 *
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.doctrine-project.org
 * @since       2.0
 * @todo Support for mapped superclasses (@MappedSuperclass)
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
    
    /**
     * Creates a new factory instance that uses the given metadata driver implementation.
     *
     * @param $driver  The metadata driver to use.
     */
    public function __construct(\Doctrine\ORM\EntityManager $em)
    {
        $this->_em = $em;
        $this->_driver = $em->getConfiguration()->getMetadataDriverImpl();
        $this->_targetPlatform = $em->getConnection()->getDatabasePlatform();
        $this->_evm = $em->getEventManager();
    }

    /**
     * Sets the cache driver used by the factory to cache ClassMetadata instances
     * and invokes the preload() method of the metadata driver to prepopulate the cache.
     *
     * @param Doctrine\Common\Cache\Cache $cacheDriver
     */
    public function setCacheDriver($cacheDriver)
    {
        $this->_cacheDriver = $cacheDriver;
        foreach ($this->_driver->preload() as $className) {
            $cacheKey = "$className\$CLASSMETADATA";
            $this->_cacheDriver->save($cacheKey, $this->getMetadataFor($className), null);
        }
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

    /**
     * Returns the metadata object for a class.
     *
     * @param string $className The name of the class.
     * @return Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getMetadataFor($className)
    {
        if ( ! isset($this->_loadedMetadata[$className])) {
            $cacheKey = "$className\$CLASSMETADATA";
            if ($this->_cacheDriver) {
                if ($this->_cacheDriver->contains($cacheKey)) {
                    $this->_loadedMetadata[$className] = $this->_cacheDriver->fetch($cacheKey);
                } else {
                    $this->_loadMetadata($className);
                    $this->_cacheDriver->save($cacheKey, $this->_loadedMetadata[$className], null);
                }
            } else {
                $this->_loadMetadata($className);
            }
        }
        return $this->_loadedMetadata[$className];
    }
    
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
     * Loads the metadata of the class in question and all it's ancestors whose metadata
     * is still not loaded.
     *
     * @param string $name The name of the class for which the metadata should get loaded.
     * @param array  $tables The metadata collection to which the loaded metadata is added.
     */
    protected function _loadMetadata($name)
    {
        // Collect parent classes, ignoring transient (not-mapped) classes.
        $parentClass = $name;
        $parentClasses = array();
        while ($parentClass = get_parent_class($parentClass)) {
            if ( ! $this->_driver->isTransient($parentClass)) {
                $parentClasses[] = $parentClass;
            }
        }
        $parentClasses = array_reverse($parentClasses);
        $parentClasses[] = $name;
        
        // Move down the hierarchy of parent classes, starting from the topmost class
        $parent = null;
        $visited = array();
        foreach ($parentClasses as $className) {
            if (isset($this->_loadedMetadata[$className])) {
                $parent = $this->_loadedMetadata[$className];
                array_unshift($visited, $className);
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
            }
            
            // Invoke driver
            $this->_driver->loadMetadataForClass($className, $class);

            // Verify & complete identifier mapping
            if ( ! $class->identifier) {
                throw MappingException::identifierRequired($className);
            }
            if ($parent) {
                if ($parent->isIdGeneratorSequence()) {
                    $class->setSequenceGeneratorDefinition($parent->getSequenceGeneratorDefinition());
                } else if ($parent->isIdGeneratorTable()) {
                    $class->getTableGeneratorDefinition($parent->getTableGeneratorDefinition());
                }
                $class->setIdGeneratorType($parent->generatorType);
                $class->setidGenerator($parent->getIdGenerator());
            } else {
                $this->_completeIdGeneratorMapping($class);
            }
            
            if ($parent && $parent->isInheritanceTypeSingleTable()) {
                $class->setTableName($parent->getTableName());
            }

            $class->setParentClasses($visited);

            if ($this->_evm->hasListeners(Events::loadClassMetadata)) {
                $eventArgs = new \Doctrine\ORM\Event\LoadClassMetadataEventArgs($class);
                $this->_evm->dispatchEvent(Events::loadClassMetadata, $eventArgs);
            }

            $this->_generateStaticSql($class);
            
            $this->_loadedMetadata[$className] = $class;
            
            $parent = $class;
            array_unshift($visited, $className);
        }
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
            if ( ! isset($mapping['inherited'])) {
                $mapping['inherited'] = $parentClass->name;
            }
            $subClass->addFieldMapping($mapping);
        }
        foreach ($parentClass->reflFields as $name => $field) {
            $subClass->reflFields[$name] = $field;
        }
    }
    
    /**
     * Adds inherited associations to the subclass mapping.
     *
     * @param Doctrine\ORM\Mapping\ClassMetadata $subClass
     * @param Doctrine\ORM\Mapping\ClassMetadata $parentClass
     */
    private function _addInheritedRelations(ClassMetadata $subClass, ClassMetadata $parentClass)
    {
        foreach ($parentClass->associationMappings as $mapping) {
            if (isset($parentClass->inheritedAssociationFields[$mapping->sourceFieldName])) {
                // parent class also inherited that one
                $subClass->addAssociationMapping($mapping, $parentClass->inheritedAssociationFields[$mapping->sourceFieldName]);
            } else {
                // parent class defined that one
                $subClass->addAssociationMapping($mapping, $parentClass->name);
            }
        }
    }

    /**
     * Generates any static SQL strings for a class and stores them in the descriptor.
     *
     * @param ClassMetadata $class
     */
    private function _generateStaticSql($class)
    {
        if ($versioned = $class->isVersioned) {
            $versionField = $class->versionField;
        }

        // Generate INSERT SQL
        $columns = $values = array();
        if ($class->inheritanceType == ClassMetadata::INHERITANCE_TYPE_JOINED) {
            // Generate INSERT SQL for inheritance type JOINED
            foreach ($class->reflFields as $name => $field) {
                if (isset($class->fieldMappings[$name]['inherited']) && ! isset($class->fieldMappings[$name]['id'])
                        || isset($class->inheritedAssociationFields[$name])
                        || ($versioned && $versionField == $name)) {
                    continue;
                }

                if (isset($class->associationMappings[$name])) {
                    $assoc = $class->associationMappings[$name];
                    if ($assoc->isOneToOne() && $assoc->isOwningSide) {
                        foreach ($assoc->targetToSourceKeyColumns as $sourceCol) {
                            $columns[] = $this->_targetPlatform->quoteIdentifier($sourceCol);
                            $values[] = '?';
                        }
                    }
                } else if ($class->name != $class->rootEntityName || ! $class->isIdGeneratorIdentity() || $class->identifier[0] != $name) {
                    $columns[] = $this->_targetPlatform->quoteIdentifier($class->columnNames[$name]);
                    $values[] = '?';
                }
            }
        } else {
            // Generate INSERT SQL for inheritance types NONE, SINGLE_TABLE, TABLE_PER_CLASS
            foreach ($class->reflFields as $name => $field) {
                if ($versioned && $versionField == $name) {
                    continue;
                }
                if (isset($class->associationMappings[$name])) {
                    $assoc = $class->associationMappings[$name];
                    if ($assoc->isOwningSide && $assoc->isOneToOne()) {
                        foreach ($assoc->targetToSourceKeyColumns as $sourceCol) {
                            $columns[] = $this->_targetPlatform->quoteIdentifier($sourceCol);
                            $values[] = '?';
                        }
                    }
                } else if ($class->generatorType != ClassMetadata::GENERATOR_TYPE_IDENTITY ||  $class->identifier[0] != $name) {
                    $columns[] = $this->_targetPlatform->quoteIdentifier($class->columnNames[$name]);
                    $values[] = '?';
                }
            }
        }
        
        // Add discriminator column to the INSERT SQL if necessary
        if ($class->isInheritanceTypeSingleTable() || $class->isInheritanceTypeJoined() && $class->name == $class->rootEntityName) {
            $columns[] = $class->discriminatorColumn['name'];
            $values[] = '?';
        }

        $class->insertSql = 'INSERT INTO ' .
                $this->_targetPlatform->quoteIdentifier($class->primaryTable['name'])
               . ' (' . implode(', ', $columns) . ') '
               . 'VALUES (' . implode(', ', $values) . ')';
    }

    /**
     * Completes the ID generator mapping. If "auto" is specified we choose the generator
     * most appropriate for the targeted database platform.
     *
     * @param Doctrine\ORM\Mapping\ClassMetadata $class
     */
    private function _completeIdGeneratorMapping(ClassMetadata $class)
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
                $class->setIdGenerator(new \Doctrine\ORM\Id\IdentityGenerator());
                break;
            case ClassMetadata::GENERATOR_TYPE_SEQUENCE:
                // If there is no sequence definition yet, create a default definition
                $definition = $class->getSequenceGeneratorDefinition();
                if ( ! $definition) {
                    $definition['sequenceName'] = $class->getTableName() . '_' . $class->getSingleIdentifierColumnName() . '_seq';
                    $definition['allocationSize'] = 20;
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
                $class->setIdGenerator(new \Doctrine\ORM\Id\Assigned());
                break;
            case ClassMetadata::GENERATOR_TYPE_TABLE:
                throw new DoctrineException("DoctrineTableGenerator not yet implemented.");
                break;
            default:
                throw new DoctrineException("Unexhaustive match.");
        }
    }
}

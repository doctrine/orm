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
 * <http://www.phpdoctrine.org>.
 */

namespace Doctrine\ORM\Mapping;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * The metadata factory is used to create ClassMetadata objects that contain all the
 * metadata mapping informations of a class which describes how a class should be mapped
 * to a relational database.
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision$
 * @link        www.doctrine-project.org
 * @since       2.0
 */
class ClassMetadataFactory
{
    /** The targeted database platform. */
    private $_targetPlatform;
    private $_driver;
    private $_cacheDriver;
    
    /**
     * Creates a new factory instance that uses the given metadata driver implementation.
     *
     * @param $driver  The metadata driver to use.
     */
    public function __construct($driver, AbstractPlatform $targetPlatform)
    {
        $this->_driver = $driver;
        $this->_targetPlatform = $targetPlatform;
    }

    /**
     * Sets the cache driver used by the factory to cache ClassMetadata instances.
     *
     * @param object $cacheDriver
     */
    public function setCacheDriver($cacheDriver)
    {
        $this->_cacheDriver = $cacheDriver;
    }

    /**
     * Gets the cache driver used by the factory to cache ClassMetadata instances.
     *
     * @return object
     */
    public function getCacheDriver()
    {
        return $this->_cacheDriver;
    }

    /**
     * Returns the metadata object for a class.
     *
     * @param string $className  The name of the class.
     * @return Doctrine\ORM\Mapping\ClassMetadata
     */
    public function getMetadataFor($className)
    {
        if ( ! isset($this->_loadedMetadata[$className])) {
            if ($this->_cacheDriver) {
                if ($this->_cacheDriver->contains("$className\$CLASSMETADATA")) {
                    $this->_loadedMetadata[$className] = $this->_cacheDriver->fetch("$className\$CLASSMETADATA");
                } else {
                    $this->_loadMetadata($className);
                    $this->_cacheDriver->save($this->_loadedMetadata[$className], "$className\$CLASSMETADATA", null);
                }
            } else {
                $this->_loadMetadata($className);
            }
        }
        return $this->_loadedMetadata[$className];
    }
    
    /**
     * Loads the metadata of the class in question and all it's ancestors whose metadata
     * is still not loaded.
     *
     * @param string $name   The name of the class for which the metadata should get loaded.
     * @param array  $tables The metadata collection to which the loaded metadata is added.
     */
    protected function _loadMetadata($name)
    {
        $parentClass = $name;
        $parentClasses = array();
        $loadedParentClass = false;
        while ($parentClass = get_parent_class($parentClass)) {
            if (isset($this->_loadedMetadata[$parentClass])) {
                $loadedParentClass = $parentClass;
                break;
            }
            $parentClasses[] = $parentClass;
        }
        $parentClasses = array_reverse($parentClasses);
        $parentClasses[] = $name;
        
        if ($loadedParentClass) {
            $class = $this->_loadedMetadata[$loadedParentClass];
        } else {
            $rootClassOfHierarchy = count($parentClasses) > 0 ? array_shift($parentClasses) : $name;
            $class = $this->_newClassMetadataInstance($rootClassOfHierarchy);
            $this->_loadClassMetadata($class, $rootClassOfHierarchy);
            $this->_loadedMetadata[$rootClassOfHierarchy] = $class;
        }
        
        if (count($parentClasses) == 0) {
            return $class;
        }
        
        // load metadata of subclasses
        // -> child1 -> child2 -> $name
        
        // Move down the hierarchy of parent classes, starting from the topmost class
        $parent = $class;
        foreach ($parentClasses as $subclassName) {
            $subClass = $this->_newClassMetadataInstance($subclassName);
            $subClass->setInheritanceType($parent->getInheritanceType());
            $subClass->setDiscriminatorMap($parent->getDiscriminatorMap());
            $subClass->setDiscriminatorColumn($parent->getDiscriminatorColumn());
            $subClass->setIdGeneratorType($parent->getIdGeneratorType());
            $this->_addInheritedFields($subClass, $parent);
            $this->_addInheritedRelations($subClass, $parent);
            $this->_loadClassMetadata($subClass, $subclassName);
            if ($parent->isInheritanceTypeSingleTable()) {
                $subClass->setTableName($parent->getTableName());
            }
            $this->_loadedMetadata[$subclassName] = $subClass;
            $parent = $subClass;
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
    private function _addInheritedFields($subClass, $parentClass)
    {
        foreach ($parentClass->getFieldMappings() as $fieldName => $mapping) {
            if ( ! isset($mapping['inherited'])) {
                $mapping['inherited'] = $parentClass->getClassName();
            }
            $subClass->mapField($mapping);
        }
    }
    
    /**
     * Adds inherited associations to the subclass mapping.
     *
     * @param Doctrine\ORM\Mapping\ClassMetadata $subClass
     * @param Doctrine\ORM\Mapping\ClassMetadata $parentClass
     */
    private function _addInheritedRelations($subClass, $parentClass)
    {
        foreach ($parentClass->getAssociationMappings() as $mapping) {
            $subClass->addAssociationMapping($mapping);
        }
    }
    
    /**
     * Loads the metadata of a specified class.
     *
     * @param Doctrine_ClassMetadata $class  The container for the metadata.
     * @param string $name  The name of the class for which the metadata will be loaded.
     */
    private function _loadClassMetadata(ClassMetadata $class, $name)
    {
        if ( ! class_exists($name) || empty($name)) {
            throw new DoctrineException("Couldn't find class " . $name . ".");
        }

        $names = array();
        $className = $name;
        // get parent classes
        //TODO: Skip Entity types MappedSuperclass/Transient
        do {
            if ($className == $name) {
                continue;
            }
            $names[] = $className;
        } while ($className = get_parent_class($className));

        // save parents
        $class->setParentClasses($names);

        // load user-specified mapping metadata through the driver
        $this->_driver->loadMetadataForClass($name, $class);

        // Complete Id generator mapping. If AUTO is specified we choose the generator
        // most appropriate for the target platform.
        if ($class->getIdGeneratorType() == \Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_AUTO) {
            if ($this->_targetPlatform->prefersSequences()) {
                $class->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_SEQUENCE);
            } else if ($this->_targetPlatform->prefersIdentityColumns()) {
                $class->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_IDENTITY);
            } else {
                $class->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_TABLE);
            }
        }
        
        return $class;
    }
}




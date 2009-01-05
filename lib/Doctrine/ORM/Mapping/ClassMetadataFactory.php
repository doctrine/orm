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

#namespace Doctrine\ORM\Mapping;

#use Doctrine\DBAL\Platforms\AbstractPlatform;

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
class Doctrine_ORM_Mapping_ClassMetadataFactory
{
    /** The targeted database platform. */
    private $_targetPlatform;
    private $_driver;
    
    /**
     * Constructor.
     * Creates a new factory instance that uses the given metadata driver implementation.
     *
     * @param $driver  The metadata driver to use.
     */
    public function __construct($driver, Doctrine_DBAL_Platforms_AbstractPlatform $targetPlatform)
    {
        $this->_driver = $driver;
        $this->_targetPlatform = $targetPlatform;
    }

    /**
     * Returns the metadata object for a class.
     *
     * @param string $className  The name of the class.
     * @return Doctrine_Metadata
     */
    public function getMetadataFor($className)
    {
        if ( ! isset($this->_loadedMetadata[$className])) {
            $this->_loadMetadata($className);
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
            $class = new Doctrine_ORM_Mapping_ClassMetadata($rootClassOfHierarchy);
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
            $subClass = new Doctrine_ORM_Mapping_ClassMetadata($subclassName);
            $subClass->setInheritanceType($parent->getInheritanceType(), $parent->getInheritanceOptions());
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
     * Adds inherited fields to the subclass mapping.
     *
     * @param Doctrine::ORM::Mapping::ClassMetadata $subClass
     * @param Doctrine::ORM::Mapping::ClassMetadata $parentClass
     */
    private function _addInheritedFields($subClass, $parentClass)
    {
        foreach ($parentClass->getFieldMappings() as $fieldName => $mapping) {
            if ( ! isset($mapping['inherited'])) {
                $mapping['inherited'] = $parentClass->getClassName();
            }
            $subClass->addFieldMapping($fieldName, $mapping);
        }
    }
    
    /**
     * Adds inherited associations to the subclass mapping.
     *
     * @param unknown_type $subClass
     * @param unknown_type $parentClass
     */
    private function _addInheritedRelations($subClass, $parentClass)
    {
        foreach ($parentClass->getAssociationMappings() as $fieldName => $mapping) {
            $subClass->addAssociationMapping($name, $mapping);
        }
    }
    
    /**
     * Loads the metadata of a specified class.
     *
     * @param Doctrine_ClassMetadata $class  The container for the metadata.
     * @param string $name  The name of the class for which the metadata will be loaded.
     */
    private function _loadClassMetadata(Doctrine_ORM_Mapping_ClassMetadata $class, $name)
    {
        if ( ! class_exists($name) || empty($name)) {
            throw new Doctrine_Exception("Couldn't find class " . $name . ".");
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
        
        // set default table name, if necessary
        $tableName = $class->getTableName();
        if ( ! isset($tableName)) {
            $class->setTableName(Doctrine::tableize($class->getClassName()));
        }

        // Complete Id generator mapping. If AUTO is specified we choose the generator
        // most appropriate for the target platform.
        if ($class->getIdGeneratorType() == Doctrine_ORM_Mapping_ClassMetadata::GENERATOR_TYPE_AUTO) {
            if ($this->_targetPlatform->prefersSequences()) {
                $class->setIdGeneratorType(Doctrine_ORM_Mapping_ClassMetadata::GENERATOR_TYPE_SEQUENCE);
            } else if ($this->_targetPlatform->prefersIdentityColumns()) {
                $class->setIdGeneratorType(Doctrine_ORM_Mapping_ClassMetadata::GENERATOR_TYPE_IDENTITY);
            } else {
                $class->setIdGeneratorType(Doctrine_ORM_Mapping_ClassMetadata::GENERATOR_TYPE_TABLE);
            }
        }
        
        return $class;
    }
}




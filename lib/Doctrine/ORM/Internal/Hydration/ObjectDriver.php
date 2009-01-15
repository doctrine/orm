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

#namespace Doctrine\ORM\Internal\Hydration;

/**
 * Defines the object hydration strategy.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 * @internal All the methods in this class are performance-sentitive.
 * @DEPRECATED
 */
class Doctrine_ORM_Internal_Hydration_ObjectDriver
{
    /** Collections initialized by the driver */
    protected $_collections = array();
    /** Memory for initialized relations */
    private $_initializedRelations = array();
    /** Null object */
    //private $_nullObject;
    /** The EntityManager */
    private $_em;
    private $_uow;
    private $_metadataMap = array();
    private $_entityData = array();
    
    public function __construct(Doctrine_ORM_EntityManager $em)
    {
        //$this->_nullObject = Doctrine_ORM_Internal_Null::$INSTANCE;
        $this->_em = $em;
        $this->_uow = $this->_em->getUnitOfWork();
    }

    public function getElementCollection($component)
    {
        $coll = new Doctrine_ORM_Collection($this->_em, $component);
        $this->_collections[] = $coll;
        return $coll;
    }

    public function getLastKey($coll) 
    {
        // check needed because of mixed results.
        // is_object instead of is_array because is_array is slow on large arrays.
        if (is_object($coll)) {
            $coll->last();
            return $coll->key();
        } else {
            end($coll);
            return key($coll);
        }
    }
    
    public function initRelatedCollection($entity, $name)
    {
        $oid = spl_object_hash($entity);
        $classMetadata = $this->_metadataMap[$oid];
        if ( ! isset($this->_initializedRelations[$oid][$name])) {
            $relation = $classMetadata->getAssociationMapping($name);
            $relatedClass = $this->_em->getClassMetadata($relation->getTargetEntityName());
            $coll = $this->getElementCollection($relatedClass->getClassName());
            $coll->_setOwner($entity, $relation);
            $coll->_setHydrationFlag(true);
            $classMetadata->getReflectionProperty($name)->setValue($entity, $coll);
            $this->_initializedRelations[$oid][$name] = true;
            $this->_uow->setOriginalEntityProperty($oid, $name, $coll);
        }
    }
    
    public function registerCollection(Doctrine_ORM_Collection $coll)
    {
        $this->_collections[] = $coll;
    }
    
    public function getNullPointer() 
    {
        //TODO: Return VirtualProxy if lazy association
        return null;
    }
    
    public function getElement(array $data, $className)
    {
        $entity = $this->_em->getUnitOfWork()->createEntity($className, $data);
        $oid = spl_object_hash($entity);
        $this->_metadataMap[$oid] = $this->_em->getClassMetadata($className);
        return $entity;
    }

    /**
     * Adds an element to an indexed collection-valued property.
     *
     * @param <type> $entity1
     * @param <type> $property
     * @param <type> $entity2
     * @param <type> $indexField
     */
    public function addRelatedIndexedElement($entity1, $property, $entity2, $indexField)
    {
        $classMetadata1 = $this->_metadataMap[spl_object_hash($entity1)];
        $classMetadata2 = $this->_metadataMap[spl_object_hash($entity2)];
        $indexValue = $classMetadata2->getReflectionProperty($indexField)->getValue($entity2);
        $classMetadata1->getReflectionProperty($property)->getValue($entity1)->set($indexValue, $entity2);
    }

    /**
     * Adds an element to a collection-valued property.
     *
     * @param <type> $entity1
     * @param <type> $property
     * @param <type> $entity2
     */
    public function addRelatedElement($entity1, $property, $entity2)
    {
        $classMetadata1 = $this->_metadataMap[spl_object_hash($entity1)];
        $classMetadata1->getReflectionProperty($property)->getValue($entity1)->add($entity2);    
    }

    /**
     * Sets a related element.
     *
     * @param <type> $entity1
     * @param <type> $property
     * @param <type> $entity2
     */
    public function setRelatedElement($entity1, $property, $entity2)
    {
        $oid = spl_object_hash($entity1);
        $classMetadata1 = $this->_metadataMap[$oid];
        $classMetadata1->getReflectionProperty($property)->setValue($entity1, $entity2);
        $this->_uow->setOriginalEntityProperty($oid, $property, $entity2);
        $relation = $classMetadata1->getAssociationMapping($property);
        if ($relation->isOneToOne()) {
            $targetClass = $this->_em->getClassMetadata($relation->getTargetEntityName());
            if ($relation->isOwningSide()) {
                // If there is an inverse mapping on the target class its bidirectional
                if ($targetClass->hasInverseAssociationMapping($property)) {
                    $oid2 = spl_object_hash($entity2);
                    $sourceProp = $targetClass->getInverseAssociationMapping($fieldName)->getSourceFieldName();
                    $targetClass->getReflectionProperty($sourceProp)->setValue($entity2, $entity1);
                    //$this->_entityData[$oid2][$sourceProp] = $entity1;
                }
            } else {
                // for sure bidirectional, as there is no inverse side in unidirectional
                $mappedByProp = $relation->getMappedByFieldName();
                $targetClass->getReflectionProperty($mappedByProp)->setValue($entity2, $entity1);
                //$this->_entityData[spl_object_hash($entity2)][$mappedByProp] = $entity1;
            }
        }
    }
    
    public function isIndexKeyInUse($entity, $assocField, $indexField)
    {
        return $this->_metadataMap[spl_object_hash($entity)]->getReflectionProperty($assocField)
                ->getValue($entity)->containsKey($indexField);
    }
    
    public function isFieldSet($entity, $field)
    {
        return $this->_metadataMap[spl_object_hash($entity)]->getReflectionProperty($field)
                ->getValue($entity) !== null;
    }
    
    public function getFieldValue($entity, $field)
    {
        return $this->_metadataMap[spl_object_hash($entity)]->getReflectionProperty($field)
                ->getValue($entity);
    }
    
    public function getReferenceValue($entity, $field)
    {
        return $this->_metadataMap[spl_object_hash($entity)]->getReflectionProperty($field)
                ->getValue($entity);
    }
    
    public function addElementToIndexedCollection($coll, $entity, $keyField)
    {
        $coll->set($entity, $this->getFieldValue($keyField, $entity));
    }
    
    public function addElementToCollection($coll, $entity)
    {
        $coll->add($entity);
    }
    
    /**
     * Updates the result pointer for an Entity. The result pointers point to the
     * last seen instance of each Entity type. This is used for graph construction.
     *
     * @param array $resultPointers  The result pointers.
     * @param Collection $coll  The element.
     * @param boolean|integer $index  Index of the element in the collection.
     * @param string $dqlAlias
     * @param boolean $oneToOne  Whether it is a single-valued association or not.
     */
    public function updateResultPointer(&$resultPointers, &$coll, $index, $dqlAlias, $oneToOne)
    {
        if ($coll === null) {
            unset($resultPointers[$dqlAlias]); // Ticket #1228
            return;
        }
        
        if ($index !== false) {
            $resultPointers[$dqlAlias] = $coll[$index];
            return;
        }

        if ( ! is_object($coll)) {
            end($coll);
            $resultPointers[$dqlAlias] =& $coll[key($coll)];
        } else if ($coll instanceof Doctrine_ORM_Collection) {
            if (count($coll) > 0) {
                $resultPointers[$dqlAlias] = $coll->last();
            }
        } else {
            $resultPointers[$dqlAlias] = $coll;
        }
    }
    
    public function flush()
    {
        // take snapshots from all initialized collections
        foreach ($this->_collections as $coll) {
            $coll->_takeSnapshot();
            $coll->_setHydrationFlag(false);
            $this->_uow->addManagedCollection($coll);
        }
        // clean up
        $this->_collections = array();
        $this->_initializedRelations = array();
        $this->_metadataMap = array();
        $this->_entityData = array();
    }
    
}

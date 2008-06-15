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

/**
 * Doctrine_Hydrator_RecordDriver
 * Hydration strategy used for creating graphs of entity objects.
 *
 * @package     Doctrine
 * @subpackage  Hydrate
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 */
class Doctrine_Hydrator_RecordDriver
{
    /** Collections initialized by the driver */
    protected $_collections = array();
    /** Memory for initialized relations */
    private $_initializedRelations = array();
    /** Null object */
    private $_nullObject;
    /** The EntityManager */
    private $_em;
    
    public function __construct(Doctrine_EntityManager $em)
    {
        $this->_nullObject = Doctrine_Null::$INSTANCE;
        $this->_em = $em;
    }

    public function getElementCollection($component)
    {
        $coll = new Doctrine_Collection($component);
        $this->_collections[] = $coll;

        return $coll;
    }

    public function getLastKey($coll) 
    {
        // check needed because of mixed results
        if (is_array($coll)) {
            end($coll);
            return key($coll);
        } else {
            $coll->end();
            return $coll->key(); 
        }
    }
    
    public function initRelatedCollection(Doctrine_Entity $entity, $name)
    {
        if ( ! isset($this->_initializedRelations[$entity->getOid()][$name])) {
            $relation = $entity->getClassMetadata()->getRelation($name);
            $relatedClass = $relation->getTable();
            $coll = $this->getElementCollection($relatedClass->getClassName());
            $coll->setReference($entity, $relation);
            $entity->_rawSetReference($name, $coll);
            $this->_initializedRelations[$entity->getOid()][$name] = true;
        }
    }
    
    public function registerCollection(Doctrine_Collection $coll)
    {
        $this->_collections[] = $coll;
    }
    
    public function getNullPointer() 
    {
        return $this->_nullObject;
    }
    
    public function getElement(array $data, $className)
    {
        return $this->_em->createEntity($className, $data);
    }
    
    public function addRelatedIndexedElement(Doctrine_Entity $entity1, $property,
            Doctrine_Entity $entity2, $indexField)
    {
        $entity1->_rawGetReference($property)->add($entity2, $entity2->_rawGetField($indexField));
    }
    
    public function addRelatedElement(Doctrine_Entity $entity1, $property,
            Doctrine_Entity $entity2)
    {
        $entity1->_rawGetReference($property)->add($entity2);       
    }
    
    public function setRelatedElement(Doctrine_Entity $entity1, $property, $entity2)
    {
        $entity1->_rawSetReference($property, $entity2);
    }
    
    public function isIndexKeyInUse(Doctrine_Entity $entity, $assocField, $indexField)
    {
        return $entity->_rawGetReference($assocField)->contains($indexField);
    }
    
    public function isFieldSet(Doctrine_Entity $entity, $field)
    {
        return $entity->contains($field);
    }
    
    public function getFieldValue(Doctrine_Entity $entity, $field)
    {
        return $entity->_rawGetField($field);
    }
    
    public function getReferenceValue(Doctrine_Entity $entity, $field)
    {
        return $entity->_rawGetReference($field);
    }
    
    public function addElementToIndexedCollection($coll, $entity, $keyField)
    {
        $coll->add($entity, $entity->_rawGetField($keyField));
    }
    
    public function addElementToCollection($coll, $entity)
    {
        $coll->add($entity);
    }
    
    public function flush()
    {
        // take snapshots from all initialized collections
        foreach ($this->_collections as $coll) {
            $coll->takeSnapshot();
        }
        $this->_collections = array();
        $this->_initializedRelations = array();
    }
    
    
}

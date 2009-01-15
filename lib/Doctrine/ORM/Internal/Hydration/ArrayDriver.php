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

#namespace Doctrine\ORM\Internal\Hydration;

/**
 * Defines an array hydration strategy.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Roman Borschel <roman@code-factory.org>
 * @DEPRECATED
 */
class Doctrine_ORM_Internal_Hydration_ArrayDriver
{   
    /**
     *
     */
    public function getElementCollection($component)
    {
        return array();
    }
    
    /**
     *
     */
    public function getElement(array $data, $component)
    {
        return $data;
    }
    
    /**
     *
     */
    public function registerCollection($coll)
    { /* Nothing to do */ }
    
    /**
     *
     */
    public function initRelatedCollection(array &$data, $name)
    {
        if ( ! isset($data[$name])) {
            $data[$name] = array();
        }
    }
    
    public function addRelatedIndexedElement(array &$entity1, $property, array &$entity2, $indexField)
    {
        $entity1[$property][$entity2[$indexField]] = $entity2;
    }
    
    public function addRelatedElement(array &$entity1, $property, array &$entity2)
    {
        $entity1[$property][] = $entity2;
    }
    
    public function setRelatedElement(array &$entity1, $property, &$entity2)
    {
        $entity1[$property] = $entity2;
    }
    
    public function isIndexKeyInUse(array &$entity, $assocField, $indexField)
    {
        return isset($entity[$assocField][$indexField]);
    }
    
    public function isFieldSet(array &$entity, $field)
    {
        return isset($entity[$field]);
    }
    
    public function getFieldValue(array &$entity, $field)
    {
        return $entity[$field];
    }
    
    public function &getReferenceValue(array &$entity, $field)
    {
        return $entity[$field];
    }
    
    public function addElementToIndexedCollection(array &$coll, array &$entity, $keyField)
    {
        $coll[$entity[$keyField]] = $entity;
    }
    
    public function addElementToCollection(array &$coll, array &$entity)
    {
        $coll[] = $entity;
    }
    
    /**
     *
     */
    public function getNullPointer() 
    {
        return null;    
    }
    
    /**
     *
     */
    public function getLastKey(&$data)
    {
        end($data);
        return key($data);
    }
    
    /**
     * Updates the result pointer for an Entity. The result pointers point to the
     * last seen instance of each Entity type. This is used for graph construction.
     *
     * @param array $resultPointers  The result pointers.
     * @param array $coll  The element.
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
            $resultPointers[$dqlAlias] =& $coll[$index];
            return;
        }
        
        if ($coll) {
            if ($oneToOne) {
                $resultPointers[$dqlAlias] =& $coll;
            } else {
                end($coll);
                $resultPointers[$dqlAlias] =& $coll[key($coll)];
            }
        }
    }
    
    /**
     *
     */
    public function flush()
    { /* Nothing to do */ }
}

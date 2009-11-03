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

/**
 * A <tt>ClassMetadata</tt> instance holds all the object-relational mapping metadata
 * of an entity and it's associations.
 * 
 * Once populated, ClassMetadata instances are usually cached in a serialized form.
 *
 * <b>IMPORTANT NOTE:</b>
 *
 * The fields of this class are only public for 2 reasons:
 * 1) To allow fast READ access.
 * 2) To drastically reduce the size of a serialized instance (private/protected members
 *    get the whole class name, namespace inclusive, prepended to every property in
 *    the serialized representation).
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @since 2.0
 */
final class ClassMetadata extends ClassMetadataInfo
{
    /**
     * The ReflectionClass instance of the mapped class.
     *
     * @var ReflectionClass
     */
    public $reflClass;

    /**
     * The ReflectionProperty instances of the mapped class.
     *
     * @var array
     */
    public $reflFields = array();

    /**
     * Initializes a new ClassMetadata instance that will hold the object-relational mapping
     * metadata of the class with the given name.
     *
     * @param string $entityName The name of the entity class the new instance is used for.
     */
    public function __construct($entityName)
    {
        $this->name = $entityName;
        $this->reflClass = new \ReflectionClass($entityName);
        $this->namespace = $this->reflClass->getNamespaceName();
        $this->primaryTable['name'] = $this->reflClass->getShortName();
        $this->rootEntityName = $entityName;
        
        //$this->prototype = unserialize(sprintf('O:%d:"%s":0:{}', strlen($this->name), $this->name));
    }

    /**
     * Gets the ReflectionClass instance of the mapped class.
     *
     * @return ReflectionClass
     */
    public function getReflectionClass()
    {
        return $this->reflClass;
    }

    /**
     * Gets the ReflectionPropertys of the mapped class.
     *
     * @return array An array of ReflectionProperty instances.
     */
    public function getReflectionProperties()
    {
        return $this->reflFields;
    }

    /**
     * INTERNAL:
     * Adds a reflection property. Usually only used by the ClassMetadataFactory
     * while processing inheritance mappings.
     *
     * @param array $props
     */
    public function addReflectionProperty($propName, \ReflectionProperty $property)
    {
        $this->reflFields[$propName] = $property;
    }

    /**
     * Gets a ReflectionProperty for a specific field of the mapped class.
     *
     * @param string $name
     * @return ReflectionProperty
     */
    public function getReflectionProperty($name)
    {
        return $this->reflFields[$name];
    }

    /**
     * Gets the ReflectionProperty for the single identifier field.
     *
     * @return ReflectionProperty
     * @throws DoctrineException If the class has a composite identifier.
     */
    public function getSingleIdReflectionProperty()
    {
        if ($this->isIdentifierComposite) {
            throw DoctrineException::singleIdNotAllowedOnCompositePrimaryKey();
        }
        return $this->reflFields[$this->identifier[0]];
    }
    
    /**
     * Validates & completes the given field mapping.
     *
     * @param array $mapping  The field mapping to validated & complete.
     * @return array  The validated and completed field mapping.
     */
    protected function _validateAndCompleteFieldMapping(array &$mapping)
    {
        parent::_validateAndCompleteFieldMapping($mapping);

        // Store ReflectionProperty of mapped field
        $refProp = $this->reflClass->getProperty($mapping['fieldName']);
        $refProp->setAccessible(true);
        $this->reflFields[$mapping['fieldName']] = $refProp;
    }

    /**
     * Extracts the identifier values of an entity of this class.
     * 
     * For composite identifiers, the identifier values are returned as an array
     * with the same order as the field order in {@link identifier}.
     *
     * @param object $entity
     * @return mixed
     */
    public function getIdentifierValues($entity)
    {
        if ($this->isIdentifierComposite) {
            $id = array();
            foreach ($this->identifier as $idField) {
                $value = $this->reflFields[$idField]->getValue($entity);
                if ($value !== null) {
                    $id[] = $value;
                }
            }
            return $id;
        } else {
            return $this->reflFields[$this->identifier[0]]->getValue($entity);
        }
    }
    
    public function getColumnValues($entity, array $columns)
    {
        $values = array();
        foreach ($columns as $column) {
            $values[] = $this->reflFields[$this->fieldNames[$column]]->getValue($entity);
        }
        return $values;
    }

    /**
     * Populates the entity identifier of an entity.
     *
     * @param object $entity
     * @param mixed $id
     * @todo Rename to assignIdentifier()
     */
    public function setIdentifierValues($entity, $id)
    {
        if ($this->isIdentifierComposite) {
            foreach ((array)$id as $idField => $idValue) {
                $this->reflFields[$idField]->setValue($entity, $idValue);
            }
        } else {
            $this->reflFields[$this->identifier[0]]->setValue($entity, $id);
        }
    }

    /**
     * Sets the specified field to the specified value on the given entity.
     *
     * @param object $entity
     * @param string $field
     * @param mixed $value
     */
    public function setFieldValue($entity, $field, $value)
    {
        $this->reflFields[$field]->setValue($entity, $value);
    }

    /**
     * Sets the field mapped to the specified column to the specified value on the given entity.
     *
     * @param object $entity
     * @param string $field
     * @param mixed $value
     */
    public function setColumnValue($entity, $column, $value)
    {
        $this->reflFields[$this->fieldNames[$column]]->setValue($entity, $value);
    }

    /**
     * Stores the association mapping.
     *
     * @param AssociationMapping $assocMapping
     */
    protected function _storeAssociationMapping(AssociationMapping $assocMapping)
    {
        parent::_storeAssociationMapping($assocMapping);

        // Store ReflectionProperty of mapped field
        $sourceFieldName = $assocMapping->sourceFieldName;
        $refProp = $this->reflClass->getProperty($sourceFieldName);
        $refProp->setAccessible(true);
        $this->reflFields[$sourceFieldName] = $refProp;
    }
    
    /**
     * Dispatches the lifecycle event of the given entity to the registered
     * lifecycle callbacks and lifecycle listeners.
     *
     * @param string $event  The lifecycle event.
     * @param Entity $entity  The Entity on which the event occured.
     */
    public function invokeLifecycleCallbacks($lifecycleEvent, $entity)
    {
        foreach ($this->lifecycleCallbacks[$lifecycleEvent] as $callback) {
            $entity->$callback();
        }
    }
    
    /**
     * Gets the (possibly quoted) column name of a mapped field for safe use
     * in an SQL statement.
     * 
     * @param string $field
     * @param AbstractPlatform $platform
     * @return string
     */
    public function getQuotedColumnName($field, $platform)
    {
        return isset($this->fieldMappings[$field]['quoted']) ?
                $platform->quoteIdentifier($this->fieldMappings[$field]['columnName']) :
                $this->fieldMappings[$field]['columnName'];
    }
    
    /**
     * Gets the (possibly quoted) primary table name of this class for safe use
     * in an SQL statement.
     * 
     * @param AbstractPlatform $platform
     * @return string
     */
    public function getQuotedTableName($platform)
    {
        return isset($this->primaryTable['quoted']) ?
                $platform->quoteIdentifier($this->primaryTable['name']) :
                $this->primaryTable['name'];
    }
    
    /**
     * Gets the (possibly quoted) name of the discriminator column for safe use
     * in an SQL statement.
     * 
     * @param AbstractPlatform $platform
     * @return string
     */
    public function getQuotedDiscriminatorColumnName($platform)
    {
        return isset($this->discriminatorColumn['quoted']) ?
                $platform->quoteIdentifier($this->discriminatorColumn['name']) :
                $this->discriminatorColumn['name'];
    }
    
    /**
     * Creates a string representation of this instance.
     *
     * @return string The string representation of this instance.
     * @todo Construct meaningful string representation.
     */
    public function __toString()
    {
        return __CLASS__ . '@' . spl_object_hash($this);
    }
    
    /**
     * Determines which fields get serialized.
     * 
     * Parts that are NOT serialized because they can not be properly unserialized:
     *      - reflClass (ReflectionClass)
     *      - reflFields (ReflectionProperty array)
     * 
     * @return array The names of all the fields that should be serialized.
     */
    public function __sleep()
    {
        return array(
            'associationMappings',
            'changeTrackingPolicy',
            'columnNames',
            'customRepositoryClassName',
            'discriminatorColumn',
            'discriminatorValue',
            'discriminatorMap',
            'fieldMappings',
            'fieldNames',
            'generatorType',
            'identifier',
            'idGenerator',
            'inheritanceType',
            'inheritedAssociationFields',
            'insertSql',
            'inverseMappings',
            'isIdentifierComposite',
            'isMappedSuperclass',
            'isVersioned',
            'lifecycleCallbacks',
            'name',
            'namespace',
            'parentClasses',
            'primaryTable',
            'resultColumnNames',
            'rootEntityName',
            'sequenceGeneratorDefinition',
            'subClasses',
            'versionField'
        );
    }
    
    /**
     * Restores some state that can not be serialized/unserialized.
     * 
     * @return void
     */
    public function __wakeup()
    {
        // Restore ReflectionClass and properties
        $this->reflClass = new \ReflectionClass($this->name);
        foreach ($this->fieldNames as $field) {
            $this->reflFields[$field] = $this->reflClass->getProperty($field);
            $this->reflFields[$field]->setAccessible(true);
        }
        foreach ($this->associationMappings as $field => $mapping) {
            $this->reflFields[$field] = $this->reflClass->getProperty($field);
            $this->reflFields[$field]->setAccessible(true);
        }
        
        //$this->prototype = unserialize(sprintf('O:%d:"%s":0:{}', strlen($this->name), $this->name));
    }
    
    //public $prototype;
}

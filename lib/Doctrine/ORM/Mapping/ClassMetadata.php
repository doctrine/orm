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

use ReflectionClass, ReflectionProperty;

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
class ClassMetadata extends ClassMetadataInfo
{
    /**
     * The ReflectionProperty instances of the mapped class.
     *
     * @var array
     */
    public $reflFields = array();
    
    /**
     * The prototype from which new instances of the mapped class are created.
     * 
     * @var object
     */
    private $_prototype;

    /**
     * Initializes a new ClassMetadata instance that will hold the object-relational mapping
     * metadata of the class with the given name.
     *
     * @param string $entityName The name of the entity class the new instance is used for.
     */
    public function __construct($entityName)
    {
        parent::__construct($entityName);
        $this->reflClass = new ReflectionClass($entityName);
        $this->namespace = $this->reflClass->getNamespaceName();
        $this->table['name'] = $this->reflClass->getShortName();
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
     * @throws BadMethodCallException If the class has a composite identifier.
     */
    public function getSingleIdReflectionProperty()
    {
        if ($this->isIdentifierComposite) {
            throw new \BadMethodCallException("Class " . $this->name . " has a composite identifier.");
        }
        return $this->reflFields[$this->identifier[0]];
    }
    
    /**
     * Validates & completes the given field mapping.
     *
     * @param array $mapping  The field mapping to validated & complete.
     * @return array  The validated and completed field mapping.
     * 
     * @throws MappingException
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
     * @return array
     */
    public function getIdentifierValues($entity)
    {
        if ($this->isIdentifierComposite) {
            $id = array();
            foreach ($this->identifier as $idField) {
                $value = $this->reflFields[$idField]->getValue($entity);
                if ($value !== null) {
                    $id[$idField] = $value;
                }
            }
            return $id;
        } else {
            $value = $this->reflFields[$this->identifier[0]]->getValue($entity);
            if ($value !== null) {
                return array($this->identifier[0] => $value);
            }
            return array();
        }
    }

    /**
     * Populates the entity identifier of an entity.
     *
     * @param object $entity
     * @param mixed $id
     * @todo Rename to assignIdentifier()
     */
    public function setIdentifierValues($entity, array $id)
    {
        foreach ($id as $idField => $idValue) {
            $this->reflFields[$idField]->setValue($entity, $idValue);
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
     * Gets the specified field's value off the given entity.
     *
     * @param object $entity
     * @param string $field
     */
    public function getFieldValue($entity, $field)
    {
        return $this->reflFields[$field]->getValue($entity);
    }

    /**
     * Stores the association mapping.
     *
     * @param AssociationMapping $assocMapping
     */
    protected function _storeAssociationMapping(array $assocMapping)
    {
        parent::_storeAssociationMapping($assocMapping);

        // Store ReflectionProperty of mapped field
        $sourceFieldName = $assocMapping['fieldName'];

        $refProp = $this->reflClass->getProperty($sourceFieldName);
        $refProp->setAccessible(true);
        $this->reflFields[$sourceFieldName] = $refProp;
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
        return isset($this->table['quoted']) ?
                $platform->quoteIdentifier($this->table['name']) :
                $this->table['name'];
    }

    /**
     * Gets the (possibly quoted) name of the join table.
     *
     * @param AbstractPlatform $platform
     * @return string
     */
    public function getQuotedJoinTableName(array $assoc, $platform)
    {
        return isset($assoc['joinTable']['quoted'])
            ? $platform->quoteIdentifier($assoc['joinTable']['name'])
            : $assoc['joinTable']['name'];
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
     * It is only serialized what is necessary for best unserialization performance.
     * That means any metadata properties that are not set or empty or simply have
     * their default value are NOT serialized.
     * 
     * Parts that are also NOT serialized because they can not be properly unserialized:
     *      - reflClass (ReflectionClass)
     *      - reflFields (ReflectionProperty array)
     * 
     * @return array The names of all the fields that should be serialized.
     */
    public function __sleep()
    {
        // This metadata is always serialized/cached.
        $serialized = array(
            'associationMappings',
            'columnNames', //TODO: Not really needed. Can use fieldMappings[$fieldName]['columnName']
            'fieldMappings',
            'fieldNames',
            'identifier',
            'isIdentifierComposite', // TODO: REMOVE
            'name',
            'namespace', // TODO: REMOVE
            'table',
            'rootEntityName',
            'idGenerator', //TODO: Does not really need to be serialized. Could be moved to runtime.
        );

        // The rest of the metadata is only serialized if necessary.
        if ($this->changeTrackingPolicy != self::CHANGETRACKING_DEFERRED_IMPLICIT) {
            $serialized[] = 'changeTrackingPolicy';
        }

        if ($this->customRepositoryClassName) {
            $serialized[] = 'customRepositoryClassName';
        }

        if ($this->inheritanceType != self::INHERITANCE_TYPE_NONE) {
            $serialized[] = 'inheritanceType';
            $serialized[] = 'discriminatorColumn';
            $serialized[] = 'discriminatorValue';
            $serialized[] = 'discriminatorMap';
            $serialized[] = 'parentClasses';
            $serialized[] = 'subClasses';
        }

        if ($this->generatorType != self::GENERATOR_TYPE_NONE) {
            $serialized[] = 'generatorType';
            if ($this->generatorType == self::GENERATOR_TYPE_SEQUENCE) {
                $serialized[] = 'sequenceGeneratorDefinition';
            }
        }

        if ($this->isMappedSuperclass) {
            $serialized[] = 'isMappedSuperclass';
        }

        if ($this->isVersioned) {
            $serialized[] = 'isVersioned';
            $serialized[] = 'versionField';
        }

        if ($this->lifecycleCallbacks) {
            $serialized[] = 'lifecycleCallbacks';
        }

        return $serialized;
    }

    /**
     * Restores some state that can not be serialized/unserialized.
     * 
     * @return void
     */
    public function __wakeup()
    {
        // Restore ReflectionClass and properties
        $this->reflClass = new ReflectionClass($this->name);

        foreach ($this->fieldMappings as $field => $mapping) {
            if (isset($mapping['declared'])) {
                $reflField = new ReflectionProperty($mapping['declared'], $field);
            } else {
                $reflField = $this->reflClass->getProperty($field);
            }
            $reflField->setAccessible(true);
            $this->reflFields[$field] = $reflField;
        }

        foreach ($this->associationMappings as $field => $mapping) {
            if (isset($mapping['declared'])) {
                $reflField = new ReflectionProperty($mapping['declared'], $field);
            } else {
                $reflField = $this->reflClass->getProperty($field);
            }

            $reflField->setAccessible(true);
            $this->reflFields[$field] = $reflField;
        }
    }
    
    /**
     * Creates a new instance of the mapped class, without invoking the constructor.
     * 
     * @return object
     */
    public function newInstance()
    {
        if ($this->_prototype === null) {
            $this->_prototype = unserialize(sprintf('O:%d:"%s":0:{}', strlen($this->name), $this->name));
        }
        return clone $this->_prototype;
    }
}

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

use ReflectionClass;

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
class ClassMetadataInfo
{
    /* The inheritance mapping types */
    /**
     * NONE means the class does not participate in an inheritance hierarchy
     * and therefore does not need an inheritance mapping type.
     */
    const INHERITANCE_TYPE_NONE = 1;
    /**
     * JOINED means the class will be persisted according to the rules of
     * <tt>Class Table Inheritance</tt>.
     */
    const INHERITANCE_TYPE_JOINED = 2;
    /**
     * SINGLE_TABLE means the class will be persisted according to the rules of
     * <tt>Single Table Inheritance</tt>.
     */
    const INHERITANCE_TYPE_SINGLE_TABLE = 3;
    /**
     * TABLE_PER_CLASS means the class will be persisted according to the rules
     * of <tt>Concrete Table Inheritance</tt>.
     */
    const INHERITANCE_TYPE_TABLE_PER_CLASS = 4;

    /* The Id generator types. */
    /**
     * AUTO means the generator type will depend on what the used platform prefers.
     * Offers full portability.
     */
    const GENERATOR_TYPE_AUTO = 1;
    /**
     * SEQUENCE means a separate sequence object will be used. Platforms that do
     * not have native sequence support may emulate it. Full portability is currently
     * not guaranteed.
     */
    const GENERATOR_TYPE_SEQUENCE = 2;
    /**
     * TABLE means a separate table is used for id generation.
     * Offers full portability.
     */
    const GENERATOR_TYPE_TABLE = 3;
    /**
     * IDENTITY means an identity column is used for id generation. The database
     * will fill in the id column on insertion. Platforms that do not support
     * native identity columns may emulate them. Full portability is currently
     * not guaranteed.
     */
    const GENERATOR_TYPE_IDENTITY = 4;
    /**
     * NONE means the class does not have a generated id. That means the class
     * must have a natural, manually assigned id.
     */
    const GENERATOR_TYPE_NONE = 5;
    /**
     * DEFERRED_IMPLICIT means that changes of entities are calculated at commit-time
     * by doing a property-by-property comparison with the original data. This will
     * be done for all entities that are in MANAGED state at commit-time.
     *
     * This is the default change tracking policy.
     */
    const CHANGETRACKING_DEFERRED_IMPLICIT = 1;
    /**
     * DEFERRED_EXPLICIT means that changes of entities are calculated at commit-time
     * by doing a property-by-property comparison with the original data. This will
     * be done only for entities that were explicitly saved (through persist() or a cascade).
     */
    const CHANGETRACKING_DEFERRED_EXPLICIT = 2;
    /**
     * NOTIFY means that Doctrine relies on the entities sending out notifications
     * when their properties change. Such entity classes must implement
     * the <tt>NotifyPropertyChanged</tt> interface.
     */
    const CHANGETRACKING_NOTIFY = 3;
    /**
     * Specifies that an association is to be fetched when it is first accessed.
     */
    const FETCH_LAZY = 2;
    /**
     * Specifies that an association is to be fetched when the owner of the
     * association is fetched. 
     */
    const FETCH_EAGER = 3;
    /**
     * Identifies a one-to-one association.
     */
    const ONE_TO_ONE = 1;
    /**
     * Identifies a many-to-one association.
     */
    const MANY_TO_ONE = 2;
    /**
     * Combined bitmask for to-one (single-valued) associations.
     */
    const TO_ONE = 3;
    /**
     * Identifies a one-to-many association.
     */
    const ONE_TO_MANY = 4;
    /**
     * Identifies a many-to-many association.
     */
    const MANY_TO_MANY = 8;
    /**
     * Combined bitmask for to-many (collection-valued) associations.
     */
    const TO_MANY = 12;

    /**
     * READ-ONLY: The name of the entity class.
     */
    public $name;

    /**
     * READ-ONLY: The namespace the entity class is contained in.
     *
     * @var string
     * @todo Not really needed. Usage could be localized.
     */
    public $namespace;

    /**
     * READ-ONLY: The name of the entity class that is at the root of the mapped entity inheritance
     * hierarchy. If the entity is not part of a mapped inheritance hierarchy this is the same
     * as {@link $entityName}.
     *
     * @var string
     */
    public $rootEntityName;

    /**
     * The name of the custom repository class used for the entity class.
     * (Optional).
     *
     * @var string
     */
    public $customRepositoryClassName;

    /**
     * READ-ONLY: Whether this class describes the mapping of a mapped superclass.
     *
     * @var boolean
     */
    public $isMappedSuperclass = false;

    /**
     * READ-ONLY: The names of the parent classes (ancestors).
     *
     * @var array
     */
    public $parentClasses = array();

    /**
     * READ-ONLY: The names of all subclasses (descendants).
     *
     * @var array
     */
    public $subClasses = array();

    /**
     * READ-ONLY: The field names of all fields that are part of the identifier/primary key
     * of the mapped entity class.
     *
     * @var array
     */
    public $identifier = array();

    /**
     * READ-ONLY: The inheritance mapping type used by the class.
     *
     * @var integer
     */
    public $inheritanceType = self::INHERITANCE_TYPE_NONE;

    /**
     * READ-ONLY: The Id generator type used by the class.
     *
     * @var string
     */
    public $generatorType = self::GENERATOR_TYPE_NONE;

    /**
     * READ-ONLY: The field mappings of the class.
     * Keys are field names and values are mapping definitions.
     *
     * The mapping definition array has the following values:
     *
     * - <b>fieldName</b> (string)
     * The name of the field in the Entity.
     *
     * - <b>type</b> (string)
     * The type name of the mapped field. Can be one of Doctrine's mapping types
     * or a custom mapping type.
     *
     * - <b>columnName</b> (string, optional)
     * The column name. Optional. Defaults to the field name.
     *
     * - <b>length</b> (integer, optional)
     * The database length of the column. Optional. Default value taken from
     * the type.
     *
     * - <b>id</b> (boolean, optional)
     * Marks the field as the primary key of the entity. Multiple fields of an
     * entity can have the id attribute, forming a composite key.
     *
     * - <b>nullable</b> (boolean, optional)
     * Whether the column is nullable. Defaults to FALSE.
     *
     * - <b>columnDefinition</b> (string, optional, schema-only)
     * The SQL fragment that is used when generating the DDL for the column.
     *
     * - <b>precision</b> (integer, optional, schema-only)
     * The precision of a decimal column. Only valid if the column type is decimal.
     *
     * - <b>scale</b> (integer, optional, schema-only)
     * The scale of a decimal column. Only valid if the column type is decimal.
     *
     * - <b>unique (string, optional, schema-only)</b>
     * Whether a unique constraint should be generated for the column.
     *
     * @var array
     */
    public $fieldMappings = array();

    /**
     * READ-ONLY: An array of field names. Used to look up field names from column names.
     * Keys are column names and values are field names.
     * This is the reverse lookup map of $_columnNames.
     *
     * @var array
     */
    public $fieldNames = array();

    /**
     * READ-ONLY: A map of field names to column names. Keys are field names and values column names.
     * Used to look up column names from field names.
     * This is the reverse lookup map of $_fieldNames.
     *
     * @var array
     * @todo We could get rid of this array by just using $fieldMappings[$fieldName]['columnName'].
     */
    public $columnNames = array();

    /**
     * READ-ONLY: The discriminator value of this class.
     *
     * <b>This does only apply to the JOINED and SINGLE_TABLE inheritance mapping strategies
     * where a discriminator column is used.</b>
     *
     * @var mixed
     * @see discriminatorColumn
     */
    public $discriminatorValue;

    /**
     * READ-ONLY: The discriminator map of all mapped classes in the hierarchy.
     *
     * <b>This does only apply to the JOINED and SINGLE_TABLE inheritance mapping strategies
     * where a discriminator column is used.</b>
     *
     * @var mixed
     * @see discriminatorColumn
     */
    public $discriminatorMap = array();

    /**
     * READ-ONLY: The definition of the descriminator column used in JOINED and SINGLE_TABLE
     * inheritance mappings.
     *
     * @var array
     */
    public $discriminatorColumn;

    /**
     * READ-ONLY: The primary table definition. The definition is an array with the
     * following entries:
     *
     * name => <tableName>
     * schema => <schemaName>
     * indexes => array
     * uniqueConstraints => array
     *
     * @var array
     * @todo Rename to just $table
     */
    public $table;

    /**
     * READ-ONLY: The registered lifecycle callbacks for entities of this class.
     *
     * @var array
     */
    public $lifecycleCallbacks = array();

    /**
     * READ-ONLY: The association mappings of this class.
     *
     * The mapping definition array supports the following keys:
     *
     * - <b>fieldName</b> (string)
     * The name of the field in the entity the association is mapped to.
     *
     * - <b>targetEntity</b> (string)
     * The class name of the target entity. If it is fully-qualified it is used as is.
     * If it is a simple, unqualified class name the namespace is assumed to be the same
     * as the namespace of the source entity.
     *
     * - <b>mappedBy</b> (string, required for bidirectional associations)
     * The name of the field that completes the bidirectional association on the owning side.
     * This key must be specified on the inverse side of a bidirectional association.
     * 
     * - <b>inversedBy</b> (string, required for bidirectional associations)
     * The name of the field that completes the bidirectional association on the inverse side.
     * This key must be specified on the owning side of a bidirectional association.
     *
     * - <b>cascade</b> (array, optional)
     * The names of persistence operations to cascade on the association. The set of possible
     * values are: "persist", "remove", "detach", "merge", "refresh", "all" (implies all others).
     *
     * - <b>orderBy</b> (array, one-to-many/many-to-many only)
     * A map of field names (of the target entity) to sorting directions (ASC/DESC).
     * Example: array('priority' => 'desc')
     *
     * - <b>fetch</b> (integer, optional)
     * The fetching strategy to use for the association, usually defaults to FETCH_LAZY.
     * Possible values are: ClassMetadata::FETCH_EAGER, ClassMetadata::FETCH_LAZY.
     *
     * - <b>joinTable</b> (array, optional, many-to-many only)
     * Specification of the join table and its join columns (foreign keys).
     * Only valid for many-to-many mappings. Note that one-to-many associations can be mapped
     * through a join table by simply mapping the association as many-to-many with a unique
     * constraint on the join table.
     * 
     * A join table definition has the following structure:
     * <pre>
     * array(
     *     'name' => <join table name>,
     *      'joinColumns' => array(<join column mapping from join table to source table>),
     *      'inverseJoinColumns' => array(<join column mapping from join table to target table>)
     * )
     * </pre>
     *
     *
     * @var array
     */
    public $associationMappings = array();

    /**
     * READ-ONLY: Flag indicating whether the identifier/primary key of the class is composite.
     *
     * @var boolean
     */
    public $isIdentifierComposite = false;

    /**
     * READ-ONLY: The ID generator used for generating IDs for this class.
     *
     * @var AbstractIdGenerator
     * @todo Remove!
     */
    public $idGenerator;

    /**
     * READ-ONLY: The definition of the sequence generator of this class. Only used for the
     * SEQUENCE generation strategy.
     * 
     * The definition has the following structure:
     * <code>
     * array(
     *     'sequenceName' => 'name',
     *     'allocationSize' => 20,
     *     'initialValue' => 1
     * )
     * </code>
     *
     * @var array
     * @todo Merge with tableGeneratorDefinition into generic generatorDefinition
     */
    public $sequenceGeneratorDefinition;

    /**
     * READ-ONLY: The definition of the table generator of this class. Only used for the
     * TABLE generation strategy.
     *
     * @var array
     * @todo Merge with tableGeneratorDefinition into generic generatorDefinition
     */
    public $tableGeneratorDefinition;

    /**
     * READ-ONLY: The policy used for change-tracking on entities of this class.
     *
     * @var integer
     */
    public $changeTrackingPolicy = self::CHANGETRACKING_DEFERRED_IMPLICIT;

    /**
     * READ-ONLY: A flag for whether or not instances of this class are to be versioned
     * with optimistic locking.
     *
     * @var boolean $isVersioned
     */
    public $isVersioned;

    /**
     * READ-ONLY: The name of the field which is used for versioning in optimistic locking (if any).
     *
     * @var mixed $versionField
     */
    public $versionField;

    /**
     * The ReflectionClass instance of the mapped class.
     *
     * @var ReflectionClass
     */
    public $reflClass;

    /**
     * Initializes a new ClassMetadata instance that will hold the object-relational mapping
     * metadata of the class with the given name.
     *
     * @param string $entityName The name of the entity class the new instance is used for.
     */
    public function __construct($entityName)
    {
        $this->name = $entityName;
        $this->rootEntityName = $entityName;
    }

    /**
     * Gets the ReflectionClass instance of the mapped class.
     *
     * @return ReflectionClass
     */
    public function getReflectionClass()
    {
        if ( ! $this->reflClass) {
            $this->reflClass = new ReflectionClass($this->name);
        }
        return $this->reflClass;
    }

    /**
     * Sets the change tracking policy used by this class.
     *
     * @param integer $policy
     */
    public function setChangeTrackingPolicy($policy)
    {
        $this->changeTrackingPolicy = $policy;
    }

    /**
     * Whether the change tracking policy of this class is "deferred explicit".
     *
     * @return boolean
     */
    public function isChangeTrackingDeferredExplicit()
    {
        return $this->changeTrackingPolicy == self::CHANGETRACKING_DEFERRED_EXPLICIT;
    }

    /**
     * Whether the change tracking policy of this class is "deferred implicit".
     *
     * @return boolean
     */
    public function isChangeTrackingDeferredImplicit()
    {
        return $this->changeTrackingPolicy == self::CHANGETRACKING_DEFERRED_IMPLICIT;
    }

    /**
     * Whether the change tracking policy of this class is "notify".
     *
     * @return boolean
     */
    public function isChangeTrackingNotify()
    {
        return $this->changeTrackingPolicy == self::CHANGETRACKING_NOTIFY;
    }

    /**
     * Checks whether a field is part of the identifier/primary key field(s).
     *
     * @param string $fieldName  The field name
     * @return boolean  TRUE if the field is part of the table identifier/primary key field(s),
     *                  FALSE otherwise.
     */
    public function isIdentifier($fieldName)
    {
        if ( ! $this->isIdentifierComposite) {
            return $fieldName === $this->identifier[0];
        }
        return in_array($fieldName, $this->identifier);
    }

    /**
     * Check if the field is unique.
     *
     * @param string $fieldName  The field name
     * @return boolean  TRUE if the field is unique, FALSE otherwise.
     */
    public function isUniqueField($fieldName)
    {
        $mapping = $this->getFieldMapping($fieldName);
        if ($mapping !== false) {
            return isset($mapping['unique']) && $mapping['unique'] == true;
        }
        return false;
    }

    /**
     * Check if the field is not null.
     *
     * @param string $fieldName  The field name
     * @return boolean  TRUE if the field is not null, FALSE otherwise.
     */
    public function isNullable($fieldName)
    {
        $mapping = $this->getFieldMapping($fieldName);
        if ($mapping !== false) {
            return isset($mapping['nullable']) && $mapping['nullable'] == true;
        }
        return false;
    }

    /**
     * Gets a column name for a field name.
     * If the column name for the field cannot be found, the given field name
     * is returned.
     *
     * @param string $fieldName The field name.
     * @return string  The column name.
     */
    public function getColumnName($fieldName)
    {
        return isset($this->columnNames[$fieldName]) ?
                $this->columnNames[$fieldName] : $fieldName;
    }

    /**
     * Gets the mapping of a (regular) field that holds some data but not a
     * reference to another object.
     *
     * @param string $fieldName  The field name.
     * @return array  The field mapping.
     */
    public function getFieldMapping($fieldName)
    {
        if ( ! isset($this->fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->name, $fieldName);
        }
        return $this->fieldMappings[$fieldName];
    }

    /**
     * Gets the mapping of an association.
     *
     * @see ClassMetadataInfo::$associationMappings
     * @param string $fieldName  The field name that represents the association in
     *                           the object model.
     * @return array The mapping.
     */
    public function getAssociationMapping($fieldName)
    {
        if ( ! isset($this->associationMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->name, $fieldName);
        }
        return $this->associationMappings[$fieldName];
    }

    /**
     * Gets all association mappings of the class.
     *
     * @return array
     */
    public function getAssociationMappings()
    {
        return $this->associationMappings;
    }

    /**
     * Gets the field name for a column name.
     * If no field name can be found the column name is returned.
     *
     * @param string $columnName    column name
     * @return string               column alias
     */
    public function getFieldName($columnName)
    {
        return isset($this->fieldNames[$columnName]) ?
                $this->fieldNames[$columnName] : $columnName;
    }

    /**
     * Validates & completes the given field mapping.
     *
     * @param array $mapping  The field mapping to validated & complete.
     * @return array  The validated and completed field mapping.
     */
    protected function _validateAndCompleteFieldMapping(array &$mapping)
    {
        // Check mandatory fields
        if ( ! isset($mapping['fieldName']) || strlen($mapping['fieldName']) == 0) {
            throw MappingException::missingFieldName($this->name);
        }
        if ( ! isset($mapping['type'])) {
            // Default to string
            $mapping['type'] = 'string';
        }

        // Complete fieldName and columnName mapping
        if ( ! isset($mapping['columnName'])) {
            $mapping['columnName'] = $mapping['fieldName'];
        } else {
            if ($mapping['columnName'][0] == '`') {
                $mapping['columnName'] = trim($mapping['columnName'], '`');
                $mapping['quoted'] = true;
            }
        }

        $this->columnNames[$mapping['fieldName']] = $mapping['columnName'];
        if (isset($this->fieldNames[$mapping['columnName']]) || ($this->discriminatorColumn != null && $this->discriminatorColumn['name'] == $mapping['columnName'])) {
            throw MappingException::duplicateColumnName($this->name, $mapping['columnName']);
        }

        $this->fieldNames[$mapping['columnName']] = $mapping['fieldName'];

        // Complete id mapping
        if (isset($mapping['id']) && $mapping['id'] === true) {
            if ($this->versionField == $mapping['fieldName']) {
                throw MappingException::cannotVersionIdField($this->name, $mapping['fieldName']);
            }

            if ( ! in_array($mapping['fieldName'], $this->identifier)) {
                $this->identifier[] = $mapping['fieldName'];
            }
            // Check for composite key
            if ( ! $this->isIdentifierComposite && count($this->identifier) > 1) {
                $this->isIdentifierComposite = true;
            }
        }
    }

    /**
     * Validates & completes the basic mapping information that is common to all
     * association mappings (one-to-one, many-ot-one, one-to-many, many-to-many).
     *
     * @param array $mapping The mapping.
     * @return array The updated mapping.
     * @throws MappingException If something is wrong with the mapping.
     */
    protected function _validateAndCompleteAssociationMapping(array $mapping)
    {
        if ( ! isset($mapping['mappedBy'])) {
            $mapping['mappedBy'] = null;
        }
        if ( ! isset($mapping['inversedBy'])) {
            $mapping['inversedBy'] = null;
        }
        $mapping['isOwningSide'] = true; // assume owning side until we hit mappedBy

        // If targetEntity is unqualified, assume it is in the same namespace as
        // the sourceEntity.
        $mapping['sourceEntity'] = $this->name;
        if (isset($mapping['targetEntity']) && strpos($mapping['targetEntity'], '\\') === false
                && strlen($this->namespace) > 0) {
            $mapping['targetEntity'] = $this->namespace . '\\' . $mapping['targetEntity'];
        }

        // Mandatory: fieldName, targetEntity
        if ( ! isset($mapping['fieldName']) || strlen($mapping['fieldName']) == 0) {
            throw MappingException::missingFieldName($this->name);
        }
        if ( ! isset($mapping['targetEntity'])) {
            throw MappingException::missingTargetEntity($mapping['fieldName']);
        }
        
        // Mandatory and optional attributes for either side
        if ( ! $mapping['mappedBy']) {
            if (isset($mapping['joinTable']) && $mapping['joinTable']) {
                if (isset($mapping['joinTable']['name']) && $mapping['joinTable']['name'][0] == '`') {
                    $mapping['joinTable']['name'] = trim($mapping['joinTable']['name'], '`');
                    $mapping['joinTable']['quoted'] = true;
                }
            }
        } else {
            $mapping['isOwningSide'] = false;
        }
        
        // Fetch mode. Default fetch mode to LAZY, if not set.
        if ( ! isset($mapping['fetch'])) {
            $mapping['fetch'] = self::FETCH_LAZY;
        }

        // Cascades
        $cascades = isset($mapping['cascade']) ? $mapping['cascade'] : array();
        if (in_array('all', $cascades)) {
            $cascades = array(
               'remove',
               'persist',
               'refresh',
               'merge',
               'detach'
            );
        }
        $mapping['cascade'] = $cascades;
        $mapping['isCascadeRemove'] = in_array('remove',  $cascades);
        $mapping['isCascadePersist'] = in_array('persist',  $cascades);
        $mapping['isCascadeRefresh'] = in_array('refresh',  $cascades);
        $mapping['isCascadeMerge'] = in_array('merge',  $cascades);
        $mapping['isCascadeDetach'] = in_array('detach',  $cascades);
        
        return $mapping;
    }

    /**
     * Validates & completes a one-to-one association mapping.
     *
     * @param array $mapping  The mapping to validate & complete.
     * @return array The validated & completed mapping.
     * @override
     */
    protected function _validateAndCompleteOneToOneMapping(array $mapping)
    {
        $mapping = $this->_validateAndCompleteAssociationMapping($mapping);
        
        if (isset($mapping['joinColumns']) && $mapping['joinColumns']) {
            $mapping['isOwningSide'] = true;
        }
        
        if ($mapping['isOwningSide']) {
            if ( ! isset($mapping['joinColumns']) || ! $mapping['joinColumns']) {
                // Apply default join column
                $mapping['joinColumns'] = array(array(
                    'name' => $mapping['fieldName'] . '_id',
                    'referencedColumnName' => 'id'
                ));
            }
            foreach ($mapping['joinColumns'] as $key => &$joinColumn) {
                if ($mapping['type'] === self::ONE_TO_ONE) {
                    $joinColumn['unique'] = true;
                }
                if (empty($joinColumn['name'])) {
                    $joinColumn['name'] = $mapping['fieldName'] . '_id';
                }
                if (empty($joinColumn['referencedColumnName'])) {
                    $joinColumn['referencedColumnName'] = 'id';
                }
                $mapping['sourceToTargetKeyColumns'][$joinColumn['name']] = $joinColumn['referencedColumnName'];
                $mapping['joinColumnFieldNames'][$joinColumn['name']] = isset($joinColumn['fieldName'])
                        ? $joinColumn['fieldName'] : $joinColumn['name'];
            }
            $mapping['targetToSourceKeyColumns'] = array_flip($mapping['sourceToTargetKeyColumns']);
        }

        //TODO: if orphanRemoval, cascade=remove is implicit!
        $mapping['orphanRemoval'] = isset($mapping['orphanRemoval']) ?
                (bool) $mapping['orphanRemoval'] : false;

        return $mapping;
    }

    /**
     * Validates and completes the mapping.
     *
     * @param array $mapping The mapping to validate and complete.
     * @return array The validated and completed mapping.
     * @override
     */
    protected function _validateAndCompleteOneToManyMapping(array $mapping)
    {
        $mapping = $this->_validateAndCompleteAssociationMapping($mapping);

        // OneToMany-side MUST be inverse (must have mappedBy)
        if ( ! isset($mapping['mappedBy'])) {
            throw MappingException::oneToManyRequiresMappedBy($mapping['fieldName']);
        }
        
        //TODO: if orphanRemoval, cascade=remove is implicit!
        $mapping['orphanRemoval'] = isset($mapping['orphanRemoval']) ?
                (bool) $mapping['orphanRemoval'] : false;

        if (isset($mapping['orderBy'])) {
            if ( ! is_array($mapping['orderBy'])) {
                throw new \InvalidArgumentException("'orderBy' is expected to be an array, not ".gettype($mapping['orderBy']));
            }
        }
        
        return $mapping;
    }

    protected function _validateAndCompleteManyToManyMapping(array $mapping)
    {
        $mapping = $this->_validateAndCompleteAssociationMapping($mapping);
        if ($mapping['isOwningSide']) {
            if (strpos($mapping['sourceEntity'], '\\') !== false) {
                $sourceShortName = strtolower(substr($mapping['sourceEntity'], strrpos($mapping['sourceEntity'], '\\') + 1));
            } else {
                $sourceShortName = strtolower($mapping['sourceEntity']);
            }
            if (strpos($mapping['targetEntity'], '\\') !== false) {
                $targetShortName = strtolower(substr($mapping['targetEntity'], strrpos($mapping['targetEntity'], '\\') + 1));
            } else {
                $targetShortName = strtolower($mapping['targetEntity']);
            }
            
            // owning side MUST have a join table
            if ( ! isset($mapping['joinTable']['name'])) {
                $mapping['joinTable']['name'] = $sourceShortName .'_' . $targetShortName;
            }
            if ( ! isset($mapping['joinTable']['joinColumns'])) {
                $mapping['joinTable']['joinColumns'] = array(array(
                        'name' => $sourceShortName . '_id',
                        'referencedColumnName' => 'id',
                        'onDelete' => 'CASCADE'));
            }
            if ( ! isset($mapping['joinTable']['inverseJoinColumns'])) {
                $mapping['joinTable']['inverseJoinColumns'] = array(array(
                        'name' => $targetShortName . '_id',
                        'referencedColumnName' => 'id',
                        'onDelete' => 'CASCADE'));
            }

            foreach ($mapping['joinTable']['joinColumns'] as &$joinColumn) {
                if (empty($joinColumn['name'])) {
                    $joinColumn['name'] = $sourceShortName . '_id';
                }
                if (empty($joinColumn['referencedColumnName'])) {
                    $joinColumn['referencedColumnName'] = 'id';
                }
                if (isset($joinColumn['onDelete']) && strtolower($joinColumn['onDelete']) == 'cascade') {
                    $mapping['isOnDeleteCascade'] = true;
                }
                $mapping['relationToSourceKeyColumns'][$joinColumn['name']] = $joinColumn['referencedColumnName'];
                $mapping['joinTableColumns'][] = $joinColumn['name'];
            }

            foreach ($mapping['joinTable']['inverseJoinColumns'] as &$inverseJoinColumn) {
                if (empty($inverseJoinColumn['name'])) {
                    $inverseJoinColumn['name'] = $targetShortName . '_id';
                }
                if (empty($inverseJoinColumn['referencedColumnName'])) {
                    $inverseJoinColumn['referencedColumnName'] = 'id';
                }
                if (isset($inverseJoinColumn['onDelete']) && strtolower($inverseJoinColumn['onDelete']) == 'cascade') {
                    $mapping['isOnDeleteCascade'] = true;
                }
                $mapping['relationToTargetKeyColumns'][$inverseJoinColumn['name']] = $inverseJoinColumn['referencedColumnName'];
                $mapping['joinTableColumns'][] = $inverseJoinColumn['name'];
            }
        }

        if (isset($mapping['orderBy'])) {
            if ( ! is_array($mapping['orderBy'])) {
                throw new \InvalidArgumentException("'orderBy' is expected to be an array, not ".gettype($mapping['orderBy']));
            }
        }

        return $mapping;
    }

    /**
     * Gets the identifier (primary key) field names of the class.
     *
     * @return mixed
     */
    public function getIdentifierFieldNames()
    {
        return $this->identifier;
    }

    /**
     * Gets the name of the single id field. Note that this only works on
     * entity classes that have a single-field pk.
     *
     * @return string
     * @throws MappingException If the class has a composite primary key.
     */
    public function getSingleIdentifierFieldName()
    {
        if ($this->isIdentifierComposite) {
            throw MappingException::singleIdNotAllowedOnCompositePrimaryKey($this->name);
        }
        return $this->identifier[0];
    }

    /**
     * Gets the column name of the single id column. Note that this only works on
     * entity classes that have a single-field pk.
     *
     * @return string
     * @throws MappingException If the class has a composite primary key.
     */
    public function getSingleIdentifierColumnName()
    {
        return $this->getColumnName($this->getSingleIdentifierFieldName());
    }

    /**
     * INTERNAL:
     * Sets the mapped identifier/primary key fields of this class.
     * Mainly used by the ClassMetadataFactory to assign inherited identifiers.
     *
     * @param array $identifier
     */
    public function setIdentifier(array $identifier)
    {
        $this->identifier = $identifier;
        $this->isIdentifierComposite = (count($this->identifier) > 1);
    }

    /**
     * Checks whether the class has a (mapped) field with a certain name.
     *
     * @return boolean
     */
    public function hasField($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]);
    }

    /**
     * Gets an array containing all the column names.
     *
     * @return array
     */
    public function getColumnNames(array $fieldNames = null)
    {
        if ($fieldNames === null) {
            return array_keys($this->fieldNames);
        } else {
            $columnNames = array();
            foreach ($fieldNames as $fieldName) {
                $columnNames[] = $this->getColumnName($fieldName);
            }
            return $columnNames;
        }
    }

    /**
     * Returns an array with all the identifier column names.
     *
     * @return array
     */
    public function getIdentifierColumnNames()
    {
        if ($this->isIdentifierComposite) {
            $columnNames = array();
            foreach ($this->identifier as $idField) {
                $columnNames[] = $this->fieldMappings[$idField]['columnName'];
            }
            return $columnNames;
        } else {
            return array($this->fieldMappings[$this->identifier[0]]['columnName']);
        }
    }

    /**
     * Sets the type of Id generator to use for the mapped class.
     */
    public function setIdGeneratorType($generatorType)
    {
        $this->generatorType = $generatorType;
    }

    /**
     * Checks whether the mapped class uses an Id generator.
     *
     * @return boolean TRUE if the mapped class uses an Id generator, FALSE otherwise.
     */
    public function usesIdGenerator()
    {
        return $this->generatorType != self::GENERATOR_TYPE_NONE;
    }

    /**
     * @return boolean
     */
    public function isInheritanceTypeNone()
    {
        return $this->inheritanceType == self::INHERITANCE_TYPE_NONE;
    }

    /**
     * Checks whether the mapped class uses the JOINED inheritance mapping strategy.
     *
     * @return boolean TRUE if the class participates in a JOINED inheritance mapping,
     *                 FALSE otherwise.
     */
    public function isInheritanceTypeJoined()
    {
        return $this->inheritanceType == self::INHERITANCE_TYPE_JOINED;
    }

    /**
     * Checks whether the mapped class uses the SINGLE_TABLE inheritance mapping strategy.
     *
     * @return boolean TRUE if the class participates in a SINGLE_TABLE inheritance mapping,
     *                 FALSE otherwise.
     */
    public function isInheritanceTypeSingleTable()
    {
        return $this->inheritanceType == self::INHERITANCE_TYPE_SINGLE_TABLE;
    }

    /**
     * Checks whether the mapped class uses the TABLE_PER_CLASS inheritance mapping strategy.
     *
     * @return boolean TRUE if the class participates in a TABLE_PER_CLASS inheritance mapping,
     *                 FALSE otherwise.
     */
    public function isInheritanceTypeTablePerClass()
    {
        return $this->inheritanceType == self::INHERITANCE_TYPE_TABLE_PER_CLASS;
    }

    /**
     * Checks whether the class uses an identity column for the Id generation.
     *
     * @return boolean TRUE if the class uses the IDENTITY generator, FALSE otherwise.
     */
    public function isIdGeneratorIdentity()
    {
        return $this->generatorType == self::GENERATOR_TYPE_IDENTITY;
    }

    /**
     * Checks whether the class uses a sequence for id generation.
     *
     * @return boolean TRUE if the class uses the SEQUENCE generator, FALSE otherwise.
     */
    public function isIdGeneratorSequence()
    {
        return $this->generatorType == self::GENERATOR_TYPE_SEQUENCE;
    }

    /**
     * Checks whether the class uses a table for id generation.
     *
     * @return boolean  TRUE if the class uses the TABLE generator, FALSE otherwise.
     */
    public function isIdGeneratorTable()
    {
        $this->generatorType == self::GENERATOR_TYPE_TABLE;
    }

    /**
     * Checks whether the class has a natural identifier/pk (which means it does
     * not use any Id generator.
     *
     * @return boolean
     */
    public function isIdentifierNatural()
    {
        return $this->generatorType == self::GENERATOR_TYPE_NONE;
    }

    /**
     * Gets the type of a field.
     *
     * @param string $fieldName
     * @return Doctrine\DBAL\Types\Type
     */
    public function getTypeOfField($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]) ?
                $this->fieldMappings[$fieldName]['type'] : null;
    }

    /**
     * Gets the type of a column.
     *
     * @return Doctrine\DBAL\Types\Type
     */
    public function getTypeOfColumn($columnName)
    {
        return $this->getTypeOfField($this->getFieldName($columnName));
    }

    /**
     * Gets the name of the primary table.
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->table['name'];
    }

    /**
     * Gets the table name to use for temporary identifier tables of this class.
     *
     * @return string
     */
    public function getTemporaryIdTableName()
    {
        return $this->table['name'] . '_id_tmp';
    }

    /**
     * Sets the mapped subclasses of this class.
     *
     * @param array $subclasses The names of all mapped subclasses.
     */
    public function setSubclasses(array $subclasses)
    {
        foreach ($subclasses as $subclass) {
            if (strpos($subclass, '\\') === false && strlen($this->namespace)) {
                $this->subClasses[] = $this->namespace . '\\' . $subclass;
            } else {
                $this->subClasses[] = $subclass;
            }
        }
    }

    /**
     * Sets the parent class names.
     * Assumes that the class names in the passed array are in the order:
     * directParent -> directParentParent -> directParentParentParent ... -> root.
     */
    public function setParentClasses(array $classNames)
    {
        $this->parentClasses = $classNames;
        if (count($classNames) > 0) {
            $this->rootEntityName = array_pop($classNames);
        }
    }

    /**
     * Sets the inheritance type used by the class and it's subclasses.
     *
     * @param integer $type
     */
    public function setInheritanceType($type)
    {
        if ( ! $this->_isInheritanceType($type)) {
            throw MappingException::invalidInheritanceType($this->name, $type);
        }
        $this->inheritanceType = $type;
    }

    /**
     * Checks whether a mapped field is inherited from an entity superclass.
     *
     * @return boolean TRUE if the field is inherited, FALSE otherwise.
     */
    public function isInheritedField($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]['inherited']);
    }

    /**
     * Checks whether a mapped association field is inherited from a superclass.
     *
     * @param string $fieldName
     * @return boolean TRUE if the field is inherited, FALSE otherwise.
     */
    public function isInheritedAssociation($fieldName)
    {
        return isset($this->associationMappings[$fieldName]['inherited']);
    }

    /**
     * Sets the name of the primary table the class is mapped to.
     *
     * @param string $tableName The table name.
     * @deprecated Use {@link setPrimaryTable}.
     */
    public function setTableName($tableName)
    {
        $this->table['name'] = $tableName;
    }

    /**
     * Sets the primary table definition. The provided array supports the
     * following structure:
     *
     * name => <tableName> (optional, defaults to class name)
     * indexes => array of indexes (optional)
     * uniqueConstraints => array of constraints (optional)
     *
     * If a key is omitted, the current value is kept.
     *
     * @param array $table The table description.
     */
    public function setPrimaryTable(array $table)
    {
        if (isset($table['name'])) {
            if ($table['name'][0] == '`') {
                $this->table['name'] = trim($table['name'], '`');
                $this->table['quoted'] = true;
            } else {
                $this->table['name'] = $table['name'];
            }
        }
        if (isset($table['indexes'])) {
            $this->table['indexes'] = $table['indexes'];
        }
        if (isset($table['uniqueConstraints'])) {
            $this->table['uniqueConstraints'] = $table['uniqueConstraints'];
        }
    }

    /**
     * Checks whether the given type identifies an inheritance type.
     *
     * @param integer $type
     * @return boolean TRUE if the given type identifies an inheritance type, FALSe otherwise.
     */
    private function _isInheritanceType($type)
    {
        return $type == self::INHERITANCE_TYPE_NONE ||
                $type == self::INHERITANCE_TYPE_SINGLE_TABLE ||
                $type == self::INHERITANCE_TYPE_JOINED ||
                $type == self::INHERITANCE_TYPE_TABLE_PER_CLASS;
    }

    /**
     * Adds a mapped field to the class.
     *
     * @param array $mapping The field mapping.
     */
    public function mapField(array $mapping)
    {
        $this->_validateAndCompleteFieldMapping($mapping);
        if (isset($this->fieldMappings[$mapping['fieldName']]) || isset($this->associationMappings[$mapping['fieldName']])) {
            throw MappingException::duplicateFieldMapping($this->name, $mapping['fieldName']);
        }
        $this->fieldMappings[$mapping['fieldName']] = $mapping;
    }

    /**
     * INTERNAL:
     * Adds an association mapping without completing/validating it.
     * This is mainly used to add inherited association mappings to derived classes.
     *
     * @param AssociationMapping $mapping
     * @param string $owningClassName The name of the class that defined this mapping.
     */
    public function addInheritedAssociationMapping(array $mapping/*, $owningClassName = null*/)
    {
        if (isset($this->associationMappings[$mapping['fieldName']])) {
            throw MappingException::duplicateAssociationMapping($this->name, $mapping['fieldName']);
        }
        $this->associationMappings[$mapping['fieldName']] = $mapping;
    }

    /**
     * INTERNAL:
     * Adds a field mapping without completing/validating it.
     * This is mainly used to add inherited field mappings to derived classes.
     *
     * @param array $mapping
     * @todo Rename: addInheritedFieldMapping
     */
    public function addInheritedFieldMapping(array $fieldMapping)
    {
        $this->fieldMappings[$fieldMapping['fieldName']] = $fieldMapping;
        $this->columnNames[$fieldMapping['fieldName']] = $fieldMapping['columnName'];
        $this->fieldNames[$fieldMapping['columnName']] = $fieldMapping['fieldName'];
    }

    /**
     * Adds a one-to-one mapping.
     *
     * @param array $mapping The mapping.
     */
    public function mapOneToOne(array $mapping)
    {
        $mapping['type'] = self::ONE_TO_ONE;
        $mapping = $this->_validateAndCompleteOneToOneMapping($mapping);
        $this->_storeAssociationMapping($mapping);
    }

    /**
     * Adds a one-to-many mapping.
     *
     * @param array $mapping The mapping.
     */
    public function mapOneToMany(array $mapping)
    {
        $mapping['type'] = self::ONE_TO_MANY;
        $mapping = $this->_validateAndCompleteOneToManyMapping($mapping);
        $this->_storeAssociationMapping($mapping);
    }

    /**
     * Adds a many-to-one mapping.
     *
     * @param array $mapping The mapping.
     */
    public function mapManyToOne(array $mapping)
    {
        $mapping['type'] = self::MANY_TO_ONE;
        // A many-to-one mapping is essentially a one-one backreference
        $mapping = $this->_validateAndCompleteOneToOneMapping($mapping);
        $this->_storeAssociationMapping($mapping);
    }

    /**
     * Adds a many-to-many mapping.
     *
     * @param array $mapping The mapping.
     */
    public function mapManyToMany(array $mapping)
    {
        $mapping['type'] = self::MANY_TO_MANY;
        $mapping = $this->_validateAndCompleteManyToManyMapping($mapping);
        $this->_storeAssociationMapping($mapping);
    }

    /**
     * Stores the association mapping.
     *
     * @param AssociationMapping $assocMapping
     */
    protected function _storeAssociationMapping(array $assocMapping)
    {
        $sourceFieldName = $assocMapping['fieldName'];
        if (isset($this->fieldMappings[$sourceFieldName]) || isset($this->associationMappings[$sourceFieldName])) {
            throw MappingException::duplicateFieldMapping($this->name, $sourceFieldName);
        }
        $this->associationMappings[$sourceFieldName] = $assocMapping;
    }

    /**
     * Registers a custom repository class for the entity class.
     *
     * @param string $mapperClassName  The class name of the custom mapper.
     */
    public function setCustomRepositoryClass($repositoryClassName)
    {
        $this->customRepositoryClassName = $repositoryClassName;
    }

    /**
     * Dispatches the lifecycle event of the given entity to the registered
     * lifecycle callbacks and lifecycle listeners.
     *
     * @param string $event The lifecycle event.
     * @param Entity $entity The Entity on which the event occured.
     */
    public function invokeLifecycleCallbacks($lifecycleEvent, $entity)
    {
        foreach ($this->lifecycleCallbacks[$lifecycleEvent] as $callback) {
            $entity->$callback();
        }
    }

    /**
     * Whether the class has any attached lifecycle listeners or callbacks for a lifecycle event.
     *
     * @param string $lifecycleEvent
     * @return boolean
     */
    public function hasLifecycleCallbacks($lifecycleEvent)
    {
        return isset($this->lifecycleCallbacks[$lifecycleEvent]);
    }

    /**
     * Gets the registered lifecycle callbacks for an event.
     *
     * @param string $event
     * @return array
     */
    public function getLifecycleCallbacks($event)
    {
        return isset($this->lifecycleCallbacks[$event]) ? $this->lifecycleCallbacks[$event] : array();
    }

    /**
     * Adds a lifecycle callback for entities of this class.
     *
     * Note: If the same callback is registered more than once, the old one
     * will be overridden.
     *
     * @param string $callback
     * @param string $event
     */
    public function addLifecycleCallback($callback, $event)
    {
        $this->lifecycleCallbacks[$event][] = $callback;
    }

    /**
     * Sets the lifecycle callbacks for entities of this class.
     * Any previously registered callbacks are overwritten.
     *
     * @param array $callbacks
     */
    public function setLifecycleCallbacks(array $callbacks)
    {
        $this->lifecycleCallbacks = $callbacks;
    }

    /**
     * Sets the discriminator column definition.
     *
     * @param array $columnDef
     * @see getDiscriminatorColumn()
     */
    public function setDiscriminatorColumn($columnDef)
    {
        if ($columnDef !== null) {
            if (isset($this->fieldNames[$columnDef['name']])) {
                throw MappingException::duplicateColumnName($this->name, $columnDef['name']);
            }

            if ( ! isset($columnDef['name'])) {
                throw MappingException::nameIsMandatoryForDiscriminatorColumns($this->name, $columnDef);
            }
            if ( ! isset($columnDef['fieldName'])) {
                $columnDef['fieldName'] = $columnDef['name'];
            }
            if ( ! isset($columnDef['type'])) {
                $columnDef['type'] = "string";
            }
            if (in_array($columnDef['type'], array("boolean", "array", "object", "datetime", "time", "date"))) {
                throw MappingException::invalidDiscriminatorColumnType($this->name, $columnDef['type']);
            }

            $this->discriminatorColumn = $columnDef;
        }
    }

    /**
     * Sets the discriminator values used by this class.
     * Used for JOINED and SINGLE_TABLE inheritance mapping strategies.
     *
     * @param array $map
     */
    public function setDiscriminatorMap(array $map)
    {
        foreach ($map as $value => $className) {
            if (strpos($className, '\\') === false && strlen($this->namespace)) {
                $className = $this->namespace . '\\' . $className;
            }
            $this->discriminatorMap[$value] = $className;
            if ($this->name == $className) {
                $this->discriminatorValue = $value;
            } else {
                if ( ! class_exists($className)) {
                    throw MappingException::invalidClassInDiscriminatorMap($className, $this->name);
                }
                if (is_subclass_of($className, $this->name)) {
                    $this->subClasses[] = $className;
                }
            }
        }
    }

    /**
     * Checks whether the class has a mapped association with the given field name.
     *
     * @param string $fieldName
     * @return boolean
     */
    public function hasAssociation($fieldName)
    {
        return isset($this->associationMappings[$fieldName]);
    }

    /**
     * Checks whether the class has a mapped association for the specified field
     * and if yes, checks whether it is a single-valued association (to-one).
     *
     * @param string $fieldName
     * @return boolean TRUE if the association exists and is single-valued, FALSE otherwise.
     */
    public function isSingleValuedAssociation($fieldName)
    {
        return isset($this->associationMappings[$fieldName]) &&
                ($this->associationMappings[$fieldName]['type'] & self::TO_ONE);
    }

    /**
     * Checks whether the class has a mapped association for the specified field
     * and if yes, checks whether it is a collection-valued association (to-many).
     *
     * @param string $fieldName
     * @return boolean TRUE if the association exists and is collection-valued, FALSE otherwise.
     */
    public function isCollectionValuedAssociation($fieldName)
    {
        return isset($this->associationMappings[$fieldName]) &&
                ! ($this->associationMappings[$fieldName]['type'] & self::TO_ONE);
    }

    /**
     * Sets the ID generator used to generate IDs for instances of this class.
     *
     * @param AbstractIdGenerator $generator
     */
    public function setIdGenerator($generator)
    {
        $this->idGenerator = $generator;
    }

    /**
     * Sets the definition of the sequence ID generator for this class.
     *
     * The definition must have the following structure:
     * <code>
     * array(
     *     'sequenceName' => 'name',
     *     'allocationSize' => 20,
     *     'initialValue' => 1
     * )
     * </code>
     *
     * @param array $definition
     */
    public function setSequenceGeneratorDefinition(array $definition)
    {
        $this->sequenceGeneratorDefinition = $definition;
    }

    /**
     * Sets the version field mapping used for versioning. Sets the default
     * value to use depending on the column type.
     *
     * @param array $mapping   The version field mapping array
     */
    public function setVersionMapping(array &$mapping)
    {
        $this->isVersioned = true;
        $this->versionField = $mapping['fieldName'];

        if ( ! isset($mapping['default'])) {
            if ($mapping['type'] == 'integer') {
                $mapping['default'] = 1;
            } else if ($mapping['type'] == 'datetime') {
                $mapping['default'] = 'CURRENT_TIMESTAMP';
            } else {
                throw MappingException::unsupportedOptimisticLockingType($this->name, $mapping['fieldName'], $mapping['type']);
            }
        }
    }

    /**
     * Sets whether this class is to be versioned for optimistic locking.
     *
     * @param boolean $bool
     */
    public function setVersioned($bool)
    {
        $this->isVersioned = $bool;
    }

    /**
     * Sets the name of the field that is to be used for versioning if this class is
     * versioned for optimistic locking.
     *
     * @param string $versionField
     */
    public function setVersionField($versionField)
    {
        $this->versionField = $versionField;
    }
}
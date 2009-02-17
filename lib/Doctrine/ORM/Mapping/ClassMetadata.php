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

use \ReflectionClass;
use Doctrine\Common\DoctrineException;

/**
 * A <tt>ClassMetadata</tt> instance holds all the ORM metadata of an entity and
 * it's associations. It is the backbone of Doctrine's metadata mapping.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
final class ClassMetadata
{
    /* The inheritance mapping types */
    /**
     * NONE means the class does not participate in an inheritance hierarchy
     * and therefore does not need an inheritance mapping type.
     */
    const INHERITANCE_TYPE_NONE = 'none';
    /**
     * JOINED means the class will be persisted according to the rules of
     * <tt>Class Table Inheritance</tt>.
     */
    const INHERITANCE_TYPE_JOINED = 'joined';
    /**
     * SINGLE_TABLE means the class will be persisted according to the rules of
     * <tt>Single Table Inheritance</tt>.
     */
    const INHERITANCE_TYPE_SINGLE_TABLE = 'singleTable';
    /**
     * TABLE_PER_CLASS means the class will be persisted according to the rules
     * of <tt>Concrete Table Inheritance</tt>.
     */
    const INHERITANCE_TYPE_TABLE_PER_CLASS = 'tablePerClass';
    
    /* The Id generator types. */
    /**
     * AUTO means the generator type will depend on what the used platform prefers.
     * Offers full portability.
     */
    const GENERATOR_TYPE_AUTO = 'auto';
    /**
     * SEQUENCE means a separate sequence object will be used. Platforms that do
     * not have native sequence support may emulate it. Full portability is currently
     * not guaranteed.
     */
    const GENERATOR_TYPE_SEQUENCE = 'sequence';
    /**
     * TABLE means a separate table is used for id generation.
     * Offers full portability.
     */
    const GENERATOR_TYPE_TABLE = 'table';
    /**
     * IDENTITY means an identity column is used for id generation. The database
     * will fill in the id column on insertion. Platforms that do not support
     * native identity columns may emulate them. Full portability is currently
     * not guaranteed.
     */
    const GENERATOR_TYPE_IDENTITY = 'identity';
    /**
     * NONE means the class does not have a generated id. That means the class
     * must have a natural id.
     */
    const GENERATOR_TYPE_NONE = 'none';

    /**
     * The name of the entity class.
     */
    private $_entityName;

    /**
     * The namespace the entity class is contained in.
     *
     * @var string
     */
    private $_namespace;

    /**
     * The name of the entity class that is at the root of the entity inheritance
     * hierarchy. If the entity is not part of an inheritance hierarchy this is the same
     * as $_entityName.
     *
     * @var string
     */
    private $_rootEntityName;

    /**
     * The name of the custom repository class used for the entity class.
     * (Optional).
     *
     * @var string
     */
    private $_customRepositoryClassName;

    /**
     * The names of the parent classes (ancestors).
     * 
     * @var array
     */
    private $_parentClasses = array();

    /**
     * The names of all subclasses.
     * 
     * @var array
     */
    private $_subClasses = array();

    /**
     * The field names of all fields that are part of the identifier/primary key
     * of the mapped entity class.
     *
     * @var array
     */
    private $_identifier = array();
    
    /**
     * The inheritance mapping type used by the class.
     *
     * @var integer
     */
    private $_inheritanceType = self::INHERITANCE_TYPE_NONE;
    
    /**
     * The Id generator type used by the class.
     *
     * @var string
     */
    private $_generatorType = self::GENERATOR_TYPE_NONE;
    
    /**
     * The field mappings of the class.
     * Keys are field names and values are mapping definitions.
     *
     * The mapping definition array has the following values:
     * 
     * - <b>fieldName</b> (string)
     * The name of the field in the Entity. 
     * 
     * - <b>type</b> (object Doctrine\DBAL\Types\* or custom type)
     * The type of the column. Can be one of Doctrine's portable types
     * or a custom type.
     * 
     * - <b>columnName</b> (string, optional)
     * The column name. Optional. Defaults to the field name.
     * 
     * - <b>length</b> (integer, optional)
     * The database length of the column. Optional. Default value taken from
     * the type.
     * 
     * - <b>id</b> (boolean, optional)
     * Marks the field as the primary key of the Entity. Multiple fields of an
     * entity can have the id attribute, forming a composite key.
     * 
     * - <b>idGenerator</b> (string, optional)
     * Either: idGenerator => 'nameOfGenerator', usually only for TABLE/SEQUENCE generators
     * Or: idGenerator => 'identity' or 'auto' or 'table' or 'sequence'
     * Note that 'auto', 'table', 'sequence' and 'identity' are reserved names and
     * therefore cant be used as a generator name!
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
     * - <b>index (string, optional, schema-only)</b>
     * Whether an index should be generated for the column.
     * The value specifies the name of the index. To create a multi-column index,
     * just use the same name for several mappings.
     * 
     * - <b>unique (string, optional, schema-only)</b>
     * Whether a unique constraint should be generated for the column.
     * The value specifies the name of the unique constraint. To create a multi-column 
     * unique constraint, just use the same name for several mappings.
     * 
     * - <b>foreignKey (string, optional, schema-only)</b>
     *
     * @var array
     */    
    private $_fieldMappings = array();
    
    /**
     * An array of field names. Used to look up field names from column names.
     * Keys are column names and values are field names.
     * This is the reverse lookup map of $_columnNames.
     *
     * @var array
     */
    private $_fieldNames = array();

    /**
     * An array of column names. Keys are field names and values column names.
     * Used to look up column names from field names.
     * This is the reverse lookup map of $_fieldNames.
     *
     * @var array
     */
    private $_columnNames = array();
    
    /**
     * Map that maps lowercased column names (keys) to field names (values).
     * Mainly used during hydration because Doctrine enforces PDO_CASE_LOWER
     * for portability.
     *
     * @var array
     */
    private $_lcColumnToFieldNames = array();

    /**
     * Whether to automatically OUTER JOIN subtypes when a basetype is queried.
     *
     * <b>This does only apply to the JOINED inheritance mapping strategy.</b>
     *
     * @var boolean
     */
    private $_joinSubclasses = true;

    /**
     * A map that maps discriminator values to class names.
     *
     * <b>This does only apply to the JOINED and SINGLE_TABLE inheritance mapping strategies
     * where a discriminator column is used.</b>
     *
     * @var array
     * @see _discriminatorColumn
     */
    private $_discriminatorMap = array();

    /**
     * The definition of the descriminator column used in JOINED and SINGLE_TABLE
     * inheritance mappings.
     *
     * @var array
     */
    private $_discriminatorColumn;

    /**
     * The primary table definition. The definition is an array with the
     * following entries:
     *
     * name => <tableName>
     * schema => <schemaName>
     * catalog => <catalogName>
     *
     * @var array
     */
    private $_primaryTable;
    
    /**
     * The cached lifecycle listeners. There is only one instance of each
     * listener class at any time.
     *
     * @var array
     */
    private $_lifecycleListenerInstances = array();

    /**
     * The registered lifecycle callbacks for entities of this class.
     *
     * @var array
     */
    private $_lifecycleCallbacks = array();
    
    /**
     * The registered lifecycle listeners for entities of this class.
     *
     * @var array
     */
    private $_lifecycleListeners = array();
    
    /**
     * The association mappings. All mappings, inverse and owning side.
     *
     * @var array
     */
    private $_associationMappings = array();
    
    /**
     * List of inverse association mappings, indexed by mappedBy field name.
     *
     * @var array
     */
    private $_inverseMappings = array();
    
    /**
     * Flag indicating whether the identifier/primary key of the class is composite.
     *
     * @var boolean
     */
    private $_isIdentifierComposite = false;

    /**
     * The ReflectionClass instance of the mapped class.
     *
     * @var ReflectionClass
     */
    private $_reflectionClass;

    /**
     * The ReflectionProperty instances of the mapped class.
     *
     * @var array
     */
    private $_reflectionProperties;

    //private $_insertSql;

    /**
     * Initializes a new ClassMetadata instance that will hold the object-relational mapping
     * metadata of the class with the given name.
     *
     * @param string $entityName  Name of the entity class the new instance is used for.
     */
    public function __construct($entityName)
    {
        $this->_entityName = $entityName;
        $this->_namespace = substr($entityName, 0, strrpos($entityName, '\\'));
        $this->_primaryTable['name'] = str_replace($this->_namespace . '\\', '', $this->_entityName);
        $this->_rootEntityName = $entityName;
        $this->_reflectionClass = new ReflectionClass($entityName);
    }

    /**
     * Gets the ReflectionClass instance of the mapped class.
     *
     * @return ReflectionClass
     */
    public function getReflectionClass()
    {
        return $this->_reflectionClass;
    }

    /**
     * Gets the ReflectionPropertys of the mapped class.
     *
     * @return array An array of ReflectionProperty instances.
     */
    public function getReflectionProperties()
    {
        return $this->_reflectionProperties;
    }

    /**
     * Gets a ReflectionProperty for a specific field of the mapped class.
     *
     * @param string $name
     * @return ReflectionProperty
     */
    public function getReflectionProperty($name)
    {
        return $this->_reflectionProperties[$name];
    }

    /**
     * Gets the ReflectionProperty for the single identifier field.
     *
     * @return ReflectionProperty
     * @throws DoctrineException If the class has a composite identifier.
     */
    public function getSingleIdReflectionProperty()
    {
        if ($this->_isIdentifierComposite) {
            throw new DoctrineException("getSingleIdReflectionProperty called on entity with composite key.");
        }
        return $this->_reflectionProperties[$this->_identifier[0]];
    }

    /**
     * Gets the name of the mapped class.
     *
     * @return string
     */
    public function getClassName()
    {
        return $this->_entityName;
    }

    /**
     * Gets the name of the root class of the mapped entity hierarchy. If the entity described
     * by this ClassMetadata instance is not participating in a hierarchy, this is the same as the
     * name returned by {@link getClassName()}.
     *
     * @return string The name of the root class of the entity hierarchy.
     */
    public function getRootClassName()
    {
        return $this->_rootEntityName;
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
        if ( ! $this->_isIdentifierComposite) {
            return $fieldName === $this->_identifier[0];
        }
        return in_array($fieldName, $this->_identifier);
    }

    /**
     * Checks if the class has a composite identifier.
     *
     * @param string $fieldName  The field name
     * @return boolean  TRUE if the identifier is composite, FALSE otherwise.
     */
    public function isIdentifierComposite()
    {
        return $this->_isIdentifierComposite;
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
    public function isNotNull($fieldName)
    {
        $mapping = $this->getFieldMapping($fieldName);
        if ($mapping !== false) {
            return isset($mapping['nullable']) && $mapping['nullable'] == false;
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
        return isset($this->_columnNames[$fieldName]) ?
                $this->_columnNames[$fieldName] : $fieldName;
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
        if ( ! isset($this->_fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($fieldName);
        }
        return $this->_fieldMappings[$fieldName];
    }
    
    /**
     * Gets the mapping of an association.
     *
     * @param string $fieldName  The field name that represents the association in
     *                           the object model.
     * @return Doctrine\ORM\Mapping\AssociationMapping  The mapping.
     */
    public function getAssociationMapping($fieldName)
    {
        if ( ! isset($this->_associationMappings[$fieldName])) {
            throw MappingException::mappingNotFound($fieldName);
        }
        return $this->_associationMappings[$fieldName];
    }
    
    /**
     * Gets the inverse association mapping for the given fieldname.
     *
     * @param string $mappedByFieldName
     * @return Doctrine\ORM\Mapping\AssociationMapping The mapping.
     */
    public function getInverseAssociationMapping($mappedByFieldName)
    {
        if ( ! isset($this->_inverseMappings[$mappedByFieldName])) {
            throw MappingException::mappingNotFound($mappedByFieldName);
        }
        return $this->_inverseMappings[$mappedByFieldName];
    }
    
    /**
     * Whether the class has an inverse association mapping on the given fieldname.
     *
     * @param string $mappedByFieldName
     * @return boolean
     */
    public function hasInverseAssociationMapping($mappedByFieldName)
    {
        return isset($this->_inverseMappings[$mappedByFieldName]);
    }
    
    /**
     * Gets all association mappings of the class.
     *
     * @return array
     */
    public function getAssociationMappings()
    {
        return $this->_associationMappings;
    }
    
    /**
     * Gets all association mappings of the class.
     * 
     * Alias for getAssociationMappings().
     *
     * @return array
     */
    public function getAssociations()
    {
        return $this->_associationMappings;
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
        return isset($this->_fieldNames[$columnName]) ?
                $this->_fieldNames[$columnName] : $columnName;
    }
    
    /**
     * Gets the field name for a completely lowercased column name.
     * Mainly used during hydration.
     *
     * @param string $lcColumnName The all-lowercase column name.
     * @return string The field name.
     */
    public function getFieldNameForLowerColumnName($lcColumnName)
    {
        return isset($this->_lcColumnToFieldNames[$lcColumnName]) ?
                $this->_lcColumnToFieldNames[$lcColumnName] : $lcColumnName;
    }

    /**
     * Checks whether a specified column name (all lowercase) exists in this class.
     *
     * @param string $lcColumnName
     * @return boolean
     */
    public function hasLowerColumn($lcColumnName)
    {
        return isset($this->_lcColumnToFieldNames[$lcColumnName]);
    }
    
    /**
     * Validates & completes the given field mapping.
     *
     * @param array $mapping  The field mapping to validated & complete.
     * @return array  The validated and completed field mapping.
     */
    private function _validateAndCompleteFieldMapping(array &$mapping)
    {
        // Check mandatory fields
        if ( ! isset($mapping['fieldName'])) {
            throw MappingException::missingFieldName();
        }
        if ( ! isset($mapping['type'])) {
            throw MappingException::missingType();
        }

        if ( ! is_object($mapping['type'])) {
            $mapping['type'] = \Doctrine\DBAL\Types\Type::getType($mapping['type']);
        }

        // Complete fieldName and columnName mapping
        if ( ! isset($mapping['columnName'])) {
            $mapping['columnName'] = $mapping['fieldName'];
        }
        $lcColumnName = strtolower($mapping['columnName']);

        $this->_columnNames[$mapping['fieldName']] = $mapping['columnName'];
        $this->_fieldNames[$mapping['columnName']] = $mapping['fieldName'];
        $this->_lcColumnToFieldNames[$lcColumnName] = $mapping['fieldName'];
        
        // Complete id mapping
        if (isset($mapping['id']) && $mapping['id'] === true) {
            if ( ! in_array($mapping['fieldName'], $this->_identifier)) {
                $this->_identifier[] = $mapping['fieldName'];
            }
            if (isset($mapping['idGenerator'])) {
                if ( ! $this->_isIdGeneratorType($mapping['idGenerator'])) {
                    //TODO: check if the idGenerator specifies an existing generator by name
                    throw MappingException::invalidGeneratorType($mapping['idGenerator']);
                } else if (count($this->_identifier) > 1) {
                    throw MappingException::generatorNotAllowedWithCompositeId();
                }
                $this->_generatorType = $mapping['idGenerator'];
            }
            // TODO: validate/complete 'tableGenerator' and 'sequenceGenerator' mappings
            
            // Check for composite key
            if ( ! $this->_isIdentifierComposite && count($this->_identifier) > 1) {
                $this->_isIdentifierComposite = true;
            }
        }

        // Store ReflectionProperty of mapped field
        $refProp = $this->_reflectionClass->getProperty($mapping['fieldName']);
        $refProp->setAccessible(true);
        $this->_reflectionProperties[$mapping['fieldName']] = $refProp;
    }
    
    /**
     * @todo Implementation of Optimistic Locking.
     */
    public function mapVersionField(array $mapping)
    {
        //...
    }
    
    /**
     * Overrides an existant field mapping.
     * Used i.e. by Entity classes deriving from another Entity class that acts
     * as a mapped superclass to refine the basic mapping.
     *
     * @param array $newMapping
     * @todo Implementation.
     */
    public function overrideFieldMapping(array $newMapping)
    {
        //...
    }
    
    /**
     * Maps an embedded value object.
     *
     * @todo Implementation.
     */
    public function mapEmbeddedValue()
    {
        //...
    }

    /**
     * Gets the identifier (primary key) field names of the class.
     *
     * @return mixed
     * @deprecated Use getIdentifierFieldNames()
     */
    public function getIdentifier()
    {
        return $this->_identifier;
    }

    /**
     * Gets the identifier (primary key) field names of the class.
     *
     * @return mixed
     */
    public function getIdentifierFieldNames()
    {
        return $this->_identifier;
    }
    
    /**
     * Gets the name of the single id field. Note that this only works on
     * entity classes that have a single-field pk.
     *
     * @return string
     */
    public function getSingleIdentifierFieldName()
    {
        if ($this->_isIdentifierComposite) {
            throw new Doctrine_Exception("Calling getSingleIdentifierFieldName "
                    . "on a class that uses a composite identifier is not allowed.");
        }
        return $this->_identifier[0];
    }

    public function setIdentifier(array $identifier)
    {
        $this->_identifier = $identifier;
    }

    /**
     * Checks whether the class has a (mapped) field with a certain name.
     * 
     * @return boolean
     */
    public function hasField($fieldName)
    {
        return isset($this->_columnNames[$fieldName]);
    }
    
    /**
     * Gets all field mappings.
     *
     * @return array
     */
    public function getFieldMappings()
    {
        return $this->_fieldMappings;
    }

    /**
     * Gets an array containing all the column names.
     *
     * @return array
     */
    public function getColumnNames(array $fieldNames = null)
    {
        if ($fieldNames === null) {
            return array_keys($this->_fieldNames);
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
        return $this->getColumnNames((array)$this->getIdentifierFieldNames());
    }

    /**
     * Returns an array containing all the field names.
     *
     * @return array
     */
    public function getFieldNames()
    {
        return array_values($this->_fieldNames);
    }
    
    /**
     * Gets the Id generator type used by the class.
     *
     * @return string
     */
    public function getIdGeneratorType()
    {
        return $this->_generatorType;
    }

    /**
     * Sets the type of Id generator to use for the mapped class.
     */
    public function setIdGeneratorType($generatorType)
    {
        $this->_generatorType = $generatorType;
    }
    
    /**
     * Checks whether the mapped class uses an Id generator.
     *
     * @return boolean  TRUE if the mapped class uses an Id generator, FALSE otherwise.
     */
    public function usesIdGenerator()
    {
        return $this->_generatorType != self::GENERATOR_TYPE_NONE;
    }

    /**
     *
     * @return <type> 
     */
    public function isInheritanceTypeNone()
    {
        return $this->_inheritanceType == self::INHERITANCE_TYPE_NONE;
    }
    
    /**
     * Checks whether the mapped class uses the JOINED inheritance mapping strategy.
     *
     * @return boolean TRUE if the class participates in a JOINED inheritance mapping,
     *                 FALSE otherwise.
     */
    public function isInheritanceTypeJoined()
    {
        return $this->_inheritanceType == self::INHERITANCE_TYPE_JOINED;
    }
    
    /**
     * Checks whether the mapped class uses the SINGLE_TABLE inheritance mapping strategy.
     *
     * @return boolean TRUE if the class participates in a SINGLE_TABLE inheritance mapping,
     *                 FALSE otherwise.
     */
    public function isInheritanceTypeSingleTable()
    {
        return $this->_inheritanceType == self::INHERITANCE_TYPE_SINGLE_TABLE;
    }
    
    /**
     * Checks whether the mapped class uses the TABLE_PER_CLASS inheritance mapping strategy.
     *
     * @return boolean TRUE if the class participates in a TABLE_PER_CLASS inheritance mapping,
     *                 FALSE otherwise.
     */
    public function isInheritanceTypeTablePerClass()
    {
        return $this->_inheritanceType == self::INHERITANCE_TYPE_TABLE_PER_CLASS;
    }
    
    /**
     * Checks whether the class uses an identity column for the Id generation.
     *
     * @return boolean TRUE if the class uses the IDENTITY generator, FALSE otherwise.
     */
    public function isIdGeneratorIdentity()
    {
        return $this->_generatorType == self::GENERATOR_TYPE_IDENTITY;
    }
    
    /**
     * Checks whether the class uses a sequence for id generation.
     *
     * @return boolean TRUE if the class uses the SEQUENCE generator, FALSE otherwise.
     */
    public function isIdGeneratorSequence()
    {
        return $this->_generatorType == self::GENERATOR_TYPE_SEQUENCE;
    }
    
    /**
     * Checks whether the class uses a table for id generation.
     *
     * @return boolean  TRUE if the class uses the TABLE generator, FALSE otherwise.
     */
    public function isIdGeneratorTable()
    {
        $this->_generatorType == self::GENERATOR_TYPE_TABLE;
    }
    
    /**
     * Checks whether the class has a natural identifier/pk (which means it does
     * not use any Id generator.
     *
     * @return boolean
     */
    public function isIdentifierNatural()
    {
        return $this->_generatorType == self::GENERATOR_TYPE_NONE;
    }
    
    /**
     * Gets the type of a field.
     *
     * @param string $fieldName
     * @return Doctrine\DBAL\Types\Type
     */
    public function getTypeOfField($fieldName)
    {
        return isset($this->_fieldMappings[$fieldName]) ?
                $this->_fieldMappings[$fieldName]['type'] : null;
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
     * Gets the (maximum) length of a field.
     */
    public function getFieldLength($fieldName)
    {
        return $this->_fieldMappings[$fieldName]['length'];
    }

    /**
     * Gets the name of the primary table.
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->_primaryTable['name'];
    }

    public function getInheritedFields()
    {

    }

    /**
     * Adds a named query.
     *
     * @param string $name  The name under which the query gets registered.
     * @param string $query The DQL query.
     * @todo Implementation.
     */
    public function addNamedQuery($name, $query)
    {
        //...
    }

    /**
     * Gets the inheritance mapping type used by the mapped class.
     *
     * @return string
     */
    public function getInheritanceType()
    {
        return $this->_inheritanceType;
    }

    /**
     * Sets the subclasses of the mapped class.
     * 
     * <b>All entity classes that participate in a hierarchy and have subclasses
     * need to declare them this way.</b>
     *
     * @param array $subclasses  The names of all subclasses.
     */
    public function setSubclasses(array $subclasses)
    {
        $this->_subClasses = $subclasses;
    }

    /**
     * Gets the names of all subclasses.
     *
     * @return array  The names of all subclasses.
     */
    public function getSubclasses()
    {
        return $this->_subClasses;
    }

    /**
     * Checks whether the class has any persistent subclasses.
     *
     * @return boolean TRUE if the class has one or more persistent subclasses, FALSE otherwise.
     */
    public function hasSubclasses()
    {
        return ! $this->_subClasses;
    }

    /**
     * Gets the names of all parent classes.
     *
     * @return array  The names of all parent classes.
     */
    public function getParentClasses()
    {
        return $this->_parentClasses;
    }

    /**
     * Sets the parent class names.
     * Assumes that the class names in the passed array are in the order:
     * directParent -> directParentParent -> directParentParentParent ... -> root.
     */
    public function setParentClasses(array $classNames)
    {
        $this->_parentClasses = $classNames;
        if (count($classNames) > 0) {
            $this->_rootEntityName = array_pop($classNames);
        }
    }

    /**
     * Checks whether the class has any persistent parent classes.
     *
     * @return boolean TRUE if the class has one or more persistent parent classes, FALSE otherwise.
     */
    public function hasParentClasses()
    {
        return ! $this->_parentClasses;
    }

    /**
     * Sets the inheritance type used by the class and it's subclasses.
     *
     * @param integer $type
     */
    public function setInheritanceType($type)
    {
        if ( ! $this->_isInheritanceType($type)) {
            throw MappingException::invalidInheritanceType($type);
        }
        $this->_inheritanceType = $type;
    }

    /**
     * Checks whether a mapped field is inherited from a superclass.
     *
     * @return boolean TRUE if the field is inherited, FALSE otherwise.
     */
    public function isInheritedField($fieldName)
    {
        return isset($this->_fieldMappings[$fieldName]['inherited']);
    }

    /**
     * Sets the name of the primary table the class is mapped to.
     *
     * @param string $tableName  The table name.
     * @deprecated
     */
    public function setTableName($tableName)
    {
        $this->_primaryTable['name'] = $tableName;
    }

    /**
     * Sets the primary table definition. The provided array must have th
     * following structure:
     *
     * name => <tableName>
     * schema => <schemaName>
     * catalog => <catalogName>
     *
     * @param array $primaryTableDefinition
     */
    public function setPrimaryTable(array $primaryTableDefinition)
    {
        $this->_primaryTable = $primaryTableDefinition;
    }

    /**
     * Gets the primary table definition.
     *
     * @see setPrimaryTable()
     * @return array
     */
    public function getPrimaryTable()
    {
        return $this->_primaryTable;
    }
    
    /**
     * Checks whether the given type identifies an inheritance type.
     *
     * @param string $type
     * @return boolean
     */
    private function _isInheritanceType($type)
    {
        return $type == self::INHERITANCE_TYPE_NONE ||
                $type == self::INHERITANCE_TYPE_SINGLE_TABLE ||
                $type == self::INHERITANCE_TYPE_JOINED ||
                $type == self::INHERITANCE_TYPE_TABLE_PER_CLASS;
    }
    
    /**
     * Checks whether the given type identifies an id generator type.
     *
     * @param string $type
     * @return boolean
     */
    private function _isIdGeneratorType($type)
    {
        return $type == self::GENERATOR_TYPE_AUTO ||
                $type == self::GENERATOR_TYPE_IDENTITY ||
                $type == self::GENERATOR_TYPE_SEQUENCE ||
                $type == self::GENERATOR_TYPE_TABLE ||
                $type == self::GENERATOR_TYPE_NONE;
    }
    
    /**
     * Makes some automatic additions to the association mapping to make the life
     * easier for the user.
     *
     * @param array $mapping
     * @todo Pass param by ref?
     */
    private function _completeAssociationMapping(array $mapping)
    {
        $mapping['sourceEntity'] = $this->_entityName;
        if (isset($mapping['targetEntity']) && strpos($mapping['targetEntity'], '\\') === false) {
            $mapping['targetEntity'] = $this->_namespace . '\\' . $mapping['targetEntity'];
        }
        return $mapping;
    }

    /**
     * Adds a field mapping.
     *
     * @param array $mapping
     */
    public function mapField(array $mapping)
    {
        $this->_validateAndCompleteFieldMapping($mapping);
        if (isset($this->_fieldMappings[$mapping['fieldName']])) {
            throw MappingException::duplicateFieldMapping();
        }
        $this->_fieldMappings[$mapping['fieldName']] = $mapping;
    }

    /**
     * INTERNAL:
     * Adds an association mapping without completing/validating it.
     * This is mainly used to add inherited association mappings to derived classes.
     *
     * @param AssociationMapping $mapping
     */
    public function addAssociationMapping(AssociationMapping $mapping)
    {
        $this->_storeAssociationMapping($mapping);
    }

    /**
     * Adds a one-to-one mapping.
     * 
     * @param array $mapping The mapping.
     */
    public function mapOneToOne(array $mapping)
    {
        $mapping = $this->_completeAssociationMapping($mapping);
        $oneToOneMapping = new OneToOneMapping($mapping);
        $this->_storeAssociationMapping($oneToOneMapping);
    }

    /**
     * Registers the mapping as an inverse mapping, if it is a mapping on the
     * inverse side of an association mapping.
     *
     * @param AssociationMapping The mapping to register as inverse if it is a mapping
     *      for the inverse side of an association.
     */
    private function _registerMappingIfInverse(AssociationMapping $assoc)
    {
        if ($assoc->isInverseSide()) {
            $this->_inverseMappings[$assoc->getMappedByFieldName()] = $assoc;
        }
    }

    /**
     * Adds a one-to-many mapping.
     * 
     * @param array $mapping The mapping.
     */
    public function mapOneToMany(array $mapping)
    {
        $mapping = $this->_completeAssociationMapping($mapping);
        $oneToManyMapping = new OneToManyMapping($mapping);
        $this->_storeAssociationMapping($oneToManyMapping);
    }

    /**
     * Adds a many-to-one mapping.
     * 
     * @param array $mapping The mapping.
     */
    public function mapManyToOne(array $mapping)
    {
        // A many-to-one mapping is simply a one-one backreference
        $this->mapOneToOne($mapping);
    }

    /**
     * Adds a many-to-many mapping.
     * 
     * @param array $mapping The mapping.
     */
    public function mapManyToMany(array $mapping)
    {
        $mapping = $this->_completeAssociationMapping($mapping);
        $manyToManyMapping = new ManyToManyMapping($mapping);
        $this->_storeAssociationMapping($manyToManyMapping);
    }
    
    /**
     * Stores the association mapping.
     *
     * @param AssociationMapping $assocMapping
     */
    private function _storeAssociationMapping(AssociationMapping $assocMapping)
    {
        $sourceFieldName = $assocMapping->getSourceFieldName();
        if (isset($this->_associationMappings[$sourceFieldName])) {
            throw MappingException::duplicateFieldMapping();
        }
        $this->_associationMappings[$sourceFieldName] = $assocMapping;
        $this->_registerMappingIfInverse($assocMapping);

        // Store ReflectionProperty of mapped field
        $refProp = $this->_reflectionClass->getProperty($sourceFieldName);
        $refProp->setAccessible(true);
        $this->_reflectionProperties[$sourceFieldName] = $refProp;
    }
    
    /**
     * Registers a custom repository class for the entity class.
     *
     * @param string $mapperClassName  The class name of the custom mapper.
     */
    public function setCustomRepositoryClass($repositoryClassName)
    {
        $this->_customRepositoryClassName = $repositoryClassName;
    }
    
    /**
     * Gets the name of the custom repository class used for the entity class.
     *
     * @return string|null  The name of the custom repository class or NULL if the entity
     *                      class does not have a custom repository class.
     */
    public function getCustomRepositoryClass()
    {
         return $this->_customRepositoryClassName;
    }

    /**
     * Sets whether sub classes should be automatically OUTER JOINed when a base
     * class is queried in a class hierarchy that uses the JOINED inheritance mapping
     * strategy.
     *
     * <b>This options does only apply to the JOINED inheritance mapping strategy.</b>
     *
     * @param boolean $bool
     * @see getJoinSubClasses()
     */
    public function setJoinSubClasses($bool)
    {
        $this->_joinSubclasses = (bool)$bool;
    }

    /**
     * Gets whether the class mapped by this instance should OUTER JOIN sub classes
     * when a base class is queried.
     *
     * @return <type>
     * @see setJoinSubClasses()
     */
    public function getJoinSubClasses()
    {
        return $this->_joinSubclasses;
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
        foreach ($this->getLifecycleCallbacks($lifecycleEvent) as $callback) {
            $entity->$callback();
        }
        foreach ($this->getLifecycleListeners($lifecycleEvent) as $className => $callback) {
            if ( ! isset($this->_lifecycleListenerInstances[$className])) {
                $this->_lifecycleListenerInstances[$className] = new $className;
            }
            $this->_lifecycleListenerInstances[$className]->$callback($entity);
        }
    }
    
    /**
     * Gets the registered lifecycle callbacks for an event.
     *
     * @param string $event
     * @return array
     */
    public function getLifecycleCallbacks($event)
    {
        return isset($this->_lifecycleCallbacks[$event]) ?
                $this->_lifecycleCallbacks[$event] : array();
    }
    
    /**
     * Gets the registered lifecycle listeners for an event.
     *
     * @param string $event
     * @return array
     */
    public function getLifecycleListeners($event)
    {
        return isset($this->_lifecycleListeners[$event]) ?
                $this->_lifecycleListeners[$event] : array();
    }
    
    /**
     * Adds a lifecycle listener for entities of this class.
     * 
     * Note: If the same listener class is registered more than once, the old
     * one will be overridden.
     *
     * @param string $listenerClass
     * @param array $callbacks
     */
    public function addLifecycleListener($listenerClass, array $callbacks)
    {
        $this->_lifecycleListeners[$event][$listenerClass] = array();
        foreach ($callbacks as $method => $event) {
            $this->_lifecycleListeners[$event][$listenerClass][] = $method;
        }
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
        if ( ! isset($this->_lifecycleCallbacks[$event])) {
            $this->_lifecycleCallbacks[$event] = array();
        }
        if ( ! in_array($callback, $this->_lifecycleCallbacks[$event])) {
            $this->_lifecycleCallbacks[$event][$callback] = $callback;
        } 
    }

    /**
     * Sets the discriminator column definition.
     *
     * @param array $columnDef
     * @see getDiscriminatorColumn()
     */
    public function setDiscriminatorColumn($columnDef)
    {
        $this->_discriminatorColumn = $columnDef;
    }

    /**
     * Gets the discriminator column definition.
     *
     * The discriminator column definition is an array with the following keys:
     * name: The name of the column
     * type: The type of the column (only integer and string supported)
     * length: The length of the column (applies only if type is string)
     *
     * A discriminator column is used for JOINED and SINGLE_TABLE inheritance mappings.
     *
     * @return array
     * @see setDiscriminatorColumn()
     */
    public function getDiscriminatorColumn()
    {
        return $this->_discriminatorColumn;
    }

    /**
     * Sets the dsicriminator map used for mapping discriminator values to class names.
     * Used for JOINED and SINGLE_TABLE inheritance mapping strategies.
     *
     * @param array $map
     */
    public function setDiscriminatorMap(array $map)
    {
        $this->_discriminatorMap = $map;
    }

    /**
     * Gets the discriminator map that maps discriminator values to class names.
     * Used for JOINED and SINGLE_TABLE inheritance mapping strategies.
     *
     * @return array
     */
    public function getDiscriminatorMap()
    {
        return $this->_discriminatorMap;
    }

    /**
     * Checks whether the given column name is the discriminator column.
     *
     * @param string $columnName
     * @return boolean
     */
    public function isDiscriminatorColumn($columnName)
    {
        return $columnName === $this->_discriminatorColumn['name'];
    }

    /**
     * Checks whether the class has a mapped association with the given field name.
     *
     * @param string $fieldName
     * @return boolean
     */
    public function hasAssociation($fieldName)
    {
        return isset($this->_associationMappings[$fieldName]);
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
        return isset($this->_associationMappings[$fieldName]) &&
                $this->_associationMappings[$fieldName]->isOneToOne();
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
        return isset($this->_associationMappings[$fieldName]) &&
                ! $this->_associationMappings[$fieldName]->isOneToOne();
    }

    /** Creates a string representation of the instance. */
    public function __toString()
    {
        return __CLASS__ . '@' . spl_object_hash($this);
    }
}


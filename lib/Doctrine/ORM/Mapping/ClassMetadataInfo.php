<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use BackedEnum;
use BadMethodCallException;
use DateInterval;
use DateTime;
use DateTimeImmutable;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;
use Doctrine\Deprecations\Deprecation;
use Doctrine\Instantiator\Instantiator;
use Doctrine\Instantiator\InstantiatorInterface;
use Doctrine\ORM\Cache\Exception\NonCacheableEntityAssociation;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\Mapping\ReflectionService;
use InvalidArgumentException;
use LogicException;
use ReflectionClass;
use ReflectionEnum;
use ReflectionNamedType;
use ReflectionProperty;
use RuntimeException;

use function array_diff;
use function array_flip;
use function array_intersect;
use function array_keys;
use function array_map;
use function array_merge;
use function array_pop;
use function array_values;
use function assert;
use function class_exists;
use function count;
use function enum_exists;
use function explode;
use function gettype;
use function in_array;
use function interface_exists;
use function is_array;
use function is_subclass_of;
use function ltrim;
use function method_exists;
use function spl_object_id;
use function str_contains;
use function str_replace;
use function strtolower;
use function trait_exists;
use function trim;

use const PHP_VERSION_ID;

/**
 * A <tt>ClassMetadata</tt> instance holds all the object-relational mapping metadata
 * of an entity and its associations.
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
 * @template-covariant T of object
 * @template-implements ClassMetadata<T>
 * @psalm-type FieldMapping = array{
 *      type: string,
 *      fieldName: string,
 *      columnName: string,
 *      length?: int,
 *      id?: bool,
 *      nullable?: bool,
 *      notInsertable?: bool,
 *      notUpdatable?: bool,
 *      generated?: string,
 *      enumType?: class-string<BackedEnum>,
 *      columnDefinition?: string,
 *      precision?: int,
 *      scale?: int,
 *      unique?: string,
 *      inherited?: class-string,
 *      originalClass?: class-string,
 *      originalField?: string,
 *      quoted?: bool,
 *      requireSQLConversion?: bool,
 *      declared?: class-string,
 *      declaredField?: string,
 *      options?: array<string, mixed>
 * }
 */
class ClassMetadataInfo implements ClassMetadata
{
    /* The inheritance mapping types */
    /**
     * NONE means the class does not participate in an inheritance hierarchy
     * and therefore does not need an inheritance mapping type.
     */
    public const INHERITANCE_TYPE_NONE = 1;

    /**
     * JOINED means the class will be persisted according to the rules of
     * <tt>Class Table Inheritance</tt>.
     */
    public const INHERITANCE_TYPE_JOINED = 2;

    /**
     * SINGLE_TABLE means the class will be persisted according to the rules of
     * <tt>Single Table Inheritance</tt>.
     */
    public const INHERITANCE_TYPE_SINGLE_TABLE = 3;

    /**
     * TABLE_PER_CLASS means the class will be persisted according to the rules
     * of <tt>Concrete Table Inheritance</tt>.
     */
    public const INHERITANCE_TYPE_TABLE_PER_CLASS = 4;

    /* The Id generator types. */
    /**
     * AUTO means the generator type will depend on what the used platform prefers.
     * Offers full portability.
     */
    public const GENERATOR_TYPE_AUTO = 1;

    /**
     * SEQUENCE means a separate sequence object will be used. Platforms that do
     * not have native sequence support may emulate it. Full portability is currently
     * not guaranteed.
     */
    public const GENERATOR_TYPE_SEQUENCE = 2;

    /**
     * TABLE means a separate table is used for id generation.
     * Offers full portability (in that it results in an exception being thrown
     * no matter the platform).
     *
     * @deprecated no replacement planned
     */
    public const GENERATOR_TYPE_TABLE = 3;

    /**
     * IDENTITY means an identity column is used for id generation. The database
     * will fill in the id column on insertion. Platforms that do not support
     * native identity columns may emulate them. Full portability is currently
     * not guaranteed.
     */
    public const GENERATOR_TYPE_IDENTITY = 4;

    /**
     * NONE means the class does not have a generated id. That means the class
     * must have a natural, manually assigned id.
     */
    public const GENERATOR_TYPE_NONE = 5;

    /**
     * UUID means that a UUID/GUID expression is used for id generation. Full
     * portability is currently not guaranteed.
     *
     * @deprecated use an application-side generator instead
     */
    public const GENERATOR_TYPE_UUID = 6;

    /**
     * CUSTOM means that customer will use own ID generator that supposedly work
     */
    public const GENERATOR_TYPE_CUSTOM = 7;

    /**
     * DEFERRED_IMPLICIT means that changes of entities are calculated at commit-time
     * by doing a property-by-property comparison with the original data. This will
     * be done for all entities that are in MANAGED state at commit-time.
     *
     * This is the default change tracking policy.
     */
    public const CHANGETRACKING_DEFERRED_IMPLICIT = 1;

    /**
     * DEFERRED_EXPLICIT means that changes of entities are calculated at commit-time
     * by doing a property-by-property comparison with the original data. This will
     * be done only for entities that were explicitly saved (through persist() or a cascade).
     */
    public const CHANGETRACKING_DEFERRED_EXPLICIT = 2;

    /**
     * NOTIFY means that Doctrine relies on the entities sending out notifications
     * when their properties change. Such entity classes must implement
     * the <tt>NotifyPropertyChanged</tt> interface.
     */
    public const CHANGETRACKING_NOTIFY = 3;

    /**
     * Specifies that an association is to be fetched when it is first accessed.
     */
    public const FETCH_LAZY = 2;

    /**
     * Specifies that an association is to be fetched when the owner of the
     * association is fetched.
     */
    public const FETCH_EAGER = 3;

    /**
     * Specifies that an association is to be fetched lazy (on first access) and that
     * commands such as Collection#count, Collection#slice are issued directly against
     * the database if the collection is not yet initialized.
     */
    public const FETCH_EXTRA_LAZY = 4;

    /**
     * Identifies a one-to-one association.
     */
    public const ONE_TO_ONE = 1;

    /**
     * Identifies a many-to-one association.
     */
    public const MANY_TO_ONE = 2;

    /**
     * Identifies a one-to-many association.
     */
    public const ONE_TO_MANY = 4;

    /**
     * Identifies a many-to-many association.
     */
    public const MANY_TO_MANY = 8;

    /**
     * Combined bitmask for to-one (single-valued) associations.
     */
    public const TO_ONE = 3;

    /**
     * Combined bitmask for to-many (collection-valued) associations.
     */
    public const TO_MANY = 12;

    /**
     * ReadOnly cache can do reads, inserts and deletes, cannot perform updates or employ any locks,
     */
    public const CACHE_USAGE_READ_ONLY = 1;

    /**
     * Nonstrict Read Write Cache doesn’t employ any locks but can do inserts, update and deletes.
     */
    public const CACHE_USAGE_NONSTRICT_READ_WRITE = 2;

    /**
     * Read Write Attempts to lock the entity before update/delete.
     */
    public const CACHE_USAGE_READ_WRITE = 3;

    /**
     * The value of this column is never generated by the database.
     */
    public const GENERATED_NEVER = 0;

    /**
     * The value of this column is generated by the database on INSERT, but not on UPDATE.
     */
    public const GENERATED_INSERT = 1;

    /**
     * The value of this column is generated by the database on both INSERT and UDPATE statements.
     */
    public const GENERATED_ALWAYS = 2;

    /**
     * READ-ONLY: The name of the entity class.
     *
     * @var string
     * @psalm-var class-string<T>
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
     * as {@link $name}.
     *
     * @var string
     * @psalm-var class-string
     */
    public $rootEntityName;

    /**
     * READ-ONLY: The definition of custom generator. Only used for CUSTOM
     * generator type
     *
     * The definition has the following structure:
     * <code>
     * array(
     *     'class' => 'ClassName',
     * )
     * </code>
     *
     * @todo Merge with tableGeneratorDefinition into generic generatorDefinition
     * @var array<string, string>|null
     */
    public $customGeneratorDefinition;

    /**
     * The name of the custom repository class used for the entity class.
     * (Optional).
     *
     * @var string|null
     * @psalm-var ?class-string<EntityRepository>
     */
    public $customRepositoryClassName;

    /**
     * READ-ONLY: Whether this class describes the mapping of a mapped superclass.
     *
     * @var bool
     */
    public $isMappedSuperclass = false;

    /**
     * READ-ONLY: Whether this class describes the mapping of an embeddable class.
     *
     * @var bool
     */
    public $isEmbeddedClass = false;

    /**
     * READ-ONLY: The names of the parent classes (ancestors).
     *
     * @psalm-var list<class-string>
     */
    public $parentClasses = [];

    /**
     * READ-ONLY: The names of all subclasses (descendants).
     *
     * @psalm-var list<class-string>
     */
    public $subClasses = [];

    /**
     * READ-ONLY: The names of all embedded classes based on properties.
     *
     * @psalm-var array<string, mixed[]>
     */
    public $embeddedClasses = [];

    /**
     * READ-ONLY: The named queries allowed to be called directly from Repository.
     *
     * @psalm-var array<string, array<string, mixed>>
     */
    public $namedQueries = [];

    /**
     * READ-ONLY: The named native queries allowed to be called directly from Repository.
     *
     * A native SQL named query definition has the following structure:
     * <pre>
     * array(
     *     'name'               => <query name>,
     *     'query'              => <sql query>,
     *     'resultClass'        => <class of the result>,
     *     'resultSetMapping'   => <name of a SqlResultSetMapping>
     * )
     * </pre>
     *
     * @psalm-var array<string, array<string, mixed>>
     */
    public $namedNativeQueries = [];

    /**
     * READ-ONLY: The mappings of the results of native SQL queries.
     *
     * A native result mapping definition has the following structure:
     * <pre>
     * array(
     *     'name'               => <result name>,
     *     'entities'           => array(<entity result mapping>),
     *     'columns'            => array(<column result mapping>)
     * )
     * </pre>
     *
     * @psalm-var array<string, array{
     *                name: string,
     *                entities: mixed[],
     *                columns: mixed[]
     *            }>
     */
    public $sqlResultSetMappings = [];

    /**
     * READ-ONLY: The field names of all fields that are part of the identifier/primary key
     * of the mapped entity class.
     *
     * @psalm-var list<string>
     */
    public $identifier = [];

    /**
     * READ-ONLY: The inheritance mapping type used by the class.
     *
     * @var int
     * @psalm-var self::INHERITANCE_TYPE_*
     */
    public $inheritanceType = self::INHERITANCE_TYPE_NONE;

    /**
     * READ-ONLY: The Id generator type used by the class.
     *
     * @var int
     * @psalm-var self::GENERATOR_TYPE_*
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
     * - <b>'notInsertable'</b> (boolean, optional)
     * Whether the column is not insertable. Optional. Is only set if value is TRUE.
     *
     * - <b>'notUpdatable'</b> (boolean, optional)
     * Whether the column is updatable. Optional. Is only set if value is TRUE.
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
     * - <b>'unique'</b> (string, optional, schema-only)
     * Whether a unique constraint should be generated for the column.
     *
     * @var mixed[]
     * @psalm-var array<string, FieldMapping>
     */
    public $fieldMappings = [];

    /**
     * READ-ONLY: An array of field names. Used to look up field names from column names.
     * Keys are column names and values are field names.
     *
     * @psalm-var array<string, string>
     */
    public $fieldNames = [];

    /**
     * READ-ONLY: A map of field names to column names. Keys are field names and values column names.
     * Used to look up column names from field names.
     * This is the reverse lookup map of $_fieldNames.
     *
     * @deprecated 3.0 Remove this.
     *
     * @var mixed[]
     */
    public $columnNames = [];

    /**
     * READ-ONLY: The discriminator value of this class.
     *
     * <b>This does only apply to the JOINED and SINGLE_TABLE inheritance mapping strategies
     * where a discriminator column is used.</b>
     *
     * @see discriminatorColumn
     *
     * @var mixed
     */
    public $discriminatorValue;

    /**
     * READ-ONLY: The discriminator map of all mapped classes in the hierarchy.
     *
     * <b>This does only apply to the JOINED and SINGLE_TABLE inheritance mapping strategies
     * where a discriminator column is used.</b>
     *
     * @see discriminatorColumn
     *
     * @var mixed
     */
    public $discriminatorMap = [];

    /**
     * READ-ONLY: The definition of the discriminator column used in JOINED and SINGLE_TABLE
     * inheritance mappings.
     *
     * @psalm-var array<string, mixed>|null
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
     * @var mixed[]
     * @psalm-var array{
     *               name: string,
     *               schema: string,
     *               indexes: array,
     *               uniqueConstraints: array,
     *               options: array<string, mixed>,
     *               quoted?: bool
     *           }
     */
    public $table;

    /**
     * READ-ONLY: The registered lifecycle callbacks for entities of this class.
     *
     * @psalm-var array<string, list<string>>
     */
    public $lifecycleCallbacks = [];

    /**
     * READ-ONLY: The registered entity listeners.
     *
     * @psalm-var array<string, list<array{class: class-string, method: string}>>
     */
    public $entityListeners = [];

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
     * - <b>indexBy</b> (string, optional, to-many only)
     * Specification of a field on target-entity that is used to index the collection by.
     * This field HAS to be either the primary key or a unique column. Otherwise the collection
     * does not contain all the entities that are actually related.
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
     * @psalm-var array<string, array<string, mixed>>
     */
    public $associationMappings = [];

    /**
     * READ-ONLY: Flag indicating whether the identifier/primary key of the class is composite.
     *
     * @var bool
     */
    public $isIdentifierComposite = false;

    /**
     * READ-ONLY: Flag indicating whether the identifier/primary key contains at least one foreign key association.
     *
     * This flag is necessary because some code blocks require special treatment of this cases.
     *
     * @var bool
     */
    public $containsForeignIdentifier = false;

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
     *     'allocationSize' => '20',
     *     'initialValue' => '1'
     * )
     * </code>
     *
     * @var array<string, mixed>
     * @psalm-var array{sequenceName: string, allocationSize: string, initialValue: string, quoted?: mixed}
     * @todo Merge with tableGeneratorDefinition into generic generatorDefinition
     */
    public $sequenceGeneratorDefinition;

    /**
     * READ-ONLY: The definition of the table generator of this class. Only used for the
     * TABLE generation strategy.
     *
     * @deprecated
     *
     * @var array<string, mixed>
     */
    public $tableGeneratorDefinition;

    /**
     * READ-ONLY: The policy used for change-tracking on entities of this class.
     *
     * @var int
     */
    public $changeTrackingPolicy = self::CHANGETRACKING_DEFERRED_IMPLICIT;

    /**
     * READ-ONLY: A Flag indicating whether one or more columns of this class
     * have to be reloaded after insert / update operations.
     *
     * @var bool
     */
    public $requiresFetchAfterChange = false;

    /**
     * READ-ONLY: A flag for whether or not instances of this class are to be versioned
     * with optimistic locking.
     *
     * @var bool
     */
    public $isVersioned = false;

    /**
     * READ-ONLY: The name of the field which is used for versioning in optimistic locking (if any).
     *
     * @var mixed
     */
    public $versionField;

    /** @var mixed[]|null */
    public $cache;

    /**
     * The ReflectionClass instance of the mapped class.
     *
     * @var ReflectionClass|null
     */
    public $reflClass;

    /**
     * Is this entity marked as "read-only"?
     *
     * That means it is never considered for change-tracking in the UnitOfWork. It is a very helpful performance
     * optimization for entities that are immutable, either in your domain or through the relation database
     * (coming from a view, or a history table for example).
     *
     * @var bool
     */
    public $isReadOnly = false;

    /**
     * NamingStrategy determining the default column and table names.
     *
     * @var NamingStrategy
     */
    protected $namingStrategy;

    /**
     * The ReflectionProperty instances of the mapped class.
     *
     * @var array<string, ReflectionProperty|null>
     */
    public $reflFields = [];

    /** @var InstantiatorInterface|null */
    private $instantiator;

    /**
     * Initializes a new ClassMetadata instance that will hold the object-relational mapping
     * metadata of the class with the given name.
     *
     * @param string $entityName The name of the entity class the new instance is used for.
     * @psalm-param class-string<T> $entityName
     */
    public function __construct($entityName, ?NamingStrategy $namingStrategy = null)
    {
        $this->name           = $entityName;
        $this->rootEntityName = $entityName;
        $this->namingStrategy = $namingStrategy ?: new DefaultNamingStrategy();
        $this->instantiator   = new Instantiator();
    }

    /**
     * Gets the ReflectionProperties of the mapped class.
     *
     * @return ReflectionProperty[]|null[] An array of ReflectionProperty instances.
     * @psalm-return array<ReflectionProperty|null>
     */
    public function getReflectionProperties()
    {
        return $this->reflFields;
    }

    /**
     * Gets a ReflectionProperty for a specific field of the mapped class.
     *
     * @param string $name
     *
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
     *
     * @throws BadMethodCallException If the class has a composite identifier.
     */
    public function getSingleIdReflectionProperty()
    {
        if ($this->isIdentifierComposite) {
            throw new BadMethodCallException('Class ' . $this->name . ' has a composite identifier.');
        }

        return $this->reflFields[$this->identifier[0]];
    }

    /**
     * Extracts the identifier values of an entity of this class.
     *
     * For composite identifiers, the identifier values are returned as an array
     * with the same order as the field order in {@link identifier}.
     *
     * @param object $entity
     *
     * @return array<string, mixed>
     */
    public function getIdentifierValues($entity)
    {
        if ($this->isIdentifierComposite) {
            $id = [];

            foreach ($this->identifier as $idField) {
                $value = $this->reflFields[$idField]->getValue($entity);

                if ($value !== null) {
                    $id[$idField] = $value;
                }
            }

            return $id;
        }

        $id    = $this->identifier[0];
        $value = $this->reflFields[$id]->getValue($entity);

        if ($value === null) {
            return [];
        }

        return [$id => $value];
    }

    /**
     * Populates the entity identifier of an entity.
     *
     * @param object $entity
     * @psalm-param array<string, mixed> $id
     *
     * @return void
     *
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
     * @param mixed  $value
     *
     * @return void
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
     *
     * @return mixed
     */
    public function getFieldValue($entity, $field)
    {
        return $this->reflFields[$field]->getValue($entity);
    }

    /**
     * Creates a string representation of this instance.
     *
     * @return string The string representation of this instance.
     *
     * @todo Construct meaningful string representation.
     */
    public function __toString()
    {
        return self::class . '@' . spl_object_id($this);
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
     * @return string[] The names of all the fields that should be serialized.
     */
    public function __sleep()
    {
        // This metadata is always serialized/cached.
        $serialized = [
            'associationMappings',
            'columnNames', //TODO: 3.0 Remove this. Can use fieldMappings[$fieldName]['columnName']
            'fieldMappings',
            'fieldNames',
            'embeddedClasses',
            'identifier',
            'isIdentifierComposite', // TODO: REMOVE
            'name',
            'namespace', // TODO: REMOVE
            'table',
            'rootEntityName',
            'idGenerator', //TODO: Does not really need to be serialized. Could be moved to runtime.
        ];

        // The rest of the metadata is only serialized if necessary.
        if ($this->changeTrackingPolicy !== self::CHANGETRACKING_DEFERRED_IMPLICIT) {
            $serialized[] = 'changeTrackingPolicy';
        }

        if ($this->customRepositoryClassName) {
            $serialized[] = 'customRepositoryClassName';
        }

        if ($this->inheritanceType !== self::INHERITANCE_TYPE_NONE) {
            $serialized[] = 'inheritanceType';
            $serialized[] = 'discriminatorColumn';
            $serialized[] = 'discriminatorValue';
            $serialized[] = 'discriminatorMap';
            $serialized[] = 'parentClasses';
            $serialized[] = 'subClasses';
        }

        if ($this->generatorType !== self::GENERATOR_TYPE_NONE) {
            $serialized[] = 'generatorType';
            if ($this->generatorType === self::GENERATOR_TYPE_SEQUENCE) {
                $serialized[] = 'sequenceGeneratorDefinition';
            }
        }

        if ($this->isMappedSuperclass) {
            $serialized[] = 'isMappedSuperclass';
        }

        if ($this->isEmbeddedClass) {
            $serialized[] = 'isEmbeddedClass';
        }

        if ($this->containsForeignIdentifier) {
            $serialized[] = 'containsForeignIdentifier';
        }

        if ($this->isVersioned) {
            $serialized[] = 'isVersioned';
            $serialized[] = 'versionField';
        }

        if ($this->lifecycleCallbacks) {
            $serialized[] = 'lifecycleCallbacks';
        }

        if ($this->entityListeners) {
            $serialized[] = 'entityListeners';
        }

        if ($this->namedQueries) {
            $serialized[] = 'namedQueries';
        }

        if ($this->namedNativeQueries) {
            $serialized[] = 'namedNativeQueries';
        }

        if ($this->sqlResultSetMappings) {
            $serialized[] = 'sqlResultSetMappings';
        }

        if ($this->isReadOnly) {
            $serialized[] = 'isReadOnly';
        }

        if ($this->customGeneratorDefinition) {
            $serialized[] = 'customGeneratorDefinition';
        }

        if ($this->cache) {
            $serialized[] = 'cache';
        }

        if ($this->requiresFetchAfterChange) {
            $serialized[] = 'requiresFetchAfterChange';
        }

        return $serialized;
    }

    /**
     * Creates a new instance of the mapped class, without invoking the constructor.
     *
     * @return object
     */
    public function newInstance()
    {
        return $this->instantiator->instantiate($this->name);
    }

    /**
     * Restores some state that can not be serialized/unserialized.
     *
     * @param ReflectionService $reflService
     *
     * @return void
     */
    public function wakeupReflection($reflService)
    {
        // Restore ReflectionClass and properties
        $this->reflClass    = $reflService->getClass($this->name);
        $this->instantiator = $this->instantiator ?: new Instantiator();

        $parentReflFields = [];

        foreach ($this->embeddedClasses as $property => $embeddedClass) {
            if (isset($embeddedClass['declaredField'])) {
                $childProperty = $this->getAccessibleProperty(
                    $reflService,
                    $this->embeddedClasses[$embeddedClass['declaredField']]['class'],
                    $embeddedClass['originalField']
                );
                assert($childProperty !== null);
                $parentReflFields[$property] = new ReflectionEmbeddedProperty(
                    $parentReflFields[$embeddedClass['declaredField']],
                    $childProperty,
                    $this->embeddedClasses[$embeddedClass['declaredField']]['class']
                );

                continue;
            }

            $fieldRefl = $this->getAccessibleProperty(
                $reflService,
                $embeddedClass['declared'] ?? $this->name,
                $property
            );

            $parentReflFields[$property] = $fieldRefl;
            $this->reflFields[$property] = $fieldRefl;
        }

        foreach ($this->fieldMappings as $field => $mapping) {
            if (isset($mapping['declaredField']) && isset($parentReflFields[$mapping['declaredField']])) {
                $childProperty = $this->getAccessibleProperty($reflService, $mapping['originalClass'], $mapping['originalField']);
                assert($childProperty !== null);

                if (isset($mapping['enumType'])) {
                    $childProperty = new ReflectionEnumProperty(
                        $childProperty,
                        $mapping['enumType']
                    );
                }

                $this->reflFields[$field] = new ReflectionEmbeddedProperty(
                    $parentReflFields[$mapping['declaredField']],
                    $childProperty,
                    $mapping['originalClass']
                );
                continue;
            }

            $this->reflFields[$field] = isset($mapping['declared'])
                ? $this->getAccessibleProperty($reflService, $mapping['declared'], $field)
                : $this->getAccessibleProperty($reflService, $this->name, $field);

            if (isset($mapping['enumType']) && $this->reflFields[$field] !== null) {
                $this->reflFields[$field] = new ReflectionEnumProperty(
                    $this->reflFields[$field],
                    $mapping['enumType']
                );
            }
        }

        foreach ($this->associationMappings as $field => $mapping) {
            $this->reflFields[$field] = isset($mapping['declared'])
                ? $this->getAccessibleProperty($reflService, $mapping['declared'], $field)
                : $this->getAccessibleProperty($reflService, $this->name, $field);
        }
    }

    /**
     * Initializes a new ClassMetadata instance that will hold the object-relational mapping
     * metadata of the class with the given name.
     *
     * @param ReflectionService $reflService The reflection service.
     *
     * @return void
     */
    public function initializeReflection($reflService)
    {
        $this->reflClass = $reflService->getClass($this->name);
        $this->namespace = $reflService->getClassNamespace($this->name);

        if ($this->reflClass) {
            $this->name = $this->rootEntityName = $this->reflClass->getName();
        }

        $this->table['name'] = $this->namingStrategy->classToTableName($this->name);
    }

    /**
     * Validates Identifier.
     *
     * @return void
     *
     * @throws MappingException
     */
    public function validateIdentifier()
    {
        if ($this->isMappedSuperclass || $this->isEmbeddedClass) {
            return;
        }

        // Verify & complete identifier mapping
        if (! $this->identifier) {
            throw MappingException::identifierRequired($this->name);
        }

        if ($this->usesIdGenerator() && $this->isIdentifierComposite) {
            throw MappingException::compositeKeyAssignedIdGeneratorRequired($this->name);
        }
    }

    /**
     * Validates association targets actually exist.
     *
     * @return void
     *
     * @throws MappingException
     */
    public function validateAssociations()
    {
        foreach ($this->associationMappings as $mapping) {
            if (
                ! class_exists($mapping['targetEntity'])
                && ! interface_exists($mapping['targetEntity'])
                && ! trait_exists($mapping['targetEntity'])
            ) {
                throw MappingException::invalidTargetEntityClass($mapping['targetEntity'], $this->name, $mapping['fieldName']);
            }
        }
    }

    /**
     * Validates lifecycle callbacks.
     *
     * @param ReflectionService $reflService
     *
     * @return void
     *
     * @throws MappingException
     */
    public function validateLifecycleCallbacks($reflService)
    {
        foreach ($this->lifecycleCallbacks as $callbacks) {
            foreach ($callbacks as $callbackFuncName) {
                if (! $reflService->hasPublicMethod($this->name, $callbackFuncName)) {
                    throw MappingException::lifecycleCallbackMethodNotFound($this->name, $callbackFuncName);
                }
            }
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getReflectionClass()
    {
        return $this->reflClass;
    }

    /**
     * @psalm-param array{usage?: mixed, region?: mixed} $cache
     *
     * @return void
     */
    public function enableCache(array $cache)
    {
        if (! isset($cache['usage'])) {
            $cache['usage'] = self::CACHE_USAGE_READ_ONLY;
        }

        if (! isset($cache['region'])) {
            $cache['region'] = strtolower(str_replace('\\', '_', $this->rootEntityName));
        }

        $this->cache = $cache;
    }

    /**
     * @param string $fieldName
     * @psalm-param array{usage?: int, region?: string} $cache
     *
     * @return void
     */
    public function enableAssociationCache($fieldName, array $cache)
    {
        $this->associationMappings[$fieldName]['cache'] = $this->getAssociationCacheDefaults($fieldName, $cache);
    }

    /**
     * @param string $fieldName
     * @param array  $cache
     * @psalm-param array{usage?: int, region?: string|null} $cache
     *
     * @return int[]|string[]
     * @psalm-return array{usage: int, region: string|null}
     */
    public function getAssociationCacheDefaults($fieldName, array $cache)
    {
        if (! isset($cache['usage'])) {
            $cache['usage'] = $this->cache['usage'] ?? self::CACHE_USAGE_READ_ONLY;
        }

        if (! isset($cache['region'])) {
            $cache['region'] = strtolower(str_replace('\\', '_', $this->rootEntityName)) . '__' . $fieldName;
        }

        return $cache;
    }

    /**
     * Sets the change tracking policy used by this class.
     *
     * @param int $policy
     *
     * @return void
     */
    public function setChangeTrackingPolicy($policy)
    {
        $this->changeTrackingPolicy = $policy;
    }

    /**
     * Whether the change tracking policy of this class is "deferred explicit".
     *
     * @return bool
     */
    public function isChangeTrackingDeferredExplicit()
    {
        return $this->changeTrackingPolicy === self::CHANGETRACKING_DEFERRED_EXPLICIT;
    }

    /**
     * Whether the change tracking policy of this class is "deferred implicit".
     *
     * @return bool
     */
    public function isChangeTrackingDeferredImplicit()
    {
        return $this->changeTrackingPolicy === self::CHANGETRACKING_DEFERRED_IMPLICIT;
    }

    /**
     * Whether the change tracking policy of this class is "notify".
     *
     * @return bool
     */
    public function isChangeTrackingNotify()
    {
        return $this->changeTrackingPolicy === self::CHANGETRACKING_NOTIFY;
    }

    /**
     * Checks whether a field is part of the identifier/primary key field(s).
     *
     * @param string $fieldName The field name.
     *
     * @return bool TRUE if the field is part of the table identifier/primary key field(s),
     * FALSE otherwise.
     */
    public function isIdentifier($fieldName)
    {
        if (! $this->identifier) {
            return false;
        }

        if (! $this->isIdentifierComposite) {
            return $fieldName === $this->identifier[0];
        }

        return in_array($fieldName, $this->identifier, true);
    }

    /**
     * Checks if the field is unique.
     *
     * @param string $fieldName The field name.
     *
     * @return bool TRUE if the field is unique, FALSE otherwise.
     */
    public function isUniqueField($fieldName)
    {
        $mapping = $this->getFieldMapping($fieldName);

        return $mapping !== false && isset($mapping['unique']) && $mapping['unique'];
    }

    /**
     * Checks if the field is not null.
     *
     * @param string $fieldName The field name.
     *
     * @return bool TRUE if the field is not null, FALSE otherwise.
     */
    public function isNullable($fieldName)
    {
        $mapping = $this->getFieldMapping($fieldName);

        return $mapping !== false && isset($mapping['nullable']) && $mapping['nullable'];
    }

    /**
     * Gets a column name for a field name.
     * If the column name for the field cannot be found, the given field name
     * is returned.
     *
     * @param string $fieldName The field name.
     *
     * @return string The column name.
     */
    public function getColumnName($fieldName)
    {
        return $this->columnNames[$fieldName] ?? $fieldName;
    }

    /**
     * Gets the mapping of a (regular) field that holds some data but not a
     * reference to another object.
     *
     * @param string $fieldName The field name.
     *
     * @return mixed[] The field mapping.
     * @psalm-return FieldMapping
     *
     * @throws MappingException
     */
    public function getFieldMapping($fieldName)
    {
        if (! isset($this->fieldMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->name, $fieldName);
        }

        return $this->fieldMappings[$fieldName];
    }

    /**
     * Gets the mapping of an association.
     *
     * @see ClassMetadataInfo::$associationMappings
     *
     * @param string $fieldName The field name that represents the association in
     *                          the object model.
     *
     * @return mixed[] The mapping.
     * @psalm-return array<string, mixed>
     *
     * @throws MappingException
     */
    public function getAssociationMapping($fieldName)
    {
        if (! isset($this->associationMappings[$fieldName])) {
            throw MappingException::mappingNotFound($this->name, $fieldName);
        }

        return $this->associationMappings[$fieldName];
    }

    /**
     * Gets all association mappings of the class.
     *
     * @psalm-return array<string, array<string, mixed>>
     */
    public function getAssociationMappings()
    {
        return $this->associationMappings;
    }

    /**
     * Gets the field name for a column name.
     * If no field name can be found the column name is returned.
     *
     * @param string $columnName The column name.
     *
     * @return string The column alias.
     */
    public function getFieldName($columnName)
    {
        return $this->fieldNames[$columnName] ?? $columnName;
    }

    /**
     * Gets the named query.
     *
     * @see ClassMetadataInfo::$namedQueries
     *
     * @param string $queryName The query name.
     *
     * @return string
     *
     * @throws MappingException
     */
    public function getNamedQuery($queryName)
    {
        if (! isset($this->namedQueries[$queryName])) {
            throw MappingException::queryNotFound($this->name, $queryName);
        }

        return $this->namedQueries[$queryName]['dql'];
    }

    /**
     * Gets all named queries of the class.
     *
     * @return mixed[][]
     * @psalm-return array<string, array<string, mixed>>
     */
    public function getNamedQueries()
    {
        return $this->namedQueries;
    }

    /**
     * Gets the named native query.
     *
     * @see ClassMetadataInfo::$namedNativeQueries
     *
     * @param string $queryName The query name.
     *
     * @return mixed[]
     * @psalm-return array<string, mixed>
     *
     * @throws MappingException
     */
    public function getNamedNativeQuery($queryName)
    {
        if (! isset($this->namedNativeQueries[$queryName])) {
            throw MappingException::queryNotFound($this->name, $queryName);
        }

        return $this->namedNativeQueries[$queryName];
    }

    /**
     * Gets all named native queries of the class.
     *
     * @psalm-return array<string, array<string, mixed>>
     */
    public function getNamedNativeQueries()
    {
        return $this->namedNativeQueries;
    }

    /**
     * Gets the result set mapping.
     *
     * @see ClassMetadataInfo::$sqlResultSetMappings
     *
     * @param string $name The result set mapping name.
     *
     * @return mixed[]
     * @psalm-return array{name: string, entities: array, columns: array}
     *
     * @throws MappingException
     */
    public function getSqlResultSetMapping($name)
    {
        if (! isset($this->sqlResultSetMappings[$name])) {
            throw MappingException::resultMappingNotFound($this->name, $name);
        }

        return $this->sqlResultSetMappings[$name];
    }

    /**
     * Gets all sql result set mappings of the class.
     *
     * @return mixed[]
     * @psalm-return array<string, array{name: string, entities: array, columns: array}>
     */
    public function getSqlResultSetMappings()
    {
        return $this->sqlResultSetMappings;
    }

    /**
     * Checks whether given property has type
     *
     * @param string $name Property name
     */
    private function isTypedProperty(string $name): bool
    {
        return PHP_VERSION_ID >= 70400
               && isset($this->reflClass)
               && $this->reflClass->hasProperty($name)
               && $this->reflClass->getProperty($name)->hasType();
    }

    /**
     * Validates & completes the given field mapping based on typed property.
     *
     * @param  mixed[] $mapping The field mapping to validate & complete.
     *
     * @return mixed[] The updated mapping.
     */
    private function validateAndCompleteTypedFieldMapping(array $mapping): array
    {
        $type = $this->reflClass->getProperty($mapping['fieldName'])->getType();

        if ($type) {
            if (
                ! isset($mapping['type'])
                && ($type instanceof ReflectionNamedType)
            ) {
                if (PHP_VERSION_ID >= 80100 && ! $type->isBuiltin() && enum_exists($type->getName())) {
                    $mapping['enumType'] = $type->getName();

                    $reflection = new ReflectionEnum($type->getName());
                    $type       = $reflection->getBackingType();

                    assert($type instanceof ReflectionNamedType);
                }

                switch ($type->getName()) {
                    case DateInterval::class:
                        $mapping['type'] = Types::DATEINTERVAL;
                        break;
                    case DateTime::class:
                        $mapping['type'] = Types::DATETIME_MUTABLE;
                        break;
                    case DateTimeImmutable::class:
                        $mapping['type'] = Types::DATETIME_IMMUTABLE;
                        break;
                    case 'array':
                        $mapping['type'] = Types::JSON;
                        break;
                    case 'bool':
                        $mapping['type'] = Types::BOOLEAN;
                        break;
                    case 'float':
                        $mapping['type'] = Types::FLOAT;
                        break;
                    case 'int':
                        $mapping['type'] = Types::INTEGER;
                        break;
                    case 'string':
                        $mapping['type'] = Types::STRING;
                        break;
                }
            }
        }

        return $mapping;
    }

    /**
     * Validates & completes the basic mapping information based on typed property.
     *
     * @param mixed[] $mapping The mapping.
     *
     * @return mixed[] The updated mapping.
     */
    private function validateAndCompleteTypedAssociationMapping(array $mapping): array
    {
        $type = $this->reflClass->getProperty($mapping['fieldName'])->getType();

        if ($type === null || ($mapping['type'] & self::TO_ONE) === 0) {
            return $mapping;
        }

        if (! isset($mapping['targetEntity']) && $type instanceof ReflectionNamedType) {
            $mapping['targetEntity'] = $type->getName();
        }

        return $mapping;
    }

    /**
     * Validates & completes the given field mapping.
     *
     * @psalm-param array<string, mixed> $mapping The field mapping to validate & complete.
     *
     * @return mixed[] The updated mapping.
     *
     * @throws MappingException
     */
    protected function validateAndCompleteFieldMapping(array $mapping): array
    {
        // Check mandatory fields
        if (! isset($mapping['fieldName']) || ! $mapping['fieldName']) {
            throw MappingException::missingFieldName($this->name);
        }

        if ($this->isTypedProperty($mapping['fieldName'])) {
            $mapping = $this->validateAndCompleteTypedFieldMapping($mapping);
        }

        if (! isset($mapping['type'])) {
            // Default to string
            $mapping['type'] = 'string';
        }

        // Complete fieldName and columnName mapping
        if (! isset($mapping['columnName'])) {
            $mapping['columnName'] = $this->namingStrategy->propertyToColumnName($mapping['fieldName'], $this->name);
        }

        if ($mapping['columnName'][0] === '`') {
            $mapping['columnName'] = trim($mapping['columnName'], '`');
            $mapping['quoted']     = true;
        }

        $this->columnNames[$mapping['fieldName']] = $mapping['columnName'];

        if (isset($this->fieldNames[$mapping['columnName']]) || ($this->discriminatorColumn && $this->discriminatorColumn['name'] === $mapping['columnName'])) {
            throw MappingException::duplicateColumnName($this->name, $mapping['columnName']);
        }

        $this->fieldNames[$mapping['columnName']] = $mapping['fieldName'];

        // Complete id mapping
        if (isset($mapping['id']) && $mapping['id'] === true) {
            if ($this->versionField === $mapping['fieldName']) {
                throw MappingException::cannotVersionIdField($this->name, $mapping['fieldName']);
            }

            if (! in_array($mapping['fieldName'], $this->identifier, true)) {
                $this->identifier[] = $mapping['fieldName'];
            }

            // Check for composite key
            if (! $this->isIdentifierComposite && count($this->identifier) > 1) {
                $this->isIdentifierComposite = true;
            }
        }

        if (Type::hasType($mapping['type']) && Type::getType($mapping['type'])->canRequireSQLConversion()) {
            if (isset($mapping['id']) && $mapping['id'] === true) {
                 throw MappingException::sqlConversionNotAllowedForIdentifiers($this->name, $mapping['fieldName'], $mapping['type']);
            }

            $mapping['requireSQLConversion'] = true;
        }

        if (isset($mapping['generated'])) {
            if (! in_array($mapping['generated'], [self::GENERATED_NEVER, self::GENERATED_INSERT, self::GENERATED_ALWAYS])) {
                throw MappingException::invalidGeneratedMode($mapping['generated']);
            }

            if ($mapping['generated'] === self::GENERATED_NEVER) {
                unset($mapping['generated']);
            }
        }

        if (isset($mapping['enumType'])) {
            if (PHP_VERSION_ID < 80100) {
                throw MappingException::enumsRequirePhp81($this->name, $mapping['fieldName']);
            }

            if (! enum_exists($mapping['enumType'])) {
                throw MappingException::nonEnumTypeMapped($this->name, $mapping['fieldName'], $mapping['enumType']);
            }
        }

        return $mapping;
    }

    /**
     * Validates & completes the basic mapping information that is common to all
     * association mappings (one-to-one, many-ot-one, one-to-many, many-to-many).
     *
     * @psalm-param array<string, mixed> $mapping The mapping.
     *
     * @return mixed[] The updated mapping.
     * @psalm-return array{
     *                   mappedBy: mixed|null,
     *                   inversedBy: mixed|null,
     *                   isOwningSide: bool,
     *                   sourceEntity: class-string,
     *                   targetEntity: string,
     *                   fieldName: mixed,
     *                   fetch: mixed,
     *                   cascade: array<array-key,string>,
     *                   isCascadeRemove: bool,
     *                   isCascadePersist: bool,
     *                   isCascadeRefresh: bool,
     *                   isCascadeMerge: bool,
     *                   isCascadeDetach: bool,
     *                   type: int,
     *                   originalField: string,
     *                   originalClass: class-string,
     *                   ?orphanRemoval: bool
     *               }
     *
     * @throws MappingException If something is wrong with the mapping.
     */
    protected function _validateAndCompleteAssociationMapping(array $mapping)
    {
        if (! isset($mapping['mappedBy'])) {
            $mapping['mappedBy'] = null;
        }

        if (! isset($mapping['inversedBy'])) {
            $mapping['inversedBy'] = null;
        }

        $mapping['isOwningSide'] = true; // assume owning side until we hit mappedBy

        if (empty($mapping['indexBy'])) {
            unset($mapping['indexBy']);
        }

        // If targetEntity is unqualified, assume it is in the same namespace as
        // the sourceEntity.
        $mapping['sourceEntity'] = $this->name;

        if ($this->isTypedProperty($mapping['fieldName'])) {
            $mapping = $this->validateAndCompleteTypedAssociationMapping($mapping);
        }

        if (isset($mapping['targetEntity'])) {
            $mapping['targetEntity'] = $this->fullyQualifiedClassName($mapping['targetEntity']);
            $mapping['targetEntity'] = ltrim($mapping['targetEntity'], '\\');
        }

        if (($mapping['type'] & self::MANY_TO_ONE) > 0 && isset($mapping['orphanRemoval']) && $mapping['orphanRemoval']) {
            throw MappingException::illegalOrphanRemoval($this->name, $mapping['fieldName']);
        }

        // Complete id mapping
        if (isset($mapping['id']) && $mapping['id'] === true) {
            if (isset($mapping['orphanRemoval']) && $mapping['orphanRemoval']) {
                throw MappingException::illegalOrphanRemovalOnIdentifierAssociation($this->name, $mapping['fieldName']);
            }

            if (! in_array($mapping['fieldName'], $this->identifier, true)) {
                if (isset($mapping['joinColumns']) && count($mapping['joinColumns']) >= 2) {
                    throw MappingException::cannotMapCompositePrimaryKeyEntitiesAsForeignId(
                        $mapping['targetEntity'],
                        $this->name,
                        $mapping['fieldName']
                    );
                }

                $this->identifier[]              = $mapping['fieldName'];
                $this->containsForeignIdentifier = true;
            }

            // Check for composite key
            if (! $this->isIdentifierComposite && count($this->identifier) > 1) {
                $this->isIdentifierComposite = true;
            }

            if ($this->cache && ! isset($mapping['cache'])) {
                throw NonCacheableEntityAssociation::fromEntityAndField(
                    $this->name,
                    $mapping['fieldName']
                );
            }
        }

        // Mandatory attributes for both sides
        // Mandatory: fieldName, targetEntity
        if (! isset($mapping['fieldName']) || ! $mapping['fieldName']) {
            throw MappingException::missingFieldName($this->name);
        }

        if (! isset($mapping['targetEntity'])) {
            throw MappingException::missingTargetEntity($mapping['fieldName']);
        }

        // Mandatory and optional attributes for either side
        if (! $mapping['mappedBy']) {
            if (isset($mapping['joinTable']) && $mapping['joinTable']) {
                if (isset($mapping['joinTable']['name']) && $mapping['joinTable']['name'][0] === '`') {
                    $mapping['joinTable']['name']   = trim($mapping['joinTable']['name'], '`');
                    $mapping['joinTable']['quoted'] = true;
                }
            }
        } else {
            $mapping['isOwningSide'] = false;
        }

        if (isset($mapping['id']) && $mapping['id'] === true && $mapping['type'] & self::TO_MANY) {
            throw MappingException::illegalToManyIdentifierAssociation($this->name, $mapping['fieldName']);
        }

        // Fetch mode. Default fetch mode to LAZY, if not set.
        if (! isset($mapping['fetch'])) {
            $mapping['fetch'] = self::FETCH_LAZY;
        }

        // Cascades
        $cascades = isset($mapping['cascade']) ? array_map('strtolower', $mapping['cascade']) : [];

        $allCascades = ['remove', 'persist', 'refresh', 'merge', 'detach'];
        if (in_array('all', $cascades, true)) {
            $cascades = $allCascades;
        } elseif (count($cascades) !== count(array_intersect($cascades, $allCascades))) {
            throw MappingException::invalidCascadeOption(
                array_diff($cascades, $allCascades),
                $this->name,
                $mapping['fieldName']
            );
        }

        $mapping['cascade']          = $cascades;
        $mapping['isCascadeRemove']  = in_array('remove', $cascades, true);
        $mapping['isCascadePersist'] = in_array('persist', $cascades, true);
        $mapping['isCascadeRefresh'] = in_array('refresh', $cascades, true);
        $mapping['isCascadeMerge']   = in_array('merge', $cascades, true);
        $mapping['isCascadeDetach']  = in_array('detach', $cascades, true);

        return $mapping;
    }

    /**
     * Validates & completes a one-to-one association mapping.
     *
     * @psalm-param array<string, mixed> $mapping The mapping to validate & complete.
     * @psalm-param array<string, mixed> $mapping The mapping to validate & complete.
     *
     * @return mixed[] The validated & completed mapping.
     * @psalm-return array{isOwningSide: mixed, orphanRemoval: bool, isCascadeRemove: bool}
     * @psalm-return array{
     *      mappedBy: mixed|null,
     *      inversedBy: mixed|null,
     *      isOwningSide: bool,
     *      sourceEntity: class-string,
     *      targetEntity: string,
     *      fieldName: mixed,
     *      fetch: mixed,
     *      cascade: array<string>,
     *      isCascadeRemove: bool,
     *      isCascadePersist: bool,
     *      isCascadeRefresh: bool,
     *      isCascadeMerge: bool,
     *      isCascadeDetach: bool,
     *      type: int,
     *      originalField: string,
     *      originalClass: class-string,
     *      joinColumns?: array{0: array{name: string, referencedColumnName: string}}|mixed,
     *      id?: mixed,
     *      sourceToTargetKeyColumns?: array,
     *      joinColumnFieldNames?: array,
     *      targetToSourceKeyColumns?: array<array-key>,
     *      orphanRemoval: bool
     * }
     *
     * @throws RuntimeException
     * @throws MappingException
     */
    protected function _validateAndCompleteOneToOneMapping(array $mapping)
    {
        $mapping = $this->_validateAndCompleteAssociationMapping($mapping);

        if (isset($mapping['joinColumns']) && $mapping['joinColumns']) {
            $mapping['isOwningSide'] = true;
        }

        if ($mapping['isOwningSide']) {
            if (empty($mapping['joinColumns'])) {
                // Apply default join column
                $mapping['joinColumns'] = [
                    [
                        'name' => $this->namingStrategy->joinColumnName($mapping['fieldName'], $this->name),
                        'referencedColumnName' => $this->namingStrategy->referenceColumnName(),
                    ],
                ];
            }

            $uniqueConstraintColumns = [];

            foreach ($mapping['joinColumns'] as &$joinColumn) {
                if ($mapping['type'] === self::ONE_TO_ONE && ! $this->isInheritanceTypeSingleTable()) {
                    if (count($mapping['joinColumns']) === 1) {
                        if (empty($mapping['id'])) {
                            $joinColumn['unique'] = true;
                        }
                    } else {
                        $uniqueConstraintColumns[] = $joinColumn['name'];
                    }
                }

                if (empty($joinColumn['name'])) {
                    $joinColumn['name'] = $this->namingStrategy->joinColumnName($mapping['fieldName'], $this->name);
                }

                if (empty($joinColumn['referencedColumnName'])) {
                    $joinColumn['referencedColumnName'] = $this->namingStrategy->referenceColumnName();
                }

                if ($joinColumn['name'][0] === '`') {
                    $joinColumn['name']   = trim($joinColumn['name'], '`');
                    $joinColumn['quoted'] = true;
                }

                if ($joinColumn['referencedColumnName'][0] === '`') {
                    $joinColumn['referencedColumnName'] = trim($joinColumn['referencedColumnName'], '`');
                    $joinColumn['quoted']               = true;
                }

                $mapping['sourceToTargetKeyColumns'][$joinColumn['name']] = $joinColumn['referencedColumnName'];
                $mapping['joinColumnFieldNames'][$joinColumn['name']]     = $joinColumn['fieldName'] ?? $joinColumn['name'];
            }

            if ($uniqueConstraintColumns) {
                if (! $this->table) {
                    throw new RuntimeException('ClassMetadataInfo::setTable() has to be called before defining a one to one relationship.');
                }

                $this->table['uniqueConstraints'][$mapping['fieldName'] . '_uniq'] = ['columns' => $uniqueConstraintColumns];
            }

            $mapping['targetToSourceKeyColumns'] = array_flip($mapping['sourceToTargetKeyColumns']);
        }

        $mapping['orphanRemoval']   = isset($mapping['orphanRemoval']) && $mapping['orphanRemoval'];
        $mapping['isCascadeRemove'] = $mapping['orphanRemoval'] || $mapping['isCascadeRemove'];

        if ($mapping['orphanRemoval']) {
            unset($mapping['unique']);
        }

        if (isset($mapping['id']) && $mapping['id'] === true && ! $mapping['isOwningSide']) {
            throw MappingException::illegalInverseIdentifierAssociation($this->name, $mapping['fieldName']);
        }

        return $mapping;
    }

    /**
     * Validates & completes a one-to-many association mapping.
     *
     * @psalm-param array<string, mixed> $mapping The mapping to validate and complete.
     *
     * @return mixed[] The validated and completed mapping.
     * @psalm-return array{
     *                   mappedBy: mixed,
     *                   inversedBy: mixed,
     *                   isOwningSide: bool,
     *                   sourceEntity: string,
     *                   targetEntity: string,
     *                   fieldName: mixed,
     *                   fetch: int|mixed,
     *                   cascade: array<array-key,string>,
     *                   isCascadeRemove: bool,
     *                   isCascadePersist: bool,
     *                   isCascadeRefresh: bool,
     *                   isCascadeMerge: bool,
     *                   isCascadeDetach: bool,
     *                   orphanRemoval: bool
     *               }
     *
     * @throws MappingException
     * @throws InvalidArgumentException
     */
    protected function _validateAndCompleteOneToManyMapping(array $mapping)
    {
        $mapping = $this->_validateAndCompleteAssociationMapping($mapping);

        // OneToMany-side MUST be inverse (must have mappedBy)
        if (! isset($mapping['mappedBy'])) {
            throw MappingException::oneToManyRequiresMappedBy($this->name, $mapping['fieldName']);
        }

        $mapping['orphanRemoval']   = isset($mapping['orphanRemoval']) && $mapping['orphanRemoval'];
        $mapping['isCascadeRemove'] = $mapping['orphanRemoval'] || $mapping['isCascadeRemove'];

        $this->assertMappingOrderBy($mapping);

        return $mapping;
    }

    /**
     * Validates & completes a many-to-many association mapping.
     *
     * @psalm-param array<string, mixed> $mapping The mapping to validate & complete.
     * @psalm-param array<string, mixed> $mapping The mapping to validate & complete.
     *
     * @return mixed[] The validated & completed mapping.
     * @psalm-return array{
     *      mappedBy: mixed,
     *      inversedBy: mixed,
     *      isOwningSide: bool,
     *      sourceEntity: class-string,
     *      targetEntity: string,
     *      fieldName: mixed,
     *      fetch: mixed,
     *      cascade: array<string>,
     *      isCascadeRemove: bool,
     *      isCascadePersist: bool,
     *      isCascadeRefresh: bool,
     *      isCascadeMerge: bool,
     *      isCascadeDetach: bool,
     *      type: int,
     *      originalField: string,
     *      originalClass: class-string,
     *      joinTable?: array{inverseJoinColumns: mixed}|mixed,
     *      joinTableColumns?: list<mixed>,
     *      isOnDeleteCascade?: true,
     *      relationToSourceKeyColumns?: array,
     *      relationToTargetKeyColumns?: array,
     *      orphanRemoval: bool
     * }
     *
     * @throws InvalidArgumentException
     */
    protected function _validateAndCompleteManyToManyMapping(array $mapping)
    {
        $mapping = $this->_validateAndCompleteAssociationMapping($mapping);

        if ($mapping['isOwningSide']) {
            // owning side MUST have a join table
            if (! isset($mapping['joinTable']['name'])) {
                $mapping['joinTable']['name'] = $this->namingStrategy->joinTableName($mapping['sourceEntity'], $mapping['targetEntity'], $mapping['fieldName']);
            }

            $selfReferencingEntityWithoutJoinColumns = $mapping['sourceEntity'] === $mapping['targetEntity']
                && (! (isset($mapping['joinTable']['joinColumns']) || isset($mapping['joinTable']['inverseJoinColumns'])));

            if (! isset($mapping['joinTable']['joinColumns'])) {
                $mapping['joinTable']['joinColumns'] = [
                    [
                        'name' => $this->namingStrategy->joinKeyColumnName($mapping['sourceEntity'], $selfReferencingEntityWithoutJoinColumns ? 'source' : null),
                        'referencedColumnName' => $this->namingStrategy->referenceColumnName(),
                        'onDelete' => 'CASCADE',
                    ],
                ];
            }

            if (! isset($mapping['joinTable']['inverseJoinColumns'])) {
                $mapping['joinTable']['inverseJoinColumns'] = [
                    [
                        'name' => $this->namingStrategy->joinKeyColumnName($mapping['targetEntity'], $selfReferencingEntityWithoutJoinColumns ? 'target' : null),
                        'referencedColumnName' => $this->namingStrategy->referenceColumnName(),
                        'onDelete' => 'CASCADE',
                    ],
                ];
            }

            $mapping['joinTableColumns'] = [];

            foreach ($mapping['joinTable']['joinColumns'] as &$joinColumn) {
                if (empty($joinColumn['name'])) {
                    $joinColumn['name'] = $this->namingStrategy->joinKeyColumnName($mapping['sourceEntity'], $joinColumn['referencedColumnName']);
                }

                if (empty($joinColumn['referencedColumnName'])) {
                    $joinColumn['referencedColumnName'] = $this->namingStrategy->referenceColumnName();
                }

                if ($joinColumn['name'][0] === '`') {
                    $joinColumn['name']   = trim($joinColumn['name'], '`');
                    $joinColumn['quoted'] = true;
                }

                if ($joinColumn['referencedColumnName'][0] === '`') {
                    $joinColumn['referencedColumnName'] = trim($joinColumn['referencedColumnName'], '`');
                    $joinColumn['quoted']               = true;
                }

                if (isset($joinColumn['onDelete']) && strtolower($joinColumn['onDelete']) === 'cascade') {
                    $mapping['isOnDeleteCascade'] = true;
                }

                $mapping['relationToSourceKeyColumns'][$joinColumn['name']] = $joinColumn['referencedColumnName'];
                $mapping['joinTableColumns'][]                              = $joinColumn['name'];
            }

            foreach ($mapping['joinTable']['inverseJoinColumns'] as &$inverseJoinColumn) {
                if (empty($inverseJoinColumn['name'])) {
                    $inverseJoinColumn['name'] = $this->namingStrategy->joinKeyColumnName($mapping['targetEntity'], $inverseJoinColumn['referencedColumnName']);
                }

                if (empty($inverseJoinColumn['referencedColumnName'])) {
                    $inverseJoinColumn['referencedColumnName'] = $this->namingStrategy->referenceColumnName();
                }

                if ($inverseJoinColumn['name'][0] === '`') {
                    $inverseJoinColumn['name']   = trim($inverseJoinColumn['name'], '`');
                    $inverseJoinColumn['quoted'] = true;
                }

                if ($inverseJoinColumn['referencedColumnName'][0] === '`') {
                    $inverseJoinColumn['referencedColumnName'] = trim($inverseJoinColumn['referencedColumnName'], '`');
                    $inverseJoinColumn['quoted']               = true;
                }

                if (isset($inverseJoinColumn['onDelete']) && strtolower($inverseJoinColumn['onDelete']) === 'cascade') {
                    $mapping['isOnDeleteCascade'] = true;
                }

                $mapping['relationToTargetKeyColumns'][$inverseJoinColumn['name']] = $inverseJoinColumn['referencedColumnName'];
                $mapping['joinTableColumns'][]                                     = $inverseJoinColumn['name'];
            }
        }

        $mapping['orphanRemoval'] = isset($mapping['orphanRemoval']) && $mapping['orphanRemoval'];

        $this->assertMappingOrderBy($mapping);

        return $mapping;
    }

    /**
     * {@inheritDoc}
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
     *
     * @throws MappingException If the class doesn't have an identifier or it has a composite primary key.
     */
    public function getSingleIdentifierFieldName()
    {
        if ($this->isIdentifierComposite) {
            throw MappingException::singleIdNotAllowedOnCompositePrimaryKey($this->name);
        }

        if (! isset($this->identifier[0])) {
            throw MappingException::noIdDefined($this->name);
        }

        return $this->identifier[0];
    }

    /**
     * Gets the column name of the single id column. Note that this only works on
     * entity classes that have a single-field pk.
     *
     * @return string
     *
     * @throws MappingException If the class doesn't have an identifier or it has a composite primary key.
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
     * @psalm-param list<mixed> $identifier
     *
     * @return void
     */
    public function setIdentifier(array $identifier)
    {
        $this->identifier            = $identifier;
        $this->isIdentifierComposite = (count($this->identifier) > 1);
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function hasField($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]) || isset($this->embeddedClasses[$fieldName]);
    }

    /**
     * Gets an array containing all the column names.
     *
     * @psalm-param list<string>|null $fieldNames
     *
     * @return mixed[]
     * @psalm-return list<string>
     */
    public function getColumnNames(?array $fieldNames = null)
    {
        if ($fieldNames === null) {
            return array_keys($this->fieldNames);
        }

        return array_values(array_map([$this, 'getColumnName'], $fieldNames));
    }

    /**
     * Returns an array with all the identifier column names.
     *
     * @psalm-return list<string>
     */
    public function getIdentifierColumnNames()
    {
        $columnNames = [];

        foreach ($this->identifier as $idProperty) {
            if (isset($this->fieldMappings[$idProperty])) {
                $columnNames[] = $this->fieldMappings[$idProperty]['columnName'];

                continue;
            }

            // Association defined as Id field
            $joinColumns      = $this->associationMappings[$idProperty]['joinColumns'];
            $assocColumnNames = array_map(static function ($joinColumn) {
                return $joinColumn['name'];
            }, $joinColumns);

            $columnNames = array_merge($columnNames, $assocColumnNames);
        }

        return $columnNames;
    }

    /**
     * Sets the type of Id generator to use for the mapped class.
     *
     * @param int $generatorType
     * @psalm-param self::GENERATOR_TYPE_* $generatorType
     *
     * @return void
     */
    public function setIdGeneratorType($generatorType)
    {
        $this->generatorType = $generatorType;
    }

    /**
     * Checks whether the mapped class uses an Id generator.
     *
     * @return bool TRUE if the mapped class uses an Id generator, FALSE otherwise.
     */
    public function usesIdGenerator()
    {
        return $this->generatorType !== self::GENERATOR_TYPE_NONE;
    }

    /**
     * @return bool
     */
    public function isInheritanceTypeNone()
    {
        return $this->inheritanceType === self::INHERITANCE_TYPE_NONE;
    }

    /**
     * Checks whether the mapped class uses the JOINED inheritance mapping strategy.
     *
     * @return bool TRUE if the class participates in a JOINED inheritance mapping,
     * FALSE otherwise.
     */
    public function isInheritanceTypeJoined()
    {
        return $this->inheritanceType === self::INHERITANCE_TYPE_JOINED;
    }

    /**
     * Checks whether the mapped class uses the SINGLE_TABLE inheritance mapping strategy.
     *
     * @return bool TRUE if the class participates in a SINGLE_TABLE inheritance mapping,
     * FALSE otherwise.
     */
    public function isInheritanceTypeSingleTable()
    {
        return $this->inheritanceType === self::INHERITANCE_TYPE_SINGLE_TABLE;
    }

    /**
     * Checks whether the mapped class uses the TABLE_PER_CLASS inheritance mapping strategy.
     *
     * @return bool TRUE if the class participates in a TABLE_PER_CLASS inheritance mapping,
     * FALSE otherwise.
     */
    public function isInheritanceTypeTablePerClass()
    {
        return $this->inheritanceType === self::INHERITANCE_TYPE_TABLE_PER_CLASS;
    }

    /**
     * Checks whether the class uses an identity column for the Id generation.
     *
     * @return bool TRUE if the class uses the IDENTITY generator, FALSE otherwise.
     */
    public function isIdGeneratorIdentity()
    {
        return $this->generatorType === self::GENERATOR_TYPE_IDENTITY;
    }

    /**
     * Checks whether the class uses a sequence for id generation.
     *
     * @return bool TRUE if the class uses the SEQUENCE generator, FALSE otherwise.
     */
    public function isIdGeneratorSequence()
    {
        return $this->generatorType === self::GENERATOR_TYPE_SEQUENCE;
    }

    /**
     * Checks whether the class uses a table for id generation.
     *
     * @deprecated
     *
     * @return false
     */
    public function isIdGeneratorTable()
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9046',
            '%s is deprecated',
            __METHOD__
        );

        return false;
    }

    /**
     * Checks whether the class has a natural identifier/pk (which means it does
     * not use any Id generator.
     *
     * @return bool
     */
    public function isIdentifierNatural()
    {
        return $this->generatorType === self::GENERATOR_TYPE_NONE;
    }

    /**
     * Checks whether the class use a UUID for id generation.
     *
     * @deprecated
     *
     * @return bool
     */
    public function isIdentifierUuid()
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/9046',
            '%s is deprecated',
            __METHOD__
        );

        return $this->generatorType === self::GENERATOR_TYPE_UUID;
    }

    /**
     * Gets the type of a field.
     *
     * @param string $fieldName
     *
     * @return string|null
     *
     * @todo 3.0 Remove this. PersisterHelper should fix it somehow
     */
    public function getTypeOfField($fieldName)
    {
        return isset($this->fieldMappings[$fieldName])
            ? $this->fieldMappings[$fieldName]['type']
            : null;
    }

    /**
     * Gets the type of a column.
     *
     * @deprecated 3.0 remove this. this method is bogus and unreliable, since it cannot resolve the type of a column
     *             that is derived by a referenced field on a different entity.
     *
     * @param string $columnName
     *
     * @return string|null
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
     * Gets primary table's schema name.
     *
     * @return string|null
     */
    public function getSchemaName()
    {
        return $this->table['schema'] ?? null;
    }

    /**
     * Gets the table name to use for temporary identifier tables of this class.
     *
     * @return string
     */
    public function getTemporaryIdTableName()
    {
        // replace dots with underscores because PostgreSQL creates temporary tables in a special schema
        return str_replace('.', '_', $this->getTableName() . '_id_tmp');
    }

    /**
     * Sets the mapped subclasses of this class.
     *
     * @psalm-param list<string> $subclasses The names of all mapped subclasses.
     *
     * @return void
     */
    public function setSubclasses(array $subclasses)
    {
        foreach ($subclasses as $subclass) {
            $this->subClasses[] = $this->fullyQualifiedClassName($subclass);
        }
    }

    /**
     * Sets the parent class names.
     * Assumes that the class names in the passed array are in the order:
     * directParent -> directParentParent -> directParentParentParent ... -> root.
     *
     * @psalm-param list<class-string> $classNames
     *
     * @return void
     */
    public function setParentClasses(array $classNames)
    {
        $this->parentClasses = $classNames;

        if (count($classNames) > 0) {
            $this->rootEntityName = array_pop($classNames);
        }
    }

    /**
     * Sets the inheritance type used by the class and its subclasses.
     *
     * @param int $type
     * @psalm-param self::INHERITANCE_TYPE_* $type
     *
     * @return void
     *
     * @throws MappingException
     */
    public function setInheritanceType($type)
    {
        if (! $this->isInheritanceType($type)) {
            throw MappingException::invalidInheritanceType($this->name, $type);
        }

        $this->inheritanceType = $type;
    }

    /**
     * Sets the association to override association mapping of property for an entity relationship.
     *
     * @param string $fieldName
     * @psalm-param array<string, mixed> $overrideMapping
     *
     * @return void
     *
     * @throws MappingException
     */
    public function setAssociationOverride($fieldName, array $overrideMapping)
    {
        if (! isset($this->associationMappings[$fieldName])) {
            throw MappingException::invalidOverrideFieldName($this->name, $fieldName);
        }

        $mapping = $this->associationMappings[$fieldName];

        //if (isset($mapping['inherited']) && (count($overrideMapping) !== 1 || ! isset($overrideMapping['fetch']))) {
            // TODO: Deprecate overriding the fetch mode via association override for 3.0,
            // users should do this with a listener and a custom attribute/annotation
            // TODO: Enable this exception in 2.8
            //throw MappingException::illegalOverrideOfInheritedProperty($this->name, $fieldName);
        //}

        if (isset($overrideMapping['joinColumns'])) {
            $mapping['joinColumns'] = $overrideMapping['joinColumns'];
        }

        if (isset($overrideMapping['inversedBy'])) {
            $mapping['inversedBy'] = $overrideMapping['inversedBy'];
        }

        if (isset($overrideMapping['joinTable'])) {
            $mapping['joinTable'] = $overrideMapping['joinTable'];
        }

        if (isset($overrideMapping['fetch'])) {
            $mapping['fetch'] = $overrideMapping['fetch'];
        }

        $mapping['joinColumnFieldNames']       = null;
        $mapping['joinTableColumns']           = null;
        $mapping['sourceToTargetKeyColumns']   = null;
        $mapping['relationToSourceKeyColumns'] = null;
        $mapping['relationToTargetKeyColumns'] = null;

        switch ($mapping['type']) {
            case self::ONE_TO_ONE:
                $mapping = $this->_validateAndCompleteOneToOneMapping($mapping);
                break;
            case self::ONE_TO_MANY:
                $mapping = $this->_validateAndCompleteOneToManyMapping($mapping);
                break;
            case self::MANY_TO_ONE:
                $mapping = $this->_validateAndCompleteOneToOneMapping($mapping);
                break;
            case self::MANY_TO_MANY:
                $mapping = $this->_validateAndCompleteManyToManyMapping($mapping);
                break;
        }

        $this->associationMappings[$fieldName] = $mapping;
    }

    /**
     * Sets the override for a mapped field.
     *
     * @param string $fieldName
     * @psalm-param array<string, mixed> $overrideMapping
     *
     * @return void
     *
     * @throws MappingException
     */
    public function setAttributeOverride($fieldName, array $overrideMapping)
    {
        if (! isset($this->fieldMappings[$fieldName])) {
            throw MappingException::invalidOverrideFieldName($this->name, $fieldName);
        }

        $mapping = $this->fieldMappings[$fieldName];

        //if (isset($mapping['inherited'])) {
            // TODO: Enable this exception in 2.8
            //throw MappingException::illegalOverrideOfInheritedProperty($this->name, $fieldName);
        //}

        if (isset($mapping['id'])) {
            $overrideMapping['id'] = $mapping['id'];
        }

        if (! isset($overrideMapping['type'])) {
            $overrideMapping['type'] = $mapping['type'];
        }

        if (! isset($overrideMapping['fieldName'])) {
            $overrideMapping['fieldName'] = $mapping['fieldName'];
        }

        if ($overrideMapping['type'] !== $mapping['type']) {
            throw MappingException::invalidOverrideFieldType($this->name, $fieldName);
        }

        unset($this->fieldMappings[$fieldName]);
        unset($this->fieldNames[$mapping['columnName']]);
        unset($this->columnNames[$mapping['fieldName']]);

        $overrideMapping = $this->validateAndCompleteFieldMapping($overrideMapping);

        $this->fieldMappings[$fieldName] = $overrideMapping;
    }

    /**
     * Checks whether a mapped field is inherited from an entity superclass.
     *
     * @param string $fieldName
     *
     * @return bool TRUE if the field is inherited, FALSE otherwise.
     */
    public function isInheritedField($fieldName)
    {
        return isset($this->fieldMappings[$fieldName]['inherited']);
    }

    /**
     * Checks if this entity is the root in any entity-inheritance-hierarchy.
     *
     * @return bool
     */
    public function isRootEntity()
    {
        return $this->name === $this->rootEntityName;
    }

    /**
     * Checks whether a mapped association field is inherited from a superclass.
     *
     * @param string $fieldName
     *
     * @return bool TRUE if the field is inherited, FALSE otherwise.
     */
    public function isInheritedAssociation($fieldName)
    {
        return isset($this->associationMappings[$fieldName]['inherited']);
    }

    /**
     * @param string $fieldName
     *
     * @return bool
     */
    public function isInheritedEmbeddedClass($fieldName)
    {
        return isset($this->embeddedClasses[$fieldName]['inherited']);
    }

    /**
     * Sets the name of the primary table the class is mapped to.
     *
     * @deprecated Use {@link setPrimaryTable}.
     *
     * @param string $tableName The table name.
     *
     * @return void
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
     * @psalm-param array<string, mixed> $table The table description.
     *
     * @return void
     */
    public function setPrimaryTable(array $table)
    {
        if (isset($table['name'])) {
            // Split schema and table name from a table name like "myschema.mytable"
            if (str_contains($table['name'], '.')) {
                [$this->table['schema'], $table['name']] = explode('.', $table['name'], 2);
            }

            if ($table['name'][0] === '`') {
                $table['name']         = trim($table['name'], '`');
                $this->table['quoted'] = true;
            }

            $this->table['name'] = $table['name'];
        }

        if (isset($table['quoted'])) {
            $this->table['quoted'] = $table['quoted'];
        }

        if (isset($table['schema'])) {
            $this->table['schema'] = $table['schema'];
        }

        if (isset($table['indexes'])) {
            $this->table['indexes'] = $table['indexes'];
        }

        if (isset($table['uniqueConstraints'])) {
            $this->table['uniqueConstraints'] = $table['uniqueConstraints'];
        }

        if (isset($table['options'])) {
            $this->table['options'] = $table['options'];
        }
    }

    /**
     * Checks whether the given type identifies an inheritance type.
     *
     * @return bool TRUE if the given type identifies an inheritance type, FALSE otherwise.
     */
    private function isInheritanceType(int $type): bool
    {
        return $type === self::INHERITANCE_TYPE_NONE ||
                $type === self::INHERITANCE_TYPE_SINGLE_TABLE ||
                $type === self::INHERITANCE_TYPE_JOINED ||
                $type === self::INHERITANCE_TYPE_TABLE_PER_CLASS;
    }

    /**
     * Adds a mapped field to the class.
     *
     * @psalm-param array<string, mixed> $mapping The field mapping.
     *
     * @return void
     *
     * @throws MappingException
     */
    public function mapField(array $mapping)
    {
        $mapping = $this->validateAndCompleteFieldMapping($mapping);
        $this->assertFieldNotMapped($mapping['fieldName']);

        if (isset($mapping['generated'])) {
            $this->requiresFetchAfterChange = true;
        }

        $this->fieldMappings[$mapping['fieldName']] = $mapping;
    }

    /**
     * INTERNAL:
     * Adds an association mapping without completing/validating it.
     * This is mainly used to add inherited association mappings to derived classes.
     *
     * @psalm-param array<string, mixed> $mapping
     *
     * @return void
     *
     * @throws MappingException
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
     * @psalm-param array<string, mixed> $fieldMapping
     *
     * @return void
     */
    public function addInheritedFieldMapping(array $fieldMapping)
    {
        $this->fieldMappings[$fieldMapping['fieldName']] = $fieldMapping;
        $this->columnNames[$fieldMapping['fieldName']]   = $fieldMapping['columnName'];
        $this->fieldNames[$fieldMapping['columnName']]   = $fieldMapping['fieldName'];
    }

    /**
     * INTERNAL:
     * Adds a named query to this class.
     *
     * @deprecated
     *
     * @psalm-param array<string, mixed> $queryMapping
     *
     * @return void
     *
     * @throws MappingException
     */
    public function addNamedQuery(array $queryMapping)
    {
        if (! isset($queryMapping['name'])) {
            throw MappingException::nameIsMandatoryForQueryMapping($this->name);
        }

        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/issues/8592',
            'Named Queries are deprecated, here "%s" on entity %s. Move the query logic into EntityRepository',
            $queryMapping['name'],
            $this->name
        );

        if (isset($this->namedQueries[$queryMapping['name']])) {
            throw MappingException::duplicateQueryMapping($this->name, $queryMapping['name']);
        }

        if (! isset($queryMapping['query'])) {
            throw MappingException::emptyQueryMapping($this->name, $queryMapping['name']);
        }

        $name  = $queryMapping['name'];
        $query = $queryMapping['query'];
        $dql   = str_replace('__CLASS__', $this->name, $query);

        $this->namedQueries[$name] = [
            'name'  => $name,
            'query' => $query,
            'dql'   => $dql,
        ];
    }

    /**
     * INTERNAL:
     * Adds a named native query to this class.
     *
     * @deprecated
     *
     * @psalm-param array<string, mixed> $queryMapping
     *
     * @return void
     *
     * @throws MappingException
     */
    public function addNamedNativeQuery(array $queryMapping)
    {
        if (! isset($queryMapping['name'])) {
            throw MappingException::nameIsMandatoryForQueryMapping($this->name);
        }

        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/issues/8592',
            'Named Native Queries are deprecated, here "%s" on entity %s. Move the query logic into EntityRepository',
            $queryMapping['name'],
            $this->name
        );

        if (isset($this->namedNativeQueries[$queryMapping['name']])) {
            throw MappingException::duplicateQueryMapping($this->name, $queryMapping['name']);
        }

        if (! isset($queryMapping['query'])) {
            throw MappingException::emptyQueryMapping($this->name, $queryMapping['name']);
        }

        if (! isset($queryMapping['resultClass']) && ! isset($queryMapping['resultSetMapping'])) {
            throw MappingException::missingQueryMapping($this->name, $queryMapping['name']);
        }

        $queryMapping['isSelfClass'] = false;

        if (isset($queryMapping['resultClass'])) {
            if ($queryMapping['resultClass'] === '__CLASS__') {
                $queryMapping['isSelfClass'] = true;
                $queryMapping['resultClass'] = $this->name;
            }

            $queryMapping['resultClass'] = $this->fullyQualifiedClassName($queryMapping['resultClass']);
            $queryMapping['resultClass'] = ltrim($queryMapping['resultClass'], '\\');
        }

        $this->namedNativeQueries[$queryMapping['name']] = $queryMapping;
    }

    /**
     * INTERNAL:
     * Adds a sql result set mapping to this class.
     *
     * @psalm-param array<string, mixed> $resultMapping
     *
     * @return void
     *
     * @throws MappingException
     */
    public function addSqlResultSetMapping(array $resultMapping)
    {
        if (! isset($resultMapping['name'])) {
            throw MappingException::nameIsMandatoryForSqlResultSetMapping($this->name);
        }

        if (isset($this->sqlResultSetMappings[$resultMapping['name']])) {
            throw MappingException::duplicateResultSetMapping($this->name, $resultMapping['name']);
        }

        if (isset($resultMapping['entities'])) {
            foreach ($resultMapping['entities'] as $key => $entityResult) {
                if (! isset($entityResult['entityClass'])) {
                    throw MappingException::missingResultSetMappingEntity($this->name, $resultMapping['name']);
                }

                $entityResult['isSelfClass'] = false;
                if ($entityResult['entityClass'] === '__CLASS__') {
                    $entityResult['isSelfClass'] = true;
                    $entityResult['entityClass'] = $this->name;
                }

                $entityResult['entityClass'] = $this->fullyQualifiedClassName($entityResult['entityClass']);

                $resultMapping['entities'][$key]['entityClass'] = ltrim($entityResult['entityClass'], '\\');
                $resultMapping['entities'][$key]['isSelfClass'] = $entityResult['isSelfClass'];

                if (isset($entityResult['fields'])) {
                    foreach ($entityResult['fields'] as $k => $field) {
                        if (! isset($field['name'])) {
                            throw MappingException::missingResultSetMappingFieldName($this->name, $resultMapping['name']);
                        }

                        if (! isset($field['column'])) {
                            $fieldName = $field['name'];
                            if (str_contains($fieldName, '.')) {
                                [, $fieldName] = explode('.', $fieldName);
                            }

                            $resultMapping['entities'][$key]['fields'][$k]['column'] = $fieldName;
                        }
                    }
                }
            }
        }

        $this->sqlResultSetMappings[$resultMapping['name']] = $resultMapping;
    }

    /**
     * Adds a one-to-one mapping.
     *
     * @param array<string, mixed> $mapping The mapping.
     *
     * @return void
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
     * @psalm-param array<string, mixed> $mapping The mapping.
     *
     * @return void
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
     * @psalm-param array<string, mixed> $mapping The mapping.
     *
     * @return void
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
     * @psalm-param array<string, mixed> $mapping The mapping.
     *
     * @return void
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
     * @psalm-param array<string, mixed> $assocMapping
     *
     * @return void
     *
     * @throws MappingException
     */
    protected function _storeAssociationMapping(array $assocMapping)
    {
        $sourceFieldName = $assocMapping['fieldName'];

        $this->assertFieldNotMapped($sourceFieldName);

        $this->associationMappings[$sourceFieldName] = $assocMapping;
    }

    /**
     * Registers a custom repository class for the entity class.
     *
     * @param string|null $repositoryClassName The class name of the custom mapper.
     * @psalm-param class-string<EntityRepository>|null $repositoryClassName
     *
     * @return void
     */
    public function setCustomRepositoryClass($repositoryClassName)
    {
        $this->customRepositoryClassName = $this->fullyQualifiedClassName($repositoryClassName);
    }

    /**
     * Dispatches the lifecycle event of the given entity to the registered
     * lifecycle callbacks and lifecycle listeners.
     *
     * @deprecated Deprecated since version 2.4 in favor of \Doctrine\ORM\Event\ListenersInvoker
     *
     * @param string $lifecycleEvent The lifecycle event.
     * @param object $entity         The Entity on which the event occurred.
     *
     * @return void
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
     *
     * @return bool
     */
    public function hasLifecycleCallbacks($lifecycleEvent)
    {
        return isset($this->lifecycleCallbacks[$lifecycleEvent]);
    }

    /**
     * Gets the registered lifecycle callbacks for an event.
     *
     * @param string $event
     *
     * @return string[]
     * @psalm-return list<string>
     */
    public function getLifecycleCallbacks($event)
    {
        return $this->lifecycleCallbacks[$event] ?? [];
    }

    /**
     * Adds a lifecycle callback for entities of this class.
     *
     * @param string $callback
     * @param string $event
     *
     * @return void
     */
    public function addLifecycleCallback($callback, $event)
    {
        if ($this->isEmbeddedClass) {
            Deprecation::trigger(
                'doctrine/orm',
                'https://github.com/doctrine/orm/pull/8381',
                'Registering lifecycle callback %s on Embedded class %s is not doing anything and will throw exception in 3.0',
                $event,
                $this->name
            );
        }

        if (isset($this->lifecycleCallbacks[$event]) && in_array($callback, $this->lifecycleCallbacks[$event], true)) {
            return;
        }

        $this->lifecycleCallbacks[$event][] = $callback;
    }

    /**
     * Sets the lifecycle callbacks for entities of this class.
     * Any previously registered callbacks are overwritten.
     *
     * @psalm-param array<string, list<string>> $callbacks
     *
     * @return void
     */
    public function setLifecycleCallbacks(array $callbacks)
    {
        $this->lifecycleCallbacks = $callbacks;
    }

    /**
     * Adds a entity listener for entities of this class.
     *
     * @param string $eventName The entity lifecycle event.
     * @param string $class     The listener class.
     * @param string $method    The listener callback method.
     *
     * @return void
     *
     * @throws MappingException
     */
    public function addEntityListener($eventName, $class, $method)
    {
        $class = $this->fullyQualifiedClassName($class);

        $listener = [
            'class'  => $class,
            'method' => $method,
        ];

        if (! class_exists($class)) {
            throw MappingException::entityListenerClassNotFound($class, $this->name);
        }

        if (! method_exists($class, $method)) {
            throw MappingException::entityListenerMethodNotFound($class, $method, $this->name);
        }

        if (isset($this->entityListeners[$eventName]) && in_array($listener, $this->entityListeners[$eventName], true)) {
            throw MappingException::duplicateEntityListener($class, $method, $this->name);
        }

        $this->entityListeners[$eventName][] = $listener;
    }

    /**
     * Sets the discriminator column definition.
     *
     * @see getDiscriminatorColumn()
     *
     * @param mixed[]|null $columnDef
     * @psalm-param array<string, mixed>|null $columnDef
     *
     * @return void
     *
     * @throws MappingException
     */
    public function setDiscriminatorColumn($columnDef)
    {
        if ($columnDef !== null) {
            if (! isset($columnDef['name'])) {
                throw MappingException::nameIsMandatoryForDiscriminatorColumns($this->name);
            }

            if (isset($this->fieldNames[$columnDef['name']])) {
                throw MappingException::duplicateColumnName($this->name, $columnDef['name']);
            }

            if (! isset($columnDef['fieldName'])) {
                $columnDef['fieldName'] = $columnDef['name'];
            }

            if (! isset($columnDef['type'])) {
                $columnDef['type'] = 'string';
            }

            if (in_array($columnDef['type'], ['boolean', 'array', 'object', 'datetime', 'time', 'date'], true)) {
                throw MappingException::invalidDiscriminatorColumnType($this->name, $columnDef['type']);
            }

            $this->discriminatorColumn = $columnDef;
        }
    }

    /**
     * @return array<string, mixed>
     */
    final public function getDiscriminatorColumn(): array
    {
        if ($this->discriminatorColumn === null) {
            throw new LogicException('The discriminator column was not set.');
        }

        return $this->discriminatorColumn;
    }

    /**
     * Sets the discriminator values used by this class.
     * Used for JOINED and SINGLE_TABLE inheritance mapping strategies.
     *
     * @psalm-param array<string, class-string> $map
     *
     * @return void
     */
    public function setDiscriminatorMap(array $map)
    {
        foreach ($map as $value => $className) {
            $this->addDiscriminatorMapClass($value, $className);
        }
    }

    /**
     * Adds one entry of the discriminator map with a new class and corresponding name.
     *
     * @param string $name
     * @param string $className
     * @psalm-param class-string $className
     *
     * @return void
     *
     * @throws MappingException
     */
    public function addDiscriminatorMapClass($name, $className)
    {
        $className = $this->fullyQualifiedClassName($className);
        $className = ltrim($className, '\\');

        $this->discriminatorMap[$name] = $className;

        if ($this->name === $className) {
            $this->discriminatorValue = $name;

            return;
        }

        if (! (class_exists($className) || interface_exists($className))) {
            throw MappingException::invalidClassInDiscriminatorMap($className, $this->name);
        }

        if (is_subclass_of($className, $this->name) && ! in_array($className, $this->subClasses, true)) {
            $this->subClasses[] = $className;
        }
    }

    /**
     * Checks whether the class has a named query with the given query name.
     *
     * @param string $queryName
     *
     * @return bool
     */
    public function hasNamedQuery($queryName)
    {
        return isset($this->namedQueries[$queryName]);
    }

    /**
     * Checks whether the class has a named native query with the given query name.
     *
     * @param string $queryName
     *
     * @return bool
     */
    public function hasNamedNativeQuery($queryName)
    {
        return isset($this->namedNativeQueries[$queryName]);
    }

    /**
     * Checks whether the class has a named native query with the given query name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasSqlResultSetMapping($name)
    {
        return isset($this->sqlResultSetMappings[$name]);
    }

    /**
     * {@inheritDoc}
     */
    public function hasAssociation($fieldName)
    {
        return isset($this->associationMappings[$fieldName]);
    }

    /**
     * {@inheritDoc}
     */
    public function isSingleValuedAssociation($fieldName)
    {
        return isset($this->associationMappings[$fieldName])
            && ($this->associationMappings[$fieldName]['type'] & self::TO_ONE);
    }

    /**
     * {@inheritDoc}
     */
    public function isCollectionValuedAssociation($fieldName)
    {
        return isset($this->associationMappings[$fieldName])
            && ! ($this->associationMappings[$fieldName]['type'] & self::TO_ONE);
    }

    /**
     * Is this an association that only has a single join column?
     *
     * @param string $fieldName
     *
     * @return bool
     */
    public function isAssociationWithSingleJoinColumn($fieldName)
    {
        return isset($this->associationMappings[$fieldName])
            && isset($this->associationMappings[$fieldName]['joinColumns'][0])
            && ! isset($this->associationMappings[$fieldName]['joinColumns'][1]);
    }

    /**
     * Returns the single association join column (if any).
     *
     * @param string $fieldName
     *
     * @return string
     *
     * @throws MappingException
     */
    public function getSingleAssociationJoinColumnName($fieldName)
    {
        if (! $this->isAssociationWithSingleJoinColumn($fieldName)) {
            throw MappingException::noSingleAssociationJoinColumnFound($this->name, $fieldName);
        }

        return $this->associationMappings[$fieldName]['joinColumns'][0]['name'];
    }

    /**
     * Returns the single association referenced join column name (if any).
     *
     * @param string $fieldName
     *
     * @return string
     *
     * @throws MappingException
     */
    public function getSingleAssociationReferencedJoinColumnName($fieldName)
    {
        if (! $this->isAssociationWithSingleJoinColumn($fieldName)) {
            throw MappingException::noSingleAssociationJoinColumnFound($this->name, $fieldName);
        }

        return $this->associationMappings[$fieldName]['joinColumns'][0]['referencedColumnName'];
    }

    /**
     * Used to retrieve a fieldname for either field or association from a given column.
     *
     * This method is used in foreign-key as primary-key contexts.
     *
     * @param string $columnName
     *
     * @return string
     *
     * @throws MappingException
     */
    public function getFieldForColumn($columnName)
    {
        if (isset($this->fieldNames[$columnName])) {
            return $this->fieldNames[$columnName];
        }

        foreach ($this->associationMappings as $assocName => $mapping) {
            if (
                $this->isAssociationWithSingleJoinColumn($assocName) &&
                $this->associationMappings[$assocName]['joinColumns'][0]['name'] === $columnName
            ) {
                return $assocName;
            }
        }

        throw MappingException::noFieldNameFoundForColumn($this->name, $columnName);
    }

    /**
     * Sets the ID generator used to generate IDs for instances of this class.
     *
     * @param AbstractIdGenerator $generator
     *
     * @return void
     */
    public function setIdGenerator($generator)
    {
        $this->idGenerator = $generator;
    }

    /**
     * Sets definition.
     *
     * @psalm-param array<string, string|null> $definition
     *
     * @return void
     */
    public function setCustomGeneratorDefinition(array $definition)
    {
        $this->customGeneratorDefinition = $definition;
    }

    /**
     * Sets the definition of the sequence ID generator for this class.
     *
     * The definition must have the following structure:
     * <code>
     * array(
     *     'sequenceName'   => 'name',
     *     'allocationSize' => 20,
     *     'initialValue'   => 1
     *     'quoted'         => 1
     * )
     * </code>
     *
     * @psalm-param array{sequenceName?: string, allocationSize?: int|string, initialValue?: int|string, quoted?: mixed} $definition
     *
     * @return void
     *
     * @throws MappingException
     */
    public function setSequenceGeneratorDefinition(array $definition)
    {
        if (! isset($definition['sequenceName']) || trim($definition['sequenceName']) === '') {
            throw MappingException::missingSequenceName($this->name);
        }

        if ($definition['sequenceName'][0] === '`') {
            $definition['sequenceName'] = trim($definition['sequenceName'], '`');
            $definition['quoted']       = true;
        }

        if (! isset($definition['allocationSize']) || trim((string) $definition['allocationSize']) === '') {
            $definition['allocationSize'] = '1';
        }

        if (! isset($definition['initialValue']) || trim((string) $definition['initialValue']) === '') {
            $definition['initialValue'] = '1';
        }

        $definition['allocationSize'] = (string) $definition['allocationSize'];
        $definition['initialValue']   = (string) $definition['initialValue'];

        $this->sequenceGeneratorDefinition = $definition;
    }

    /**
     * Sets the version field mapping used for versioning. Sets the default
     * value to use depending on the column type.
     *
     * @psalm-param array<string, mixed> $mapping The version field mapping array.
     *
     * @return void
     *
     * @throws MappingException
     */
    public function setVersionMapping(array &$mapping)
    {
        $this->isVersioned              = true;
        $this->versionField             = $mapping['fieldName'];
        $this->requiresFetchAfterChange = true;

        if (! isset($mapping['default'])) {
            if (in_array($mapping['type'], ['integer', 'bigint', 'smallint'], true)) {
                $mapping['default'] = 1;
            } elseif ($mapping['type'] === 'datetime') {
                $mapping['default'] = 'CURRENT_TIMESTAMP';
            } else {
                throw MappingException::unsupportedOptimisticLockingType($this->name, $mapping['fieldName'], $mapping['type']);
            }
        }
    }

    /**
     * Sets whether this class is to be versioned for optimistic locking.
     *
     * @param bool $bool
     *
     * @return void
     */
    public function setVersioned($bool)
    {
        $this->isVersioned = $bool;

        if ($bool) {
            $this->requiresFetchAfterChange = true;
        }
    }

    /**
     * Sets the name of the field that is to be used for versioning if this class is
     * versioned for optimistic locking.
     *
     * @param string $versionField
     *
     * @return void
     */
    public function setVersionField($versionField)
    {
        $this->versionField = $versionField;
    }

    /**
     * Marks this class as read only, no change tracking is applied to it.
     *
     * @return void
     */
    public function markReadOnly()
    {
        $this->isReadOnly = true;
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldNames()
    {
        return array_keys($this->fieldMappings);
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationNames()
    {
        return array_keys($this->associationMappings);
    }

    /**
     * {@inheritDoc}
     *
     * @param string $assocName
     *
     * @return string
     * @psalm-return class-string
     *
     * @throws InvalidArgumentException
     */
    public function getAssociationTargetClass($assocName)
    {
        if (! isset($this->associationMappings[$assocName])) {
            throw new InvalidArgumentException("Association name expected, '" . $assocName . "' is not an association.");
        }

        return $this->associationMappings[$assocName]['targetEntity'];
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the (possibly quoted) identifier column names for safe use in an SQL statement.
     *
     * @deprecated Deprecated since version 2.3 in favor of \Doctrine\ORM\Mapping\QuoteStrategy
     *
     * @param AbstractPlatform $platform
     *
     * @return string[]
     * @psalm-return list<string>
     */
    public function getQuotedIdentifierColumnNames($platform)
    {
        $quotedColumnNames = [];

        foreach ($this->identifier as $idProperty) {
            if (isset($this->fieldMappings[$idProperty])) {
                $quotedColumnNames[] = isset($this->fieldMappings[$idProperty]['quoted'])
                    ? $platform->quoteIdentifier($this->fieldMappings[$idProperty]['columnName'])
                    : $this->fieldMappings[$idProperty]['columnName'];

                continue;
            }

            // Association defined as Id field
            $joinColumns            = $this->associationMappings[$idProperty]['joinColumns'];
            $assocQuotedColumnNames = array_map(
                static function ($joinColumn) use ($platform) {
                    return isset($joinColumn['quoted'])
                        ? $platform->quoteIdentifier($joinColumn['name'])
                        : $joinColumn['name'];
                },
                $joinColumns
            );

            $quotedColumnNames = array_merge($quotedColumnNames, $assocQuotedColumnNames);
        }

        return $quotedColumnNames;
    }

    /**
     * Gets the (possibly quoted) column name of a mapped field for safe use  in an SQL statement.
     *
     * @deprecated Deprecated since version 2.3 in favor of \Doctrine\ORM\Mapping\QuoteStrategy
     *
     * @param string           $field
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function getQuotedColumnName($field, $platform)
    {
        return isset($this->fieldMappings[$field]['quoted'])
            ? $platform->quoteIdentifier($this->fieldMappings[$field]['columnName'])
            : $this->fieldMappings[$field]['columnName'];
    }

    /**
     * Gets the (possibly quoted) primary table name of this class for safe use in an SQL statement.
     *
     * @deprecated Deprecated since version 2.3 in favor of \Doctrine\ORM\Mapping\QuoteStrategy
     *
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function getQuotedTableName($platform)
    {
        return isset($this->table['quoted'])
            ? $platform->quoteIdentifier($this->table['name'])
            : $this->table['name'];
    }

    /**
     * Gets the (possibly quoted) name of the join table.
     *
     * @deprecated Deprecated since version 2.3 in favor of \Doctrine\ORM\Mapping\QuoteStrategy
     *
     * @param mixed[]          $assoc
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function getQuotedJoinTableName(array $assoc, $platform)
    {
        return isset($assoc['joinTable']['quoted'])
            ? $platform->quoteIdentifier($assoc['joinTable']['name'])
            : $assoc['joinTable']['name'];
    }

    /**
     * {@inheritDoc}
     */
    public function isAssociationInverseSide($fieldName)
    {
        return isset($this->associationMappings[$fieldName])
            && ! $this->associationMappings[$fieldName]['isOwningSide'];
    }

    /**
     * {@inheritDoc}
     */
    public function getAssociationMappedByTargetField($fieldName)
    {
        return $this->associationMappings[$fieldName]['mappedBy'];
    }

    /**
     * @param string $targetClass
     *
     * @return mixed[][]
     * @psalm-return array<string, array<string, mixed>>
     */
    public function getAssociationsByTargetClass($targetClass)
    {
        $relations = [];

        foreach ($this->associationMappings as $mapping) {
            if ($mapping['targetEntity'] === $targetClass) {
                $relations[$mapping['fieldName']] = $mapping;
            }
        }

        return $relations;
    }

    /**
     * @param string|null $className
     * @psalm-param string|class-string|null $className
     *
     * @return string|null null if the input value is null
     * @psalm-return class-string|null
     */
    public function fullyQualifiedClassName($className)
    {
        if (empty($className)) {
            return $className;
        }

        if (! str_contains($className, '\\') && $this->namespace) {
            return $this->namespace . '\\' . $className;
        }

        return $className;
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getMetadataValue($name)
    {
        if (isset($this->$name)) {
            return $this->$name;
        }

        return null;
    }

    /**
     * Map Embedded Class
     *
     * @psalm-param array<string, mixed> $mapping
     *
     * @return void
     *
     * @throws MappingException
     */
    public function mapEmbedded(array $mapping)
    {
        $this->assertFieldNotMapped($mapping['fieldName']);

        if (! isset($mapping['class']) && $this->isTypedProperty($mapping['fieldName'])) {
            $type = $this->reflClass->getProperty($mapping['fieldName'])->getType();
            if ($type instanceof ReflectionNamedType) {
                $mapping['class'] = $type->getName();
            }
        }

        $this->embeddedClasses[$mapping['fieldName']] = [
            'class' => $this->fullyQualifiedClassName($mapping['class']),
            'columnPrefix' => $mapping['columnPrefix'] ?? null,
            'declaredField' => $mapping['declaredField'] ?? null,
            'originalField' => $mapping['originalField'] ?? null,
        ];
    }

    /**
     * Inline the embeddable class
     *
     * @param string $property
     *
     * @return void
     */
    public function inlineEmbeddable($property, ClassMetadataInfo $embeddable)
    {
        foreach ($embeddable->fieldMappings as $fieldMapping) {
            $fieldMapping['originalClass'] = $fieldMapping['originalClass'] ?? $embeddable->name;
            $fieldMapping['declaredField'] = isset($fieldMapping['declaredField'])
                ? $property . '.' . $fieldMapping['declaredField']
                : $property;
            $fieldMapping['originalField'] = $fieldMapping['originalField'] ?? $fieldMapping['fieldName'];
            $fieldMapping['fieldName']     = $property . '.' . $fieldMapping['fieldName'];

            if (! empty($this->embeddedClasses[$property]['columnPrefix'])) {
                $fieldMapping['columnName'] = $this->embeddedClasses[$property]['columnPrefix'] . $fieldMapping['columnName'];
            } elseif ($this->embeddedClasses[$property]['columnPrefix'] !== false) {
                $fieldMapping['columnName'] = $this->namingStrategy
                    ->embeddedFieldToColumnName(
                        $property,
                        $fieldMapping['columnName'],
                        $this->reflClass->name,
                        $embeddable->reflClass->name
                    );
            }

            $this->mapField($fieldMapping);
        }
    }

    /**
     * @throws MappingException
     */
    private function assertFieldNotMapped(string $fieldName): void
    {
        if (
            isset($this->fieldMappings[$fieldName]) ||
            isset($this->associationMappings[$fieldName]) ||
            isset($this->embeddedClasses[$fieldName])
        ) {
            throw MappingException::duplicateFieldMapping($this->name, $fieldName);
        }
    }

    /**
     * Gets the sequence name based on class metadata.
     *
     * @return string
     *
     * @todo Sequence names should be computed in DBAL depending on the platform
     */
    public function getSequenceName(AbstractPlatform $platform)
    {
        $sequencePrefix = $this->getSequencePrefix($platform);
        $columnName     = $this->getSingleIdentifierColumnName();

        return $sequencePrefix . '_' . $columnName . '_seq';
    }

    /**
     * Gets the sequence name prefix based on class metadata.
     *
     * @return string
     *
     * @todo Sequence names should be computed in DBAL depending on the platform
     */
    public function getSequencePrefix(AbstractPlatform $platform)
    {
        $tableName      = $this->getTableName();
        $sequencePrefix = $tableName;

        // Prepend the schema name to the table name if there is one
        $schemaName = $this->getSchemaName();
        if ($schemaName) {
            $sequencePrefix = $schemaName . '.' . $tableName;

            if (! $platform->supportsSchemas() && $platform->canEmulateSchemas()) {
                $sequencePrefix = $schemaName . '__' . $tableName;
            }
        }

        return $sequencePrefix;
    }

    /**
     * @psalm-param array<string, mixed> $mapping
     */
    private function assertMappingOrderBy(array $mapping): void
    {
        if (isset($mapping['orderBy']) && ! is_array($mapping['orderBy'])) {
            throw new InvalidArgumentException("'orderBy' is expected to be an array, not " . gettype($mapping['orderBy']));
        }
    }

    /**
     * @psalm-param class-string $class
     */
    private function getAccessibleProperty(ReflectionService $reflService, string $class, string $field): ?ReflectionProperty
    {
        $reflectionProperty = $reflService->getAccessibleProperty($class, $field);
        if ($reflectionProperty !== null && PHP_VERSION_ID >= 80100 && $reflectionProperty->isReadOnly()) {
            $reflectionProperty = new ReflectionReadonlyProperty($reflectionProperty);
        }

        return $reflectionProperty;
    }
}

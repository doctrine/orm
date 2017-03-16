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
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Mapping;

use BadMethodCallException;
use Doctrine\Common\ClassLoader;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use Doctrine\Instantiator\Instantiator;
use Doctrine\ORM\Cache\CacheException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Utility\PersisterHelper;
use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;

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
 * @author Roman Borschel <roman@code-factory.org>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @since 2.0
 */
class ClassMetadata implements ClassMetadataInterface
{
    /**
     * READ-ONLY: The name of the entity class.
     *
     * @var string
     */
    public $name;

    /**
     * READ-ONLY: The name of the entity class that is at the root of the mapped entity inheritance
     * hierarchy. If the entity is not part of a mapped inheritance hierarchy this is the same
     * as {@link $name}.
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
     * READ-ONLY: Whether this class describes the mapping of an embeddable class.
     *
     * @var boolean
     */
    public $isEmbeddedClass = false;

    /**
     * READ-ONLY: Whether this class describes the mapping of a read-only class.
     * That means it is never considered for change-tracking in the UnitOfWork.
     * It is a very helpful performance optimization for entities that are immutable,
     * either in your domain or through the relation database (coming from a view,
     * or a history table for example).
     *
     * @var boolean
     */
    public $isReadOnly = false;

    /**
     * READ-ONLY: The names of the parent classes (ancestors).
     *
     * @var array
     */
    public $parentClasses = [];

    /**
     * READ-ONLY: The names of all subclasses (descendants).
     *
     * @var array
     */
    public $subClasses = [];

    /**
     * READ-ONLY: The names of all embedded classes based on properties.
     *
     * @var array
     */
    //public $embeddedClasses = [];

    /**
     * READ-ONLY: The named queries allowed to be called directly from Repository.
     *
     * @var array
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
     * @var array
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
     * @var array
     */
    public $sqlResultSetMappings = [];

    /**
     * READ-ONLY: The registered lifecycle callbacks for entities of this class.
     *
     * @var array
     */
    public $lifecycleCallbacks = [];

    /**
     * READ-ONLY: The registered entity listeners.
     *
     * @var array
     */
    public $entityListeners = [];

    /**
     * READ-ONLY: The field names of all fields that are part of the identifier/primary key
     * of the mapped entity class.
     *
     * @var array
     */
    public $identifier = [];

    /**
     * READ-ONLY: Flag indicating whether the identifier/primary key of the class is composite.
     *
     * @var boolean
     */
    public $isIdentifierComposite = false;

    /**
     * READ-ONLY: The inheritance mapping type used by the class.
     *
     * @var string
     */
    public $inheritanceType = InheritanceType::NONE;

    /**
     * READ-ONLY: The Id generator type used by the class.
     *
     * @var string
     */
    public $generatorType = GeneratorType::NONE;

    /**
     * READ-ONLY: The policy used for change-tracking on entities of this class.
     *
     * @var string
     */
    public $changeTrackingPolicy = ChangeTrackingPolicy::DEFERRED_IMPLICIT;

    /**
     * READ-ONLY: The definition of the identity generator of this class.
     * In case of SEQUENCE generation strategy, the definition has the following structure:
     * <code>
     * array(
     *     'sequenceName'   => 'name',
     *     'allocationSize' => 20,
     * )
     * </code>
     *
     * In case of CUSTOM generation strategy, the definition has the following structure:
     * <code>
     * array(
     *     'class' => 'ClassName',
     * )
     * </code>
     *
     * @var array
     *
     * @todo Remove!
     */
    public $generatorDefinition;

    /**
     * READ-ONLY: The ID generator used for generating IDs for this class.
     *
     * @var \Doctrine\ORM\Sequencing\Generator
     *
     * @todo Remove!
     */
    public $idGenerator;

    /**
     * READ-ONLY: The discriminator value of this class.
     *
     * <b>This does only apply to the JOINED and SINGLE_TABLE inheritance mapping strategies
     * where a discriminator column is used.</b>
     *
     * @var mixed
     *
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
     *
     * @see discriminatorColumn
     */
    public $discriminatorMap = [];

    /**
     * READ-ONLY: The definition of the discriminator column used in JOINED and SINGLE_TABLE
     * inheritance mappings.
     *
     * @var DiscriminatorColumnMetadata
     */
    public $discriminatorColumn;

    /**
     * READ-ONLY: The primary table metadata.
     *
     * @var TableMetadata
     */
    public $table;

    /**
     * READ-ONLY: An array of field names. Used to look up field names from column names.
     * Keys are column names and values are field names.
     *
     * @var array
     */
    public $fieldNames = [];

    /**
     * READ-ONLY: The property mappings of this class.
     *
     * @var array<Property>
     */
    protected $properties = [];

    /**
     * READ-ONLY: The association mappings of this class.
     *
     * @var array<AssociationMetadata>
     */
    public $associationMappings = [];

    /**
     * READ-ONLY: The field which is used for versioning in optimistic locking (if any).
     *
     * @var FieldMetadata|null
     */
    public $versionProperty = null;

    /**
     * @var null|CacheMetadata
     */
    public $cache = null;

    /**
     * The ReflectionClass instance of the mapped class.
     *
     * @var ReflectionClass
     */
    public $reflClass;

    /**
     * NamingStrategy determining the default column and table names.
     *
     * @var \Doctrine\ORM\Mapping\NamingStrategy
     */
    protected $namingStrategy;

    /**
     * @var \Doctrine\Instantiator\InstantiatorInterface|null
     */
    private $instantiator;

    /**
     * Initializes a new ClassMetadata instance that will hold the object-relational mapping
     * metadata of the class with the given name.
     *
     * @param string              $entityName     The name of the entity class the new instance is used for.
     * @param NamingStrategy|null $namingStrategy
     */
    public function __construct($entityName, NamingStrategy $namingStrategy = null)
    {
        $this->name           = $entityName;
        $this->rootEntityName = $entityName;
        $this->table          = new TableMetadata();
        $this->namingStrategy = $namingStrategy ?: new DefaultNamingStrategy();
        $this->instantiator   = new Instantiator();
    }

    /**
     * Extracts the identifier values of an entity of this class.
     *
     * For composite identifiers, the identifier values are returned as an array
     * with the same order as the field order in {@link identifier}.
     *
     * @param object $entity
     *
     * @return array
     */
    public function getIdentifierValues($entity)
    {
        if ($this->isIdentifierComposite) {
            $id = [];

            foreach ($this->identifier as $idField) {
                if (($property = $this->getProperty($idField)) === null) {
                    $property = $this->associationMappings[$idField];
                }

                $value = $property->getValue($entity);

                if (null !== $value) {
                    $id[$idField] = $value;
                }
            }

            return $id;
        }

        $idField = $this->identifier[0];

        if (($property = $this->getProperty($idField)) === null) {
            $property = $this->associationMappings[$idField];
        }

        $value = $property->getValue($entity);

        if (null === $value) {
            return [];
        }

        return [$idField => $value];
    }

    /**
     * Populates the entity identifier of an entity.
     *
     * @param object $entity
     * @param array  $id
     *
     * @return void
     */
    public function assignIdentifier($entity, array $id)
    {
        foreach ($id as $idField => $idValue) {
            if (($property = $this->getProperty($idField)) === null) {
                $property = $this->associationMappings[$idField];
            }

            $property->setValue($entity, $idValue);
        }
    }
    
    /**
     * Handles metadata cloning nicely.
     */
    public function __clone()
    {
        if ($this->cache) {
            $this->cache = clone $this->cache;
        }

        foreach ($this->properties as $name => $property) {
            $this->properties[$name] = clone $property;
        }

        foreach ($this->associationMappings as $name => $association) {
            $this->associationMappings[$name] = clone $association;
        }
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
        $serialized = [
            'associationMappings',
            'properties',
            'fieldNames',
            //'embeddedClasses',
            'identifier',
            'isIdentifierComposite', // TODO: REMOVE
            'name',
            'table',
            'rootEntityName',
            'idGenerator', //TODO: Does not really need to be serialized. Could be moved to runtime.
        ];

        // The rest of the metadata is only serialized if necessary.
        if ($this->changeTrackingPolicy != ChangeTrackingPolicy::DEFERRED_IMPLICIT) {
            $serialized[] = 'changeTrackingPolicy';
        }

        if ($this->customRepositoryClassName) {
            $serialized[] = 'customRepositoryClassName';
        }

        if ($this->inheritanceType != InheritanceType::NONE) {
            $serialized[] = 'inheritanceType';
            $serialized[] = 'discriminatorColumn';
            $serialized[] = 'discriminatorValue';
            $serialized[] = 'discriminatorMap';
            $serialized[] = 'parentClasses';
            $serialized[] = 'subClasses';
        }

        if ($this->generatorType !== GeneratorType::NONE) {
            $serialized[] = 'generatorType';
        }

        if ($this->generatorDefinition) {
            $serialized[] = "generatorDefinition";
        }

        if ($this->isMappedSuperclass) {
            $serialized[] = 'isMappedSuperclass';
        }

        if ($this->isEmbeddedClass) {
            $serialized[] = 'isEmbeddedClass';
        }

        if ($this->isVersioned()) {
            $serialized[] = 'versionProperty';
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

        if ($this->cache) {
            $serialized[] = 'cache';
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
     * @param \Doctrine\Common\Persistence\Mapping\ReflectionService $reflService
     *
     * @return void
     */
    public function wakeupReflection($reflService)
    {
        // Restore ReflectionClass and properties
        $this->reflClass    = $reflService->getClass($this->name);
        $this->instantiator = $this->instantiator ?: new Instantiator();

        $parentReflFields = [];

        /*foreach ($this->embeddedClasses as $property => $embeddedClass) {
            if (isset($embeddedClass['declaredField'])) {
                $parentReflFields[$property] = new ReflectionEmbeddedProperty(
                    $parentReflFields[$embeddedClass['declaredField']],
                    $reflService->getAccessibleProperty(
                        $this->embeddedClasses[$embeddedClass['declaredField']]['class'],
                        $embeddedClass['originalField']
                    ),
                    $this->embeddedClasses[$embeddedClass['declaredField']]['class']
                );

                continue;
            }

            $fieldRefl = $reflService->getAccessibleProperty(
                isset($embeddedClass['declared']) ? $embeddedClass['declared'] : $this->name,
                $property
            );

            // @todo guilhermeblanco Handle reflection initialization once embeddables are back.
            $parentReflFields[$property] = $fieldRefl;
        }*/

        foreach ($this->properties as $field => $property) {
            /*if (isset($mapping['declaredField']) && isset($parentReflFields[$mapping['declaredField']])) {
                // @todo guilhermeblanco Handle reflection initialization once embeddables are back.
                $this->reflection[$field] = new ReflectionEmbeddedProperty(
                    $parentReflFields[$mapping['declaredField']],
                    $reflService->getAccessibleProperty($mapping['originalClass'], $mapping['originalField']),
                    $mapping['originalClass']
                );
                continue;
            }*/

            $property->wakeupReflection($reflService);
        }

        foreach ($this->associationMappings as $field => $property) {
            $property->wakeupReflection($reflService);
        }
    }

    /**
     * Initializes a new ClassMetadata instance that will hold the object-relational mapping
     * metadata of the class with the given name.
     *
     * @param \Doctrine\Common\Persistence\Mapping\ReflectionService $reflService The reflection service.
     *
     * @return void
     */
    public function initializeReflection($reflService)
    {
        $this->reflClass = $reflService->getClass($this->name);

        if ($this->reflClass) {
            $this->name = $this->rootEntityName = $this->reflClass->getName();
        }

        if (empty($this->table->getName())) {
            $this->table->setName($this->namingStrategy->classToTableName($this->name));
        }
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
        if ( ! $this->identifier) {
            throw MappingException::identifierRequired($this->name);
        }

        if ($this->generatorType !== GeneratorType::NONE && $this->isIdentifierComposite) {
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
        foreach ($this->associationMappings as $fieldName => $property) {
            $targetEntity = $property->getTargetEntity();

            if ( ! class_exists($targetEntity, true)) {
                throw MappingException::invalidTargetEntityClass($targetEntity, $this->name, $fieldName);
            }
        }
    }

    /**
     * Validates lifecycle callbacks.
     *
     * @param \Doctrine\Common\Persistence\Mapping\ReflectionService $reflService
     *
     * @return void
     *
     * @throws MappingException
     */
    public function validateLifecycleCallbacks($reflService)
    {
        foreach ($this->lifecycleCallbacks as $callbacks) {
            foreach ($callbacks as $callbackFuncName) {
                if ( ! $reflService->hasPublicMethod($this->name, $callbackFuncName)) {
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
     * @param null|CacheMetadata $cache
     *
     * @return void
     */
    public function setCache(CacheMetadata $cache = null)
    {
        $this->cache = $cache;
    }

    /**
     * @param string $fieldName
     * @param array  $cache
     * 
     * @todo guilhermeblanco Remove me once Association is OOed
     *
     * @return void
     */
    public function enableAssociationCache($fieldName, array $cache)
    {
        $this->associationMappings[$fieldName]->setCache($this->getAssociationCacheDefaults($fieldName, $cache));
    }

    /**
     * @param string $fieldName
     * @param array  $cache
     * 
     * @todo guilhermeblanco Remove me once Association is OOed
     *
     * @return array
     */
    public function getAssociationCacheDefaults($fieldName, array $cache)
    {
        $region = $cache['region'] ?? strtolower(str_replace('\\', '_', $this->rootEntityName)) . '__' . $fieldName;
        $usage  = $cache['usage'] ?? null;
        
        if (! $usage) {
            $usage = $this->cache->getUsage() !== null
                ? $this->cache->getUsage()
                : CacheUsage::READ_ONLY
            ;
        }
        
        $builder = new Builder\CacheMetadataBuilder();
        
        $builder
            ->withRegion($region)
            ->withUsage($usage)
        ;
        
        return $builder->build();
    }

    /**
     * Sets the change tracking policy used by this class.
     *
     * @param integer $policy
     *
     * @return void
     */
    public function setChangeTrackingPolicy($policy)
    {
        $this->changeTrackingPolicy = $policy;
    }

    /**
     * Checks whether a field is part of the identifier/primary key field(s).
     *
     * @param string $fieldName The field name.
     *
     * @return boolean TRUE if the field is part of the table identifier/primary key field(s),
     *                 FALSE otherwise.
     */
    public function isIdentifier($fieldName)
    {
        if ( ! $this->identifier) {
            return false;
        }

        if ( ! $this->isIdentifierComposite) {
            return $fieldName === $this->identifier[0];
        }

        return in_array($fieldName, $this->identifier, true);
    }

    /**
     * Gets the named query.
     *
     * @see ClassMetadata::$namedQueries
     *
     * @param string $queryName The query name.
     *
     * @return string
     *
     * @throws MappingException
     */
    public function getNamedQuery($queryName)
    {
        if ( ! isset($this->namedQueries[$queryName])) {
            throw MappingException::queryNotFound($this->name, $queryName);
        }

        return $this->namedQueries[$queryName]['dql'];
    }

    /**
     * Gets all named queries of the class.
     *
     * @return array
     */
    public function getNamedQueries()
    {
        return $this->namedQueries;
    }

    /**
     * Gets the named native query.
     *
     * @see ClassMetadata::$namedNativeQueries
     *
     * @param string $queryName The query name.
     *
     * @return array
     *
     * @throws MappingException
     */
    public function getNamedNativeQuery($queryName)
    {
        if ( ! isset($this->namedNativeQueries[$queryName])) {
            throw MappingException::queryNotFound($this->name, $queryName);
        }

        return $this->namedNativeQueries[$queryName];
    }

    /**
     * Gets all named native queries of the class.
     *
     * @return array
     */
    public function getNamedNativeQueries()
    {
        return $this->namedNativeQueries;
    }

    /**
     * Gets the result set mapping.
     *
     * @see ClassMetadata::$sqlResultSetMappings
     *
     * @param string $name The result set mapping name.
     *
     * @return array
     *
     * @throws MappingException
     */
    public function getSqlResultSetMapping($name)
    {
        if ( ! isset($this->sqlResultSetMappings[$name])) {
            throw MappingException::resultMappingNotFound($this->name, $name);
        }

        return $this->sqlResultSetMappings[$name];
    }

    /**
     * Gets all sql result set mappings of the class.
     *
     * @return array
     */
    public function getSqlResultSetMappings()
    {
        return $this->sqlResultSetMappings;
    }

    /**
     * Validates & completes the basic mapping information that is common to all
     * association mappings (one-to-one, many-ot-one, one-to-many, many-to-many).
     *
     * @param AssociationMetadata $property
     *
     * @return array The updated mapping.
     *
     * @throws MappingException If something is wrong with the mapping.
     */
    protected function validateAndCompleteAssociationMapping(AssociationMetadata $property)
    {
        // Mandatory attributes for both sides: fieldName, targetEntity
        $fieldName    = $property->getName();
        $targetEntity = $property->getTargetEntity();

        if (! $fieldName) {
            throw MappingException::missingFieldName($this->name);
        }

        if (! $targetEntity) {
            throw MappingException::missingTargetEntity($fieldName);
        }

        $targetEntity = ltrim($this->fullyQualifiedClassName($targetEntity), '\\');

        $property->setDeclaringClass($this);
        $property->setSourceEntity($this->name);
        $property->setOwningSide($property->getMappedBy() === null);
        $property->setTargetEntity($targetEntity);

        // Mandatory and optional attributes for either side
        if ($property->getMappedBy()) {
            $property->setOwningSide(false);
        }

        // Complete id mapping
        if ($property->isPrimaryKey()) {
            if ($property->isOrphanRemoval()) {
                throw MappingException::illegalOrphanRemovalOnIdentifierAssociation($this->name, $fieldName);
            }

            if ( ! in_array($property->getName(), $this->identifier)) {
                if ($property instanceof ToOneAssociationMetadata && count($property->getJoinColumns()) >= 2) {
                    throw MappingException::cannotMapCompositePrimaryKeyEntitiesAsForeignId(
                        $property->getTargetEntity(),
                        $this->name,
                        $fieldName
                    );
                }

                $this->identifier[] = $property->getName();
            }

            // Check for composite key
            if ( ! $this->isIdentifierComposite && count($this->identifier) > 1) {
                $this->isIdentifierComposite = true;
            }

            if ($this->cache && !$property->getCache()) {
                throw CacheException::nonCacheableEntityAssociation($this->name, $fieldName);
            }

            if ($property instanceof ToManyAssociationMetadata) {
                throw MappingException::illegalToManyIdentifierAssociation($this->name, $property->getName());
            }
        }

        // Cascades
        $cascadeTypes = ['remove', 'persist', 'refresh', 'merge', 'detach'];
        $cascades     = array_map('strtolower', $property->getCascade());

        if (in_array('all', $cascades)) {
            $cascades = $cascadeTypes;
        }

        if (count($cascades) !== count(array_intersect($cascades, $cascadeTypes))) {
            $diffCascades = array_diff($cascades, array_intersect($cascades, $cascadeTypes));

            throw MappingException::invalidCascadeOption($diffCascades, $this->name, $fieldName);
        }

        $property->setCascade($cascades);
    }

    /**
     * Validates & completes a to-one association mapping.
     *
     * @param ToOneAssociationMetadata $property The association mapping to validate & complete.
     *
     * @throws \RuntimeException
     * @throws MappingException
     */
    protected function validateAndCompleteToOneAssociationMetadata(ToOneAssociationMetadata $property)
    {
        $fieldName = $property->getName();

        if ($property->getJoinColumns()) {
            $property->setOwningSide(true);
        }

        if ($property->isOwningSide()) {
            if (empty($property->getJoinColumns())) {
                // Apply default join column
                $property->addJoinColumn(new JoinColumnMetadata());
            }

            $uniqueConstraintColumns = [];

            foreach ($property->getJoinColumns() as $joinColumn) {
                if ($property instanceof OneToOneAssociationMetadata && $this->inheritanceType !== InheritanceType::SINGLE_TABLE) {
                    if (1 === count($property->getJoinColumns())) {
                        if (! $property->isPrimaryKey()) {
                            $joinColumn->setUnique(true);
                        }
                    } else {
                        $uniqueConstraintColumns[] = $joinColumn->getColumnName();
                    }
                }

                $joinColumn->setTableName(! $this->isMappedSuperclass ? $this->getTableName() : null);

                if (! $joinColumn->getColumnName()) {
                    $joinColumn->setColumnName($this->namingStrategy->joinColumnName($fieldName, $this->name));
                }

                if (! $joinColumn->getReferencedColumnName()) {
                    $joinColumn->setReferencedColumnName($this->namingStrategy->referenceColumnName());
                }

                $this->fieldNames[$joinColumn->getColumnName()] = $fieldName;
            }

            if ($uniqueConstraintColumns) {
                if ( ! $this->table) {
                    throw new RuntimeException(
                        "ClassMetadata::setTable() has to be called before defining a one to one relationship."
                    );
                }

                $this->table->addUniqueConstraint(
                    [
                        'name'    => sprintf('%s_uniq', $fieldName),
                        'columns' => $uniqueConstraintColumns,
                        'options' => [],
                        'flags'   => [],
                    ]
                );
            }
        }

        if ($property->isOrphanRemoval()) {
            $cascades = $property->getCascade();

            if (! in_array('remove', $cascades)) {
                $cascades[] = 'remove';

                $property->setCascade($cascades);
            }

            // @todo guilhermeblanco where is this used?
            // @todo guilhermeblanco Shouldn￿'t we iterate through JoinColumns to set non-uniqueness?
            //$property->setUnique(false);
        }

        if ($property->isPrimaryKey() && ! $property->isOwningSide()) {
            throw MappingException::illegalInverseIdentifierAssociation($this->name, $fieldName);
        }
    }

    /**
     * Validates & completes a to-many association mapping.
     *
     * @param ToManyAssociationMetadata $property The association mapping to validate & complete.
     *
     * @throws RuntimeException
     * @throws MappingException
     */
    protected function validateAndCompleteToManyAssociationMetadata(ToManyAssociationMetadata $property)
    {
        if ($property->isPrimaryKey()) {
            throw MappingException::illegalToManyIdentifierAssociation($this->name, $property->getName());
        }
    }

    /**
     * Validates & completes a one-to-one association mapping.
     *
     * @param OneToOneAssociationMetadata $property The association mapping to validate & complete.
     *
     * @throws \RuntimeException
     * @throws MappingException
     */
    protected function validateAndCompleteOneToOneMapping(OneToOneAssociationMetadata $property)
    {
        // Do nothing
    }

    /**
     * Validates & completes a many-to-one association mapping.
     *
     * @param ManyToOneAssociationMetadata $property The association mapping to validate & complete.
     *
     * @throws \RuntimeException
     * @throws MappingException
     */
    protected function validateAndCompleteManyToOneMapping(ManyToOneAssociationMetadata $property)
    {
        // A many-to-one mapping is essentially a one-one backreference
        if ($property->isOrphanRemoval()) {
            throw MappingException::illegalOrphanRemoval($this->name, $property->getName());
        }
    }

    /**
     * Validates & completes a one-to-many association mapping.
     *
     * @param OneToManyAssociationMetadata $property The association mapping to validate & complete.
     *
     * @throws \RuntimeException
     * @throws MappingException
     */
    protected function validateAndCompleteOneToManyMapping(OneToManyAssociationMetadata $property)
    {
        // OneToMany MUST be inverse side
        $property->setOwningSide(false);

        // OneToMany MUST have mappedBy
        if (! $property->getMappedBy()) {
            throw MappingException::oneToManyRequiresMappedBy($property->getName());
        }

        if ($property->isOrphanRemoval()) {
            $cascades = $property->getCascade();

            if (! in_array('remove', $cascades)) {
                $cascades[] = 'remove';

                $property->setCascade($cascades);
            }
        }
    }

    /**
     * Validates & completes a many-to-many association mapping.
     *
     * @param ManyToManyAssociationMetadata $property The association mapping to validate & complete.
     *
     * @throws \RuntimeException
     * @throws MappingException
     */
    protected function validateAndCompleteManyToManyMapping(ManyToManyAssociationMetadata $property)
    {
        if ($property->isOwningSide()) {
            // owning side MUST have a join table
            $joinTable = $property->getJoinTable() ?: new JoinTableMetadata();

            $property->setJoinTable($joinTable);

            if (! $joinTable->getName()) {
                $joinTableName = $this->namingStrategy->joinTableName(
                    $property->getSourceEntity(),
                    $property->getTargetEntity(),
                    $property->getName()
                );

                $joinTable->setName($joinTableName);
            }

            $selfReferencingEntityWithoutJoinColumns = $property->getSourceEntity() == $property->getTargetEntity() && ! $joinTable->hasColumns();

            if (! $joinTable->getJoinColumns()) {
                $referencedColumnName = $this->namingStrategy->referenceColumnName();
                $sourceReferenceName  = $selfReferencingEntityWithoutJoinColumns ? 'source' : $referencedColumnName;
                $columnName           = $this->namingStrategy->joinKeyColumnName($property->getSourceEntity(), $sourceReferenceName);
                $joinColumn           = new JoinColumnMetadata();

                $joinColumn->setColumnName($columnName);
                $joinColumn->setReferencedColumnName($referencedColumnName);
                $joinColumn->setOnDelete('CASCADE');

                $joinTable->addJoinColumn($joinColumn);
            }

            if (! $joinTable->getInverseJoinColumns()) {
                $referencedColumnName = $this->namingStrategy->referenceColumnName();
                $targetReferenceName  = $selfReferencingEntityWithoutJoinColumns ? 'target' : $referencedColumnName;
                $columnName           = $this->namingStrategy->joinKeyColumnName($property->getTargetEntity(), $targetReferenceName);
                $joinColumn           = new JoinColumnMetadata();

                $joinColumn->setColumnName($columnName);
                $joinColumn->setReferencedColumnName($referencedColumnName);
                $joinColumn->setOnDelete('CASCADE');

                $joinTable->addInverseJoinColumn($joinColumn);
            }

            foreach ($joinTable->getJoinColumns() as $joinColumn) {
                if (! $joinColumn->getReferencedColumnName()) {
                    $joinColumn->setReferencedColumnName($this->namingStrategy->referenceColumnName());
                }

                $referencedColumnName = $joinColumn->getReferencedColumnName();

                if (! $joinColumn->getColumnName()) {
                    $columnName = $this->namingStrategy->joinKeyColumnName(
                        $property->getSourceEntity(),
                        $referencedColumnName
                    );

                    $joinColumn->setColumnName($columnName);
                }
            }

            foreach ($joinTable->getInverseJoinColumns() as $inverseJoinColumn) {
                if (! $inverseJoinColumn->getReferencedColumnName()) {
                    $inverseJoinColumn->setReferencedColumnName($this->namingStrategy->referenceColumnName());
                }

                $referencedColumnName = $inverseJoinColumn->getReferencedColumnName();

                if (! $inverseJoinColumn->getColumnName()) {
                    $columnName = $this->namingStrategy->joinKeyColumnName(
                        $property->getTargetEntity(),
                        $referencedColumnName
                    );

                    $inverseJoinColumn->setColumnName($columnName);
                }
            }
        }
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
     * INTERNAL:
     * Sets the mapped identifier/primary key fields of this class.
     * Mainly used by the ClassMetadataFactory to assign inherited identifiers.
     *
     * @param array $identifier
     *
     * @return void
     */
    public function setIdentifier(array $identifier)
    {
        $this->identifier = $identifier;
        $this->isIdentifierComposite = count($this->identifier) > 1;
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
        return isset($this->properties[$fieldName]);
    }

    /**
     * Returns an array with identifier column names and their corresponding ColumnMetadata.
     *
     * @return array
     */
    public function getIdentifierColumns(EntityManagerInterface $em)
    {
        $columns = [];

        foreach ($this->identifier as $idProperty) {
            if (($property = $this->getProperty($idProperty)) !== null) {
                $columns[$property->getColumnName()] = $property;

                continue;
            }

            // Association defined as Id field
            $association  = $this->associationMappings[$idProperty];
            $targetClass  = $em->getClassMetadata($association->getTargetEntity());

            if (! $association->isOwningSide()) {
                $association = $targetClass->associationMappings[$association->getMappedBy()];
                $targetClass = $em->getClassMetadata($association->getTargetEntity());
            }

            $joinColumns = $association instanceof ManyToManyAssociationMetadata
                ? $association->getJoinTable()->getInverseJoinColumns()
                : $association->getJoinColumns()
            ;

            foreach ($joinColumns as $joinColumn) {
                $joinColumn->setType(
                    PersisterHelper::getTypeOfColumn($joinColumn->getReferencedColumnName(), $targetClass, $em)
                );

                $columns[$joinColumn->getColumnName()] = $joinColumn;
            }
        }

        return $columns;
    }

    /**
     * Sets the type of Id generator to use for the mapped class.
     *
     * @param int $generatorType
     *
     * @return void
     */
    public function setIdGeneratorType($generatorType)
    {
        $this->generatorType = $generatorType;
    }

    /**
     * Gets the name of the primary table.
     *
     * @return string
     */
    public function getTableName()
    {
        return $this->table->getName();
    }

    /**
     * Gets primary table's schema name.
     *
     * @return string|null
     */
    public function getSchemaName()
    {
        return $this->table->getSchema();
    }

    /**
     * Gets the table name to use for temporary identifier tables of this class.
     *
     * @return string
     */
    public function getTemporaryIdTableName()
    {
        $schema = empty($this->getSchemaName())
            ? ''
            : $this->getSchemaName() . '_'
        ;

        // replace dots with underscores because PostgreSQL creates temporary tables in a special schema
        return $schema . $this->getTableName() . '_id_tmp';
    }

    /**
     * Sets the mapped subclasses of this class.
     *
     * @param array $subclasses The names of all mapped subclasses.
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
     * @param array $classNames
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
     * @param integer $type
     *
     * @return void
     *
     * @throws MappingException
     */
    public function setInheritanceType($type)
    {
        if ( ! $this->isInheritanceType($type)) {
            throw MappingException::invalidInheritanceType($this->name, $type);
        }

        $this->inheritanceType = $type;
    }

    /**
     * Sets the association to override association mapping of property for an entity relationship.
     *
     * @param AssociationMetadata $associationMetadata
     *
     * @return void
     *
     * @throws MappingException
     */
    public function setAssociationOverride(AssociationMetadata $associationMetadata)
    {
        $fieldName = $associationMetadata->getName();

        if ( ! isset($this->associationMappings[$fieldName])) {
            throw MappingException::invalidOverrideFieldName($this->name, $fieldName);
        }

        /** @var AssociationMetadata $originalAssociation */
        $originalAssociation = $this->associationMappings[$fieldName];

        // Do not allow to change association type
        if (get_class($originalAssociation) !== get_class($associationMetadata)) {
            throw MappingException::invalidOverrideAssociationType($this->name, $fieldName);
        }

        unset($this->associationMappings[$originalAssociation->getName()]);

        // Unset all defined fieldNames prior to override
        if ($originalAssociation instanceof ToOneAssociationMetadata && $originalAssociation->isOwningSide()) {
            foreach ($originalAssociation->getJoinColumns() as $joinColumn) {
                unset($this->fieldNames[$joinColumn->getColumnName()]);
            }
        }

        // Override what it should be allowed
        if ($associationMetadata->getInversedBy()) {
            $originalAssociation->setInversedBy($associationMetadata->getInversedBy());
        }

        if ($originalAssociation instanceof ToOneAssociationMetadata && $associationMetadata->getJoinColumns()) {
            $originalAssociation->setJoinColumns($associationMetadata->getJoinColumns());
        } else if ($originalAssociation instanceof ManyToManyAssociationMetadata && $associationMetadata->getJoinTable()) {
            $originalAssociation->setJoinTable($associationMetadata->getJoinTable());
        }

        $this->addAssociation($originalAssociation);
    }

    /**
     * Sets the override for a mapped field.
     *
     * @param FieldMetadata $fieldMetadata
     *
     * @return void
     *
     * @throws MappingException
     */
    public function setAttributeOverride(FieldMetadata $fieldMetadata)
    {
        $originalProperty = $this->getProperty($fieldMetadata->getName());

        if ( ! $originalProperty) {
            throw MappingException::invalidOverrideFieldName($this->name, $fieldMetadata->getName());
        }

        if ($originalProperty instanceof VersionFieldMetadata) {
            throw MappingException::invalidOverrideVersionField($this->name, $fieldMetadata->getName());
        }

        $fieldMetadata->setDeclaringClass($originalProperty->getDeclaringClass());
        $fieldMetadata->setPrimaryKey($originalProperty->isPrimaryKey());

        unset($this->properties[$originalProperty->getName()]);
        unset($this->fieldNames[$originalProperty->getColumnName()]);

        $this->addProperty($fieldMetadata);
    }

    /**
     * Gets the type of a field.
     *
     * @param string $fieldName
     *
     * @return \Doctrine\DBAL\Types\Type|string|null
     *
     * @todo 3.0 Remove this. PersisterHelper should fix it somehow
     */
    public function getTypeOfField($fieldName)
    {
        return isset($this->properties[$fieldName])
            ? $this->properties[$fieldName]->getType()
            : null;
    }

    /**
     * Checks if this entity is the root in any entity-inheritance-hierarchy.
     *
     * @return bool
     */
    public function isRootEntity()
    {
        return $this->name == $this->rootEntityName;
    }

    /**
     * Checks whether a mapped field is inherited from a superclass.
     *
     * @param string $fieldName
     *
     * @return boolean TRUE if the field is inherited, FALSE otherwise.
     */
    public function isInheritedProperty($fieldName)
    {
        $declaringClass = $this->properties[$fieldName]->getDeclaringClass();

        return ! ($declaringClass->name === $this->name);
    }

    /**
     * Checks whether a mapped association is inherited from a superclass.
     *
     * @param string $fieldName
     *
     * @return boolean TRUE if the association is inherited, FALSE otherwise.
     */
    public function isInheritedAssociation($fieldName)
    {
        $declaringClass = $this->associationMappings[$fieldName]->getDeclaringClass();

        return ! ($declaringClass->name === $this->name);
    }

    /**
     * Sets the primary table metadata.
     *
     * @param TableMetadata $tableMetadata
     *
     * @return void
     */
    public function setPrimaryTable(TableMetadata $tableMetadata)
    {
        $this->table = $tableMetadata;
    }

    /**
     * Checks whether the given type identifies an inheritance type.
     *
     * @param integer $type
     *
     * @return boolean TRUE if the given type identifies an inheritance type, FALSe otherwise.
     */
    private function isInheritanceType($type)
    {
        return $type == InheritanceType::NONE
            || $type == InheritanceType::SINGLE_TABLE
            || $type == InheritanceType::JOINED
            || $type == InheritanceType::TABLE_PER_CLASS;
    }

    /**
     * @return array<Property>
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Add a property mapping.
     *
     * @param FieldMetadata $property
     *
     * @throws MappingException
     */
    public function addProperty(FieldMetadata $property)
    {
        $fieldName  = $property->getName();
        $columnName = $property->getColumnName();

        $property->setDeclaringClass($this);

        // Check for empty field name
        if (empty($fieldName)) {
            throw MappingException::missingFieldName($this->name);
        }

        // Check for duplicated property
        $this->assertPropertyNotMapped($fieldName);

        if (empty($columnName)) {
            $columnName = $this->namingStrategy->propertyToColumnName($fieldName, $this->name);

            $property->setColumnName($columnName);
        }

        if (! $this->isMappedSuperclass) {
            $property->setTableName($this->getTableName());
        }

        // Check for already declared column
        if (isset($this->fieldNames[$columnName]) ||
            ($this->discriminatorColumn !== null && $this->discriminatorColumn->getColumnName() === $columnName)) {
            throw MappingException::duplicateColumnName($this->name, $columnName);
        }

        // Complete id mapping
        if ($property->isPrimaryKey()) {
            if ($this->versionProperty !== null && $this->versionProperty->getName() === $fieldName) {
                throw MappingException::cannotVersionIdField($this->name, $fieldName);
            }

            assert(
                ! $property->getType()->canRequireSQLConversion(),
                MappingException::sqlConversionNotAllowedForPrimaryKeyProperties($property)
            );

            if (! in_array($fieldName, $this->identifier)) {
                $this->identifier[] = $fieldName;
            }

            // Check for composite key
            if (! $this->isIdentifierComposite && count($this->identifier) > 1) {
                $this->isIdentifierComposite = true;
            }
        }

        $this->fieldNames[$columnName] = $fieldName;
        $this->properties[$fieldName] = $property;
    }

    /**
     * @param string $fieldName
     *
     * @return Property|null
     */
    public function getProperty($fieldName)
    {
        return $this->properties[$fieldName] ?? null;
    }

    /**
     * INTERNAL:
     * Adds a field mapping without completing/validating it.
     * This is mainly used to add inherited field mappings to derived classes.
     *
     * @param Property $property
     *
     * @return void
     */
    public function addInheritedProperty(Property $property)
    {
        $inheritedProperty = clone $property;
        $declaringClass    = $property->getDeclaringClass();

        if (! $declaringClass->isMappedSuperclass) {
            $inheritedProperty->setTableName($property->getTableName());
        }

        $this->assertPropertyNotMapped($property->getName());

        $this->fieldNames[$property->getColumnName()] = $property->getName();
        $this->properties[$property->getName()] = $inheritedProperty;
    }

    /**
     * INTERNAL:
     * Adds an association mapping without completing/validating it.
     * This is mainly used to add inherited association mappings to derived classes.
     *
     * @param AssociationMetadata $association
     *
     * @return void
     *
     * @throws MappingException
     */
    public function addInheritedAssociation(AssociationMetadata $association)
    {
        /* @var AssociationMetadata $inheritedAssociation */
        $inheritedAssociation = clone $association;
        $declaringClass       = $association->getDeclaringClass();

        if ($declaringClass->isMappedSuperclass) {
            $inheritedAssociation->setSourceEntity($this->name);
        }

        $this->assertPropertyNotMapped($association->getName());

        $this->associationMappings[$association->getName()] = $inheritedAssociation;
    }

    /**
     * INTERNAL:
     * Adds a named query to this class.
     *
     * @param array $queryMapping
     *
     * @return void
     *
     * @throws MappingException
     */
    public function addNamedQuery(array $queryMapping)
    {
        if (!isset($queryMapping['name'])) {
            throw MappingException::nameIsMandatoryForQueryMapping($this->name);
        }

        if (isset($this->namedQueries[$queryMapping['name']])) {
            throw MappingException::duplicateQueryMapping($this->name, $queryMapping['name']);
        }

        if (!isset($queryMapping['query'])) {
            throw MappingException::emptyQueryMapping($this->name, $queryMapping['name']);
        }

        $name   = $queryMapping['name'];
        $query  = $queryMapping['query'];
        $dql    = str_replace('__CLASS__', $this->name, $query);

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
     * @param array $queryMapping
     *
     * @return void
     *
     * @throws MappingException
     */
    public function addNamedNativeQuery(array $queryMapping)
    {
        if (!isset($queryMapping['name'])) {
            throw MappingException::nameIsMandatoryForQueryMapping($this->name);
        }

        if (isset($this->namedNativeQueries[$queryMapping['name']])) {
            throw MappingException::duplicateQueryMapping($this->name, $queryMapping['name']);
        }

        if (!isset($queryMapping['query'])) {
            throw MappingException::emptyQueryMapping($this->name, $queryMapping['name']);
        }

        if (!isset($queryMapping['resultClass']) && !isset($queryMapping['resultSetMapping'])) {
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
     * @param array $resultMapping
     *
     * @return void
     *
     * @throws MappingException
     */
    public function addSqlResultSetMapping(array $resultMapping)
    {
        if (!isset($resultMapping['name'])) {
            throw MappingException::nameIsMandatoryForSqlResultSetMapping($this->name);
        }

        if (isset($this->sqlResultSetMappings[$resultMapping['name']])) {
            throw MappingException::duplicateResultSetMapping($this->name, $resultMapping['name']);
        }

        if (isset($resultMapping['entities'])) {
            foreach ($resultMapping['entities'] as $key => $entityResult) {
                if (!isset($entityResult['entityClass'])) {
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
                        if (!isset($field['name'])) {
                            throw MappingException::missingResultSetMappingFieldName($this->name, $resultMapping['name']);
                        }

                        if (!isset($field['column'])) {
                            $fieldName = $field['name'];

                            if (strpos($fieldName, '.')) {
                                list(, $fieldName) = explode('.', $fieldName);
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
     * Adds an association mapping.
     *
     * @param AssociationMetadata $property
     *
     * @throws \RuntimeException
     * @throws MappingException
     */
    public function addAssociation(AssociationMetadata $property)
    {
        $this->validateAndCompleteAssociationMapping($property);

        if ($property instanceof OneToOneAssociationMetadata) {
            $this->validateAndCompleteToOneAssociationMetadata($property);
            $this->validateAndCompleteOneToOneMapping($property);
        } else if ($property instanceof OneToManyAssociationMetadata) {
            $this->validateAndCompleteToManyAssociationMetadata($property);
            $this->validateAndCompleteOneToManyMapping($property);
        } else if ($property instanceof ManyToOneAssociationMetadata) {
            $this->validateAndCompleteToOneAssociationMetadata($property);
            $this->validateAndCompleteManyToOneMapping($property);
        } else if ($property instanceof ManyToManyAssociationMetadata) {
            $this->validateAndCompleteToManyAssociationMetadata($property);
            $this->validateAndCompleteManyToManyMapping($property);
        }

        $sourceFieldName = $property->getName();

        $this->assertPropertyNotMapped($sourceFieldName);

        $this->associationMappings[$sourceFieldName] = $property;
    }

    /**
     * Registers a custom repository class for the entity class.
     *
     * @param string $repositoryClassName The class name of the custom mapper.
     *
     * @return void
     */
    public function setCustomRepositoryClass($repositoryClassName)
    {
        $this->customRepositoryClassName = $this->fullyQualifiedClassName($repositoryClassName);
    }

    /**
     * Whether the class has any attached lifecycle listeners or callbacks for a lifecycle event.
     *
     * @param string $lifecycleEvent
     *
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
     *
     * @return array
     */
    public function getLifecycleCallbacks($event)
    {
        return isset($this->lifecycleCallbacks[$event]) ? $this->lifecycleCallbacks[$event] : [];
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
        if (isset($this->lifecycleCallbacks[$event]) && in_array($callback, $this->lifecycleCallbacks[$event])) {
            return;
        }

        $this->lifecycleCallbacks[$event][] = $callback;
    }

    /**
     * Sets the lifecycle callbacks for entities of this class.
     * Any previously registered callbacks are overwritten.
     *
     * @param array $callbacks
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
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    public function addEntityListener($eventName, $class, $method)
    {
        $class    = $this->fullyQualifiedClassName($class);

        $listener = [
            'class'  => $class,
            'method' => $method,
        ];

        if ( ! class_exists($class)) {
            throw MappingException::entityListenerClassNotFound($class, $this->name);
        }

        if ( ! method_exists($class, $method)) {
            throw MappingException::entityListenerMethodNotFound($class, $method, $this->name);
        }

        if (isset($this->entityListeners[$eventName]) && in_array($listener, $this->entityListeners[$eventName])) {
            throw MappingException::duplicateEntityListener($class, $method, $this->name);
        }

        $this->entityListeners[$eventName][] = $listener;
    }

    /**
     * Sets the discriminator column definition.
     *
     * @param DiscriminatorColumnMetadata $discriminatorColumn
     *
     * @return void
     *
     * @throws MappingException
     *
     * @see getDiscriminatorColumn()
     */
    public function setDiscriminatorColumn(DiscriminatorColumnMetadata $discriminatorColumn)
    {
        if (empty($discriminatorColumn->getColumnName())) {
            throw MappingException::nameIsMandatoryForDiscriminatorColumns($this->name);
        }

        if (isset($this->fieldNames[$discriminatorColumn->getColumnName()])) {
            throw MappingException::duplicateColumnName($this->name, $discriminatorColumn->getColumnName());
        }

        $discriminatorColumn->setTableName($discriminatorColumn->getTableName() ?? $this->getTableName());

        $allowedTypeList = ["boolean", "array", "object", "datetime", "time", "date"];

        if (in_array($discriminatorColumn->getTypeName(), $allowedTypeList)) {
            throw MappingException::invalidDiscriminatorColumnType($discriminatorColumn->getTypeName());
        }

        $this->discriminatorColumn = $discriminatorColumn;
    }

    /**
     * Sets the discriminator values used by this class.
     * Used for JOINED and SINGLE_TABLE inheritance mapping strategies.
     *
     * @param array $map
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

        if ( ! (class_exists($className) || interface_exists($className))) {
            throw MappingException::invalidClassInDiscriminatorMap($className, $this->name);
        }

        if (is_subclass_of($className, $this->name) && ! in_array($className, $this->subClasses)) {
            $this->subClasses[] = $className;
        }
    }

    /**
     * Checks whether the class has a named query with the given query name.
     *
     * @param string $queryName
     *
     * @return boolean
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
     * @return boolean
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
     * @return boolean
     */
    public function hasSqlResultSetMapping($name)
    {
        return isset($this->sqlResultSetMappings[$name]);
    }

    /**
     * {@inheritDoc}
     *
     * @todo guilhermeblanco Remove this method (it exists in Persistence repo)
     */
    public function hasAssociation($fieldName)
    {
        return isset($this->associationMappings[$fieldName]);
    }

    /**
     * {@inheritDoc}
     *
     * @todo guilhermeblanco Remove this method (it exists in Persistence repo)
     */
    public function isSingleValuedAssociation($fieldName)
    {
        return isset($this->associationMappings[$fieldName])
            && $this->associationMappings[$fieldName] instanceof ToOneAssociationMetadata;
    }

    /**
     * {@inheritDoc}
     *
     * @todo guilhermeblanco Remove this method (it exists in Persistence repo)
     */
    public function isCollectionValuedAssociation($fieldName)
    {
        return isset($this->associationMappings[$fieldName])
            && $this->associationMappings[$fieldName] instanceof ToManyAssociationMetadata;
    }

    /**
     * Sets the ID generator used to generate IDs for instances of this class.
     *
     * @param \Doctrine\ORM\Sequencing\Generator $generator
     *
     * @return void
     */
    public function setIdGenerator($generator)
    {
        $this->idGenerator = $generator;
    }

    /**
     * Sets the generator definition for this class.
     * For sequence definition, it must have the following structure:
     *
     * <code>
     * array(
     *     'sequenceName'   => 'name',
     *     'allocationSize' => 20,
     * )
     * </code>
     *
     * For custom definition, it must have the following structure:
     *
     * <code>
     * array(
     *     'class'     => 'Path\To\ClassName',
     *     'arguments' => [],
     * )
     * </code>
     *
     * @param array $definition
     *
     * @return void
     */
    public function setGeneratorDefinition(array $definition)
    {
        if ($this->generatorType === GeneratorType::SEQUENCE && ! isset($definition['sequenceName'])) {
            throw MappingException::missingSequenceName($this->name);
        }

        $this->generatorDefinition = $definition;
    }

    /**
     * Sets the version field mapping used for versioning. Sets the default
     * value to use depending on the column type.
     *
     * @param VersionFieldMetadata $versionFieldMetadata
     *
     * @return void
     *
     * @throws MappingException
     */
    public function setVersionProperty(VersionFieldMetadata $versionFieldMetadata)
    {
        $this->versionProperty = $versionFieldMetadata;

        $options = $versionFieldMetadata->getOptions();

        if (isset($options['default'])) {
            return;
        }

        if (in_array($versionFieldMetadata->getTypeName(), ['integer', 'bigint', 'smallint'])) {
            $versionFieldMetadata->setOptions(array_merge($options, ['default' => 1]));

            return;
        }

        if ($versionFieldMetadata->getTypeName() === 'datetime') {
            $versionFieldMetadata->setOptions(array_merge($options, ['default' => 'CURRENT_TIMESTAMP']));

            return;
        }

        throw MappingException::unsupportedOptimisticLockingType($versionFieldMetadata->getType());
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
        return array_keys($this->properties);
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
     * @throws InvalidArgumentException
     *
     * @todo guilhermeblanco Remove this method (it exists in Persistence repo)
     */
    public function getAssociationTargetClass($assocName)
    {
        if ( ! isset($this->associationMappings[$assocName])) {
            throw new InvalidArgumentException("Association name expected, '" . $assocName ."' is not an association.");
        }

        return $this->associationMappings[$assocName]->getTargetEntity();
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isVersioned()
    {
        return $this->versionProperty !== null;
    }

    /**
     * {@inheritDoc}
     *
     * @todo guilhermeblanco Remove this method (it exists in Persistence repo)
     */
    public function isAssociationInverseSide($fieldName)
    {
        return isset($this->associationMappings[$fieldName])
            && ! $this->associationMappings[$fieldName]->isOwningSide();
    }

    /**
     * {@inheritDoc}
     *
     * @todo guilhermeblanco Remove this method (it exists in Persistence repo)
     */
    public function getAssociationMappedByTargetField($fieldName)
    {
        return $this->associationMappings[$fieldName]->getMappedBy();
    }

    /**
     * @param string $targetClass
     *
     * @return array
     *
     * @todo guilhermeblanco Remove this method (it exists in Persistence repo). One usage left.
     */
    public function getAssociationsByTargetClass($targetClass)
    {
        $associations = [];

        foreach ($this->associationMappings as $association) {
            if ($association->getTargetEntity() !== $targetClass) {
                continue;
            }

            $associations[$association->getName()] = $association;
        }

        return $associations;
    }

    /**
     * @param  string|null $className
     *
     * @return string|null null if the input value is null
     */
    public function fullyQualifiedClassName($className)
    {
        if (empty($className) || ! $this->reflClass) {
            return $className;
        }

        $namespace = $this->reflClass->getNamespaceName();

        if ($className !== null && strpos($className, '\\') === false && $namespace) {
            return $namespace . '\\' . $className;
        }

        return $className;
    }

    /**
     * Map Embedded Class
     *
     * @param array $mapping
     *
     * @throws MappingException
     * @return void
     */
    public function mapEmbedded(array $mapping)
    {
        /*$this->assertPropertyNotMapped($mapping['fieldName']);

        $this->embeddedClasses[$mapping['fieldName']] = [
            'class'          => $this->fullyQualifiedClassName($mapping['class']),
            'columnPrefix'   => $mapping['columnPrefix'],
            'declaredField'  => isset($mapping['declaredField']) ? $mapping['declaredField'] : null,
            'originalField'  => isset($mapping['originalField']) ? $mapping['originalField'] : null,
            'declaringClass' => $this,
        ];*/
    }

    /**
     * Inline the embeddable class
     *
     * @param string        $property
     * @param ClassMetadata $embeddable
     */
    public function inlineEmbeddable($property, ClassMetadata $embeddable)
    {
        /*foreach ($embeddable->fieldMappings as $fieldName => $fieldMapping) {
            $fieldMapping['fieldName']     = $property . "." . $fieldName;
            $fieldMapping['originalClass'] = $fieldMapping['originalClass'] ?? $embeddable->name;
            $fieldMapping['originalField'] = $fieldMapping['originalField'] ?? $fieldName;
            $fieldMapping['declaredField'] = isset($fieldMapping['declaredField'])
                ? $property . '.' . $fieldMapping['declaredField']
                : $property;

            if (! empty($this->embeddedClasses[$property]['columnPrefix'])) {
                $fieldMapping['columnName'] = $this->embeddedClasses[$property]['columnPrefix'] . $fieldMapping['columnName'];
            } elseif ($this->embeddedClasses[$property]['columnPrefix'] !== false) {
                $fieldMapping['columnName'] = $this->namingStrategy->embeddedFieldToColumnName(
                    $property,
                    $fieldMapping['columnName'],
                    $this->reflClass->name,
                    $embeddable->reflClass->name
                );
            }

            $this->mapField($fieldMapping);
        }*/
    }

    /**
     * @param string $fieldName
     *
     * @throws MappingException
     */
    private function assertPropertyNotMapped(string $fieldName)
    {
        if (isset($this->properties[$fieldName])) {
            throw MappingException::duplicateProperty($this, $this->properties[$fieldName]);
        }

        if (isset($this->associationMappings[$fieldName])) {
            throw MappingException::duplicateProperty($this, $this->associationMappings[$fieldName]);
        }

//        if (isset($this->embeddedClasses[$fieldName])) {
//            throw MappingException::duplicateProperty($this, $this->embeddedClasses[$fieldName]);
//        }
    }

    /**
     * Gets the sequence name based on class metadata.
     *
     * @param AbstractPlatform $platform
     * @return string
     *
     * @todo guilhermeblanco Sequence names should be computed in DBAL depending on the platform
     */
    public function getSequenceName(AbstractPlatform $platform)
    {
        $property = $this->properties[$this->getSingleIdentifierFieldName()];

        return sprintf('%s_%s_seq', $this->getSequencePrefix($platform), $property->getColumnName());
    }

    /**
     * Gets the sequence name prefix based on class metadata.
     *
     * @param AbstractPlatform $platform
     * @return string
     *
     * @todo guilhermeblanco Sequence names should be computed in DBAL depending on the platform
     */
    public function getSequencePrefix(AbstractPlatform $platform)
    {
        $tableName      = $this->getTableName();
        $sequencePrefix = $tableName;

        // Prepend the schema name to the table name if there is one
        if ($schemaName = $this->getSchemaName()) {
            $sequencePrefix = $schemaName . '.' . $tableName;

            if ( ! $platform->supportsSchemas() && $platform->canEmulateSchemas()) {
                $sequencePrefix = $schemaName . '__' . $tableName;
            }
        }

        return $sequencePrefix;
    }
}

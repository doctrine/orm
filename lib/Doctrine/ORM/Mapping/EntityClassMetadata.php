<?php

declare(strict_types = 1);

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

/**
 * Class EntityClassMetadata
 *
 * @package Doctrine\ORM\Mapping
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class EntityClassMetadata extends ComponentMetadata
{
    /**
     * @var null|string The name of the custom repository class used for the entity class.
     */
    protected $customRepositoryClassName;

    /**
     * @var null|Property The field which is used for versioning in optimistic locking (if any).
     */
    protected $declaredVersion = null;

    /**
     * Whether this class describes the mapping of a read-only class.
     * That means it is never considered for change-tracking in the UnitOfWork.
     * It is a very helpful performance optimization for entities that are immutable,
     * either in your domain or through the relation database (coming from a view,
     * or a history table for example).
     *
     * @var boolean
     */
    protected $readOnly = false;

    /**
     * READ-ONLY: The names of all subclasses (descendants).
     *
     * @var array
     */
    protected $subClasses = [];

    /**
     * READ-ONLY: The named queries allowed to be called directly from Repository.
     *
     * @var array
     */
    protected $namedQueries = [];

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
    protected $namedNativeQueries = [];

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
    protected $sqlResultSetMappings = [];

    /**
     * READ-ONLY: The registered lifecycle callbacks for entities of this class.
     *
     * @var array
     */
    protected $lifecycleCallbacks = [];

    /**
     * READ-ONLY: The registered entity listeners.
     *
     * @var array
     */
    protected $entityListeners = [];

    /**
     * READ-ONLY: The field names of all fields that are part of the identifier/primary key
     * of the mapped entity class.
     *
     * @var array
     */
    protected $identifier = [];

    /**
     * READ-ONLY: The primary table metadata.
     *
     * @var TableMetadata
     */
    protected $table;

    /**
     * @var \Doctrine\Instantiator\InstantiatorInterface|null
     */
    protected $instantiator;

    /**
     * MappedSuperClassMetadata constructor.
     *
     * @param string                 $className
     * @param ComponentMetadata|null $parent
     */
    public function __construct(string $className, ?ComponentMetadata $parent = null)
    {
        parent::__construct($className, $parent);
    }

    /**
     * @return null|string
     */
    public function getCustomRepositoryClassName() : ?string
    {
        return $this->customRepositoryClassName;
    }

    /**
     * @param null|string customRepositoryClassName
     */
    public function setCustomRepositoryClassName(?string $customRepositoryClassName)
    {
        $this->customRepositoryClassName = $customRepositoryClassName;
    }

    /**
     * @return Property|null
     */
    public function getDeclaredVersion() : ?Property
    {
        return $this->declaredVersion;
    }

    /**
     * @param Property $property
     */
    public function setDeclaredVersion(Property $property)
    {
        $this->declaredVersion = $property;
    }

    /**
     * @return Property|null
     */
    public function getVersion() : ?Property
    {
        /** @var ComponentMetadata|null $parent */
        $parent  = $this->parent;
        $version = $this->declaredVersion;

        if ($parent && ! $version) {
            $version = $parent->getVersion();
        }

        return $version;
    }

    /**
     * @return bool
     */
    public function isVersioned() : bool
    {
        return $this->getVersion() !== null;
    }

    /**
     * {@inheritdoc}
     */
    public function addDeclaredProperty(Property $property)
    {
        parent::addDeclaredProperty($property);

        if ($property instanceof VersionFieldMetadata) {
            $this->setDeclaredVersion($property);
        }
    }
}
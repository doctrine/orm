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

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\CacheMetadata;
use Doctrine\ORM\Mapping\ChangeTrackingPolicy;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\DiscriminatorColumnMetadata;
use Doctrine\ORM\Mapping\FieldMetadata;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\TableMetadata;
use Doctrine\ORM\Mapping\VersionFieldMetadata;

/**
 * Builder Object for ClassMetadata
 *
 * @license     http://www.opensource.org/licenses/mit-license.php MIT
 * @link        www.doctrine-project.com
 * @since       2.2
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class ClassMetadataBuilder
{
    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadata
     */
    private $cm;

    /**
     * @param \Doctrine\ORM\Mapping\ClassMetadata $cm
     */
    public function __construct(ClassMetadata $cm)
    {
        $this->cm = $cm;
    }

    /**
     * @return ClassMetadata
     */
    public function getClassMetadata()
    {
        return $this->cm;
    }

    /**
     * Marks the class as mapped superclass.
     *
     * @return ClassMetadataBuilder
     */
    public function asMappedSuperClass()
    {
        $this->cm->isMappedSuperclass = true;
        $this->cm->isEmbeddedClass = false;

        return $this;
    }

    /**
     * Marks the class as embeddable.
     *
     * @return ClassMetadataBuilder
     */
    public function asEmbeddable()
    {
        $this->cm->isEmbeddedClass = true;
        $this->cm->isMappedSuperclass = false;

        return $this;
    }

    /**
     * Marks class read only.
     *
     * @return ClassMetadataBuilder
     */
    public function asReadOnly()
    {
        $this->cm->asReadOnly();

        return $this;
    }

    /**
     * Adds and embedded class
     *
     * @param string      $fieldName
     * @param string      $class
     * @param string|null $columnPrefix
     *
     * @return $this
     */
    public function addEmbedded($fieldName, $class, $columnPrefix = null)
    {
        $this->cm->mapEmbedded(
            [
                'fieldName'    => $fieldName,
                'class'        => $class,
                'columnPrefix' => $columnPrefix
            ]
        );

        return $this;
    }

    /**
     * Sets custom Repository class name.
     *
     * @param string $repositoryClassName
     *
     * @return ClassMetadataBuilder
     */
    public function setCustomRepositoryClass($repositoryClassName)
    {
        $this->cm->setCustomRepositoryClass($repositoryClassName);

        return $this;
    }

    /**
     * Sets the table metadata.
     *
     * @param TableMetadata $tableMetadata
     *
     * @return ClassMetadataBuilder
     */
    public function withTable(TableMetadata $tableMetadata)
    {
        $this->cm->setPrimaryTable($tableMetadata);

        return $this;
    }

    /**
     * @param null|CacheMetadata $cache
     *
     * @return self
     */
    public function withCache(CacheMetadata $cache = null)
    {
        $this->cm->setCache($cache);

        return $this;
    }

    /**
     * Adds named query.
     *
     * @param string $name
     * @param string $dqlQuery
     *
     * @return ClassMetadataBuilder
     */
    public function addNamedQuery($name, $dqlQuery)
    {
        $this->cm->addNamedQuery(
            [
                'name' => $name,
                'query' => $dqlQuery,
            ]
        );

        return $this;
    }

    /**
     * Sets class as root of a joined table inheritance hierarchy.
     *
     * @return ClassMetadataBuilder
     */
    public function setJoinedTableInheritance()
    {
        $this->cm->setInheritanceType(InheritanceType::JOINED);

        return $this;
    }

    /**
     * Sets class as root of a single table inheritance hierarchy.
     *
     * @return ClassMetadataBuilder
     */
    public function setSingleTableInheritance()
    {
        $this->cm->setInheritanceType(InheritanceType::SINGLE_TABLE);

        return $this;
    }

    /**
     * Sets the discriminator column.
     *
     * @param DiscriminatorColumnMetadata $discriminatorColumn
     *
     * @return ClassMetadataBuilder
     */
    public function setDiscriminatorColumn(DiscriminatorColumnMetadata $discriminatorColumn)
    {
        $this->cm->setDiscriminatorColumn($discriminatorColumn);

        return $this;
    }

    /**
     * Adds a subclass to this inheritance hierarchy.
     *
     * @param string $name
     * @param string $class
     *
     * @return ClassMetadataBuilder
     */
    public function addDiscriminatorMapClass($name, $class)
    {
        $this->cm->addDiscriminatorMapClass($name, $class);

        return $this;
    }

    /**
     * Sets deferred explicit change tracking policy.
     *
     * @return ClassMetadataBuilder
     */
    public function setChangeTrackingPolicyDeferredExplicit()
    {
        $this->cm->setChangeTrackingPolicy(ChangeTrackingPolicy::DEFERRED_EXPLICIT);

        return $this;
    }

    /**
     * Sets notify change tracking policy.
     *
     * @return ClassMetadataBuilder
     */
    public function setChangeTrackingPolicyNotify()
    {
        $this->cm->setChangeTrackingPolicy(ChangeTrackingPolicy::NOTIFY);

        return $this;
    }

    /**
     * Adds lifecycle event.
     *
     * @param string $methodName
     * @param string $event
     *
     * @return ClassMetadataBuilder
     */
    public function addLifecycleEvent($methodName, $event)
    {
        $this->cm->addLifecycleCallback($methodName, $event);

        return $this;
    }

    /**
     * Adds Field.
     *
     * @param string $name
     * @param string $type
     * @param array  $mapping
     *
     * @return ClassMetadataBuilder
     */
    public function addProperty($name, $type, array $mapping = [])
    {
        $fieldMetadata = isset($mapping['version']) && $mapping['version']
            ? new VersionFieldMetadata($name)
            : new FieldMetadata($name)
        ;

        $fieldMetadata->setType(Type::getType($type));

        if (isset($mapping['columnName'])) {
            $fieldMetadata->setColumnName($mapping['columnName']);
        }

        if (isset($mapping['length'])) {
            $fieldMetadata->setLength((int) $mapping['length']);
        }

        if (isset($mapping['precision'])) {
            $fieldMetadata->setPrecision((int) $mapping['precision']);
        }

        if (isset($mapping['scale'])) {
            $fieldMetadata->setScale((int) $mapping['scale']);
        }

        if (isset($mapping['id'])) {
            $fieldMetadata->setPrimaryKey($mapping['id']);
        }

        if (isset($mapping['unique'])) {
            $fieldMetadata->setUnique($mapping['unique']);
        }

        if (isset($mapping['nullable'])) {
            $fieldMetadata->setNullable($mapping['nullable']);
        }

        if (isset($mapping['columnDefinition'])) {
            $fieldMetadata->setColumnDefinition($mapping['columnDefinition']);
        }

        if (isset($mapping['options'])) {
            $fieldMetadata->setOptions($mapping['options']);
        }

        $this->cm->addProperty($fieldMetadata);

        return $this;
    }

    /**
     * Creates a field builder.
     *
     * @param string $name
     * @param string $type
     *
     * @return FieldBuilder
     */
    public function createField(string $name, string $type)
    {
        return new FieldBuilder($this, $name, Type::getType($type));
    }

    /**
     * Creates an embedded builder.
     *
     * @param string $fieldName
     * @param string $class
     *
     * @return EmbeddedBuilder
     */
    public function createEmbedded($fieldName, $class)
    {
        return new EmbeddedBuilder(
            $this,
            [
                'fieldName'    => $fieldName,
                'class'        => $class,
                'columnPrefix' => null
            ]
        );
    }

    /**
     * Adds a simple many to one association, optionally with the inversed by field.
     *
     * @param string      $name
     * @param string      $targetEntity
     * @param string|null $inversedBy
     *
     * @return ClassMetadataBuilder
     */
    public function addManyToOne(string $name, string $targetEntity, string $inversedBy = null)
    {
        $builder = $this->createManyToOne($name, $targetEntity);

        if ($inversedBy) {
            $builder->inversedBy($inversedBy);
        }

        $this->cm->addAssociation($builder->build());

        return $this;
    }

    /**
     * Creates a ManyToOne Association Builder.
     *
     * Note: This method does not add the association, you have to call build() on the AssociationBuilder.
     *
     * @param string $name
     * @param string $targetEntity
     *
     * @return ManyToOneAssociationMetadataBuilder
     */
    public function createManyToOne(string $name, string $targetEntity)
    {
        $builder = new ManyToOneAssociationMetadataBuilder();

        $builder
            ->withName($name)
            ->withTargetEntity($targetEntity)
        ;

        return $builder;
    }

    /**
     * Creates a OneToOne Association Builder.
     *
     * @param string $name
     * @param string $targetEntity
     *
     * @return OneToOneAssociationMetadataBuilder
     */
    public function createOneToOne(string $name, string $targetEntity)
    {
        $builder = new OneToOneAssociationMetadataBuilder();

        $builder
            ->withName($name)
            ->withTargetEntity($targetEntity)
        ;

        return $builder;
    }

    /**
     * Adds simple inverse one-to-one association.
     *
     * @param string $name
     * @param string $targetEntity
     * @param string $mappedBy
     *
     * @return ClassMetadataBuilder
     */
    public function addInverseOneToOne(string $name, string $targetEntity, string $mappedBy)
    {
        $builder = $this->createOneToOne($name, $targetEntity);

        $builder->withMappedBy($mappedBy);

        $this->cm->addAssociation($builder->build());

        return $this;
    }

    /**
     * Adds simple owning one-to-one association.
     *
     * @param string      $name
     * @param string      $targetEntity
     * @param string|null $inversedBy
     *
     * @return ClassMetadataBuilder
     */
    public function addOwningOneToOne(string $name, string $targetEntity, string $inversedBy = null)
    {
        $builder = $this->createOneToOne($name, $targetEntity);

        if ($inversedBy) {
            $builder->withInversedBy($inversedBy);
        }

        $this->cm->addAssociation($builder->build());

        return $this;
    }

    /**
     * Creates a ManyToMany Association Builder.
     *
     * @param string $name
     * @param string $targetEntity
     *
     * @return ManyToManyAssociationMetadataBuilder
     */
    public function createManyToMany(string $name, string $targetEntity)
    {
        $builder = new ManyToManyAssociationMetadataBuilder();

        $builder
            ->withName($name)
            ->withTargetEntity($targetEntity)
        ;

        return $builder;
    }

    /**
     * Adds a simple owning many to many association.
     *
     * @param string      $name
     * @param string      $targetEntity
     * @param string|null $inversedBy
     *
     * @return ClassMetadataBuilder
     */
    public function addOwningManyToMany(string $name, string $targetEntity, string $inversedBy = null)
    {
        $builder = $this->createManyToMany($name, $targetEntity);

        if ($inversedBy) {
            $builder->withInversedBy($inversedBy);
        }

        $this->cm->addAssociation($builder->build());

        return $this;
    }

    /**
     * Adds a simple inverse many to many association.
     *
     * @param string $name
     * @param string $targetEntity
     * @param string $mappedBy
     *
     * @return ClassMetadataBuilder
     */
    public function addInverseManyToMany(string $name, string $targetEntity, string $mappedBy)
    {
        $builder = $this->createManyToMany($name, $targetEntity);

        $builder->withMappedBy($mappedBy);

        $this->cm->addAssociation($builder->build());

        return $this;
    }

    /**
     * Creates a one to many association builder.
     *
     * @param string $name
     * @param string $targetEntity
     *
     * @return OneToManyAssociationMetadataBuilder
     */
    public function createOneToMany($name, $targetEntity)
    {
        $builder = new OneToManyAssociationMetadataBuilder();

        $builder
            ->withName($name)
            ->withTargetEntity($targetEntity)
        ;

        return $builder;
    }

    /**
     * Adds simple OneToMany association.
     *
     * @param string $name
     * @param string $targetEntity
     * @param string $mappedBy
     *
     * @return ClassMetadataBuilder
     */
    public function addOneToMany($name, $targetEntity, $mappedBy)
    {
        $builder = $this->createOneToMany($name, $targetEntity);

        $builder->withMappedBy($mappedBy);

        $this->cm->addAssociation($builder->build());

        return $this;
    }
}

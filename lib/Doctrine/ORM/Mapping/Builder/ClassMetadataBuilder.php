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

namespace Doctrine\ORM\Mapping\Builder;

use Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\ClassMetadataInfo;

/**
 * Builder Object for ClassMetadata
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       2.2
 * @author      Benjamin Eberlei <kontakt@beberlei.de>
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class ClassMetadataBuilder
{
    /**
     * @var \Doctrine\ORM\Mapping\ClassMetadataInfo
     */
    private $cm;

    /**
     * @param \Doctrine\ORM\Mapping\ClassMetadataInfo $cm
     */
    public function __construct(ClassMetadataInfo $cm)
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
     * Mark the class as mapped superclass.
     *
     * @return ClassMetadataBuilder
     */
    public function setMappedSuperClass()
    {
        $this->cm->isMappedSuperclass = true;

        return $this;
    }

    /**
     * Set custom Repository class name
     *
     * @param string $repositoryClassName
     * @return ClassMetadataBuilder
     */
    public function setCustomRepositoryClass($repositoryClassName)
    {
        $this->cm->setCustomRepositoryClass($repositoryClassName);

        return $this;
    }

    /**
     * Mark class read only
     *
     * @return ClassMetadataBuilder
     */
    public function setReadOnly()
    {
        $this->cm->markReadOnly();

        return $this;
    }

    /**
     * Set the table name
     *
     * @param string $name
     * @return ClassMetadataBuilder
     */
    public function setTable($name)
    {
        $this->cm->setPrimaryTable(array('name' => $name));

        return $this;
    }

    /**
     * Add Index
     *
     * @param array $columns
     * @param string $name
     * @return ClassMetadataBuilder
     */
    public function addIndex(array $columns, $name)
    {
        if (!isset($this->cm->table['indexes'])) {
            $this->cm->table['indexes'] = array();
        }

        $this->cm->table['indexes'][$name] = array('columns' => $columns);

        return $this;
    }

    /**
     * Add Unique Constraint
     *
     * @param array $columns
     * @param string $name
     * @return ClassMetadataBuilder
     */
    public function addUniqueConstraint(array $columns, $name)
    {
        if ( ! isset($this->cm->table['uniqueConstraints'])) {
            $this->cm->table['uniqueConstraints'] = array();
        }

        $this->cm->table['uniqueConstraints'][$name] = array('columns' => $columns);

        return $this;
    }

    /**
     * Add named query
     *
     * @param string $name
     * @param string $dqlQuery
     * @return ClassMetadataBuilder
     */
    public function addNamedQuery($name, $dqlQuery)
    {
        $this->cm->addNamedQuery(array(
            'name' => $name,
            'query' => $dqlQuery,
        ));

        return $this;
    }

    /**
     * Set class as root of a joined table inheritance hierachy.
     *
     * @return ClassMetadataBuilder
     */
    public function setJoinedTableInheritance()
    {
        $this->cm->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_JOINED);

        return $this;
    }

    /**
     * Set class as root of a single table inheritance hierachy.
     *
     * @return ClassMetadataBuilder
     */
    public function setSingleTableInheritance()
    {
        $this->cm->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE);

        return $this;
    }

    /**
     * Set the discriminator column details.
     *
     * @param string $name
     * @param string $type
     */
    public function setDiscriminatorColumn($name, $type = 'string', $length = 255)
    {
        $this->cm->setDiscriminatorColumn(array(
            'name' => $name,
            'type' => $type,
            'length' => $length,
        ));

        return $this;
    }

    /**
     * Add a subclass to this inheritance hierachy.
     *
     * @param string $name
     * @param string $class
     * @return ClassMetadataBuilder
     */
    public function addDiscriminatorMapClass($name, $class)
    {
        $this->cm->addDiscriminatorMapClass($name, $class);

        return $this;
    }

    /**
     * Set deferred explicit change tracking policy.
     *
     * @return ClassMetadataBuilder
     */
    public function setChangeTrackingPolicyDeferredExplicit()
    {
        $this->cm->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_DEFERRED_EXPLICIT);

        return $this;
    }

    /**
     * Set notify change tracking policy.
     *
     * @return ClassMetadataBuilder
     */
    public function setChangeTrackingPolicyNotify()
    {
        $this->cm->setChangeTrackingPolicy(ClassMetadata::CHANGETRACKING_NOTIFY);

        return $this;
    }

    /**
     * Add lifecycle event
     *
     * @param string $methodName
     * @param string $event
     * @return ClassMetadataBuilder
     */
    public function addLifecycleEvent($methodName, $event)
    {
        $this->cm->addLifecycleCallback($methodName, $event);

        return $this;
    }

    /**
     * Add Field
     *
     * @param string $name
     * @param string $type
     * @param array $mapping
     */
    public function addField($name, $type, array $mapping = array())
    {
        $mapping['fieldName'] = $name;
        $mapping['type'] = $type;

        $this->cm->mapField($mapping);

        return $this;
    }

    /**
     * Create a field builder.
     *
     * @param string $name
     * @param string $type
     * @return FieldBuilder
     */
    public function createField($name, $type)
    {
        return new FieldBuilder(
            $this,
            array(
                'fieldName' => $name,
                'type'      => $type
            )
        );
    }

    /**
     * Add a simple many to one association, optionally with the inversed by field.
     *
     * @param string $name
     * @param string $targetEntity
     * @param string|null $inversedBy
     * @return ClassMetadataBuilder
     */
    public function addManyToOne($name, $targetEntity, $inversedBy = null)
    {
        $builder = $this->createManyToOne($name, $targetEntity);

        if ($inversedBy) {
            $builder->inversedBy($inversedBy);
        }

        return $builder->build();
    }

    /**
     * Create a ManyToOne Assocation Builder.
     *
     * Note: This method does not add the association, you have to call build() on the AssociationBuilder.
     *
     * @param string $name
     * @param string $targetEntity
     * @return AssociationBuilder
     */
    public function createManyToOne($name, $targetEntity)
    {
        return new AssociationBuilder(
            $this,
            array(
                'fieldName'    => $name,
                'targetEntity' => $targetEntity
            ),
            ClassMetadata::MANY_TO_ONE
        );
    }

    /**
     * Create OneToOne Assocation Builder
     *
     * @param string $name
     * @param string $targetEntity
     * @return AssociationBuilder
     */
    public function createOneToOne($name, $targetEntity)
    {
        return new AssociationBuilder(
            $this,
            array(
                'fieldName'    => $name,
                'targetEntity' => $targetEntity
            ),
            ClassMetadata::ONE_TO_ONE
        );
    }

    /**
     * Add simple inverse one-to-one assocation.
     *
     * @param string $name
     * @param string $targetEntity
     * @param string $mappedBy
     * @return ClassMetadataBuilder
     */
    public function addInverseOneToOne($name, $targetEntity, $mappedBy)
    {
        $builder = $this->createOneToOne($name, $targetEntity);
        $builder->mappedBy($mappedBy);

        return $builder->build();
    }

    /**
     * Add simple owning one-to-one assocation.
     *
     * @param string $name
     * @param string $targetEntity
     * @param string $inversedBy
     * @return ClassMetadataBuilder
     */
    public function addOwningOneToOne($name, $targetEntity, $inversedBy = null)
    {
        $builder = $this->createOneToOne($name, $targetEntity);

        if ($inversedBy) {
            $builder->inversedBy($inversedBy);
        }

        return $builder->build();
    }

    /**
     * Create ManyToMany Assocation Builder
     *
     * @param string $name
     * @param string $targetEntity
     * @return ManyToManyAssociationBuilder
     */
    public function createManyToMany($name, $targetEntity)
    {
        return new ManyToManyAssociationBuilder(
            $this,
            array(
                'fieldName'    => $name,
                'targetEntity' => $targetEntity
            ),
            ClassMetadata::MANY_TO_MANY
        );
    }

    /**
     * Add a simple owning many to many assocation.
     *
     * @param string $name
     * @param string $targetEntity
     * @param string|null $inversedBy
     * @return ClassMetadataBuilder
     */
    public function addOwningManyToMany($name, $targetEntity, $inversedBy = null)
    {
        $builder = $this->createManyToMany($name, $targetEntity);

        if ($inversedBy) {
            $builder->inversedBy($inversedBy);
        }

        return $builder->build();
    }

    /**
     * Add a simple inverse many to many assocation.
     *
     * @param string $name
     * @param string $targetEntity
     * @param string $mappedBy
     * @return ClassMetadataBuilder
     */
    public function addInverseManyToMany($name, $targetEntity, $mappedBy)
    {
        $builder = $this->createManyToMany($name, $targetEntity);
        $builder->mappedBy($mappedBy);

        return $builder->build();
    }

    /**
     * Create a one to many assocation builder
     *
     * @param string $name
     * @param string $targetEntity
     * @return OneToManyAssociationBuilder
     */
    public function createOneToMany($name, $targetEntity)
    {
        return new OneToManyAssociationBuilder(
            $this,
            array(
                'fieldName'    => $name,
                'targetEntity' => $targetEntity
            ),
            ClassMetadata::ONE_TO_MANY
        );
    }

    /**
     * Add simple OneToMany assocation.
     *
     * @param string $name
     * @param string $targetEntity
     * @param string $mappedBy
     * @return ClassMetadataBuilder
     */
    public function addOneToMany($name, $targetEntity, $mappedBy)
    {
        $builder = $this->createOneToMany($name, $targetEntity);
        $builder->mappedBy($mappedBy);

        return $builder->build();
    }
}

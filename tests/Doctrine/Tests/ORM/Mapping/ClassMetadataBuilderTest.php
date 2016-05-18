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

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;

/**
 * @group DDC-659
 */
class ClassMetadataBuilderTest extends \Doctrine\Tests\OrmTestCase
{
    /**
     * @var ClassMetadata
     */
    private $cm;
    /**
     * @var ClassMetadataBuilder
     */
    private $builder;

    public function setUp()
    {
        $this->cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $this->cm->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);
        $this->builder = new ClassMetadataBuilder($this->cm);
    }

    /**
     * @group embedded
     */
    public function testSetMappedSuperClass()
    {
        self::assertIsFluent($this->builder->setMappedSuperClass());
        self::assertTrue($this->cm->isMappedSuperclass);
        self::assertFalse($this->cm->isEmbeddedClass);
    }

    /**
     * @group embedded
     */
    public function testSetEmbedable()
    {
        self::assertIsFluent($this->builder->setEmbeddable());
        self::assertTrue($this->cm->isEmbeddedClass);
        self::assertFalse($this->cm->isMappedSuperclass);
    }

    /**
     * @group embedded
     */
    public function testAddEmbeddedWithOnlyRequiredParams()
    {
        self::assertIsFluent($this->builder->addEmbedded('name', 'Doctrine\Tests\Models\ValueObjects\Name'));

        self::assertEquals(
            array(
                'name' => array(
                    'class'          => 'Doctrine\Tests\Models\ValueObjects\Name',
                    'columnPrefix'   => null,
                    'declaredField'  => null,
                    'originalField'  => null,
                    'declaringClass' => $this->cm,
                )
            ),
            $this->cm->embeddedClasses
        );
    }

    /**
     * @group embedded
     */
    public function testAddEmbeddedWithPrefix()
    {
        self::assertIsFluent($this->builder->addEmbedded('name', 'Doctrine\Tests\Models\ValueObjects\Name', 'nm_'));

        self::assertEquals(
            array(
                'name' => array(
                    'class'          => 'Doctrine\Tests\Models\ValueObjects\Name',
                    'columnPrefix'   => 'nm_',
                    'declaredField'  => null,
                    'originalField'  => null,
                    'declaringClass' => $this->cm,
                )
            ),
            $this->cm->embeddedClasses
        );
    }

    /**
     * @group embedded
     */
    public function testCreateEmbeddedWithoutExtraParams()
    {
        $embeddedBuilder = $this->builder->createEmbedded('name', 'Doctrine\Tests\Models\ValueObjects\Name');

        self::assertInstanceOf('Doctrine\ORM\Mapping\Builder\EmbeddedBuilder', $embeddedBuilder);
        self::assertFalse(isset($this->cm->embeddedClasses['name']));

        self::assertIsFluent($embeddedBuilder->build());
        self::assertEquals(
            array(
                'class'          => 'Doctrine\Tests\Models\ValueObjects\Name',
                'columnPrefix'   => null,
                'declaredField'  => null,
                'originalField'  => null,
                'declaringClass' => $this->cm,
            ),
            $this->cm->embeddedClasses['name']
        );
    }

    /**
     * @group embedded
     */
    public function testCreateEmbeddedWithColumnPrefix()
    {
        $embeddedBuilder = $this->builder->createEmbedded('name', 'Doctrine\Tests\Models\ValueObjects\Name');

        self::assertEquals($embeddedBuilder, $embeddedBuilder->setColumnPrefix('nm_'));

        self::assertIsFluent($embeddedBuilder->build());
        self::assertEquals(
            array(
                'class'          => 'Doctrine\Tests\Models\ValueObjects\Name',
                'columnPrefix'   => 'nm_',
                'declaredField'  => null,
                'originalField'  => null,
                'declaringClass' => $this->cm,
            ),
            $this->cm->embeddedClasses['name']
        );
    }

    public function testSetCustomRepositoryClass()
    {
        self::assertIsFluent($this->builder->setCustomRepositoryClass('Doctrine\Tests\Models\CMS\CmsGroup'));
        self::assertEquals('Doctrine\Tests\Models\CMS\CmsGroup', $this->cm->customRepositoryClassName);
    }

    public function testSetReadOnly()
    {
        self::assertIsFluent($this->builder->setReadOnly());
        self::assertTrue($this->cm->isReadOnly);
    }

    public function testSetTable()
    {
        self::assertIsFluent($this->builder->setTable('users'));
        self::assertEquals('users', $this->cm->table['name']);
    }

    public function testAddIndex()
    {
        self::assertIsFluent($this->builder->addIndex(array('username', 'name'), 'users_idx'));
        self::assertEquals(array('users_idx' => array('columns' => array('username', 'name'))), $this->cm->table['indexes']);
    }

    public function testAddUniqueConstraint()
    {
        self::assertIsFluent($this->builder->addUniqueConstraint(array('username', 'name'), 'users_idx'));
        self::assertEquals(array('users_idx' => array('columns' => array('username', 'name'))), $this->cm->table['uniqueConstraints']);
    }

    public function testSetPrimaryTableRelated()
    {
        $this->builder->addUniqueConstraint(array('username', 'name'), 'users_idx');
        $this->builder->addIndex(array('username', 'name'), 'users_idx');
        $this->builder->setTable('users');

        self::assertEquals(
            array(
                'name' => 'users',
                'indexes' => array('users_idx' => array('columns' => array('username', 'name'))),
                'uniqueConstraints' => array('users_idx' => array('columns' => array('username', 'name'))),
            ),
            $this->cm->table
        );
    }

    public function testSetInheritanceJoined()
    {
        self::assertIsFluent($this->builder->setJoinedTableInheritance());
        self::assertEquals(ClassMetadata::INHERITANCE_TYPE_JOINED, $this->cm->inheritanceType);
    }

    public function testSetInheritanceSingleTable()
    {
        self::assertIsFluent($this->builder->setSingleTableInheritance());
        self::assertEquals(ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE, $this->cm->inheritanceType);
    }

    public function testSetDiscriminatorColumn()
    {
        self::assertIsFluent($this->builder->setDiscriminatorColumn('discr', 'string', '124'));
        self::assertEquals(
            array(
                'fieldName' => 'discr',
                'name'      => 'discr',
                'type'      => Type::getType('string'),
                'length'    => '124',
                'tableName' => 'CmsUser',
            ),
            $this->cm->discriminatorColumn
        );
    }

    public function testAddDiscriminatorMapClass()
    {
        self::assertIsFluent($this->builder->addDiscriminatorMapClass('test', 'Doctrine\Tests\Models\CMS\CmsUser'));
        self::assertIsFluent($this->builder->addDiscriminatorMapClass('test2', 'Doctrine\Tests\Models\CMS\CmsGroup'));

        self::assertEquals(
            array(
                'test' => 'Doctrine\Tests\Models\CMS\CmsUser',
                'test2' => 'Doctrine\Tests\Models\CMS\CmsGroup'
            ),
            $this->cm->discriminatorMap
        );
        self::assertEquals('test', $this->cm->discriminatorValue);
    }

    public function testChangeTrackingPolicyExplicit()
    {
        self::assertIsFluent($this->builder->setChangeTrackingPolicyDeferredExplicit());
        self::assertEquals(ClassMetadata::CHANGETRACKING_DEFERRED_EXPLICIT, $this->cm->changeTrackingPolicy);
    }

    public function testChangeTrackingPolicyNotify()
    {
        self::assertIsFluent($this->builder->setChangeTrackingPolicyNotify());
        self::assertEquals(ClassMetadata::CHANGETRACKING_NOTIFY, $this->cm->changeTrackingPolicy);
    }

    public function testAddField()
    {
        self::assertIsFluent($this->builder->addProperty('name', 'string'));
        self::assertEquals(
            array(
                'columnName'     => 'name',
                'fieldName'      => 'name',
                'type'           => Type::getType('string'),
                'declaringClass' => $this->cm,
                'tableName'      => 'CmsUser',
            ),
            $this->cm->fieldMappings['name']
        );
    }

    public function testCreateField()
    {
        $fieldBuilder = $this->builder->createField('name', 'string');

        self::assertInstanceOf('Doctrine\ORM\Mapping\Builder\FieldBuilder', $fieldBuilder);
        self::assertFalse(isset($this->cm->fieldMappings['name']));

        self::assertIsFluent($fieldBuilder->build());
        self::assertEquals(
            array(
                'columnName'     => 'name',
                'fieldName'      => 'name',
                'type'           => Type::getType('string'),
                'declaringClass' => $this->cm,
                'tableName'      => 'CmsUser',
            ),
            $this->cm->fieldMappings['name']
        );
    }

    public function testCreateVersionedField()
    {
        $this->builder->createField('name', 'integer')
            ->columnName('username')
            ->length(124)
            ->nullable()
            ->columnDefinition('foobar')
            ->unique()
            ->isVersionField()
            ->build();

        self::assertEquals(
            array(
                'columnDefinition' => 'foobar',
                'columnName'       => 'username',
                'default'          => 1,
                'fieldName'        => 'name',
                'length'           => 124,
                'type'             => Type::getType('integer'),
                'nullable'         => true,
                'unique'           => true,
                'declaringClass'   => $this->cm,
                'tableName'        => 'CmsUser',
            ),
            $this->cm->fieldMappings['name']
        );
    }

    public function testCreatePrimaryField()
    {
        $this->builder->createField('id', 'integer')
            ->makePrimaryKey()
            ->generatedValue()
            ->build();

        self::assertEquals(array('id'), $this->cm->identifier);
        self::assertEquals(
            array(
                'columnName'     => 'id',
                'fieldName'      => 'id',
                'id'             => true,
                'type'           => Type::getType('integer'),
                'declaringClass' => $this->cm,
                'tableName'      => 'CmsUser',
            ),
            $this->cm->fieldMappings['id']
        );
    }

    public function testCreateUnsignedOptionField()
    {
        $this->builder->createField('state', 'integer')
            ->option('unsigned', true)
            ->build();

        self::assertEquals(
            array(
                'fieldName'      => 'state',
                'type'           => Type::getType('integer'),
                'options'        => array('unsigned' => true),
                'columnName'     => 'state',
                'declaringClass' => $this->cm,
                'tableName'      => 'CmsUser',
            ),
            $this->cm->fieldMappings['state']
        );
    }

    public function testAddLifecycleEvent()
    {
        $this->builder->addLifecycleEvent('getStatus', 'postLoad');

        self::assertEquals(array('postLoad' => array('getStatus')), $this->cm->lifecycleCallbacks);
    }

    public function testCreateManyToOne()
    {
        self::assertIsFluent(
            $this->builder->createManyToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                  ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                  ->cascadeAll()
                  ->fetchExtraLazy()
                  ->build()
        );

        self::assertEquals(
            array(
                'groups' => array (
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'cascade' => array (
                        0 => 'remove',
                        1 => 'persist',
                        2 => 'refresh',
                        3 => 'merge',
                        4 => 'detach',
                    ),
                    'fetch' => 4,
                    'joinColumns' => array (
                        0 => array (
                            'name'                 => 'group_id',
                            'referencedColumnName' => 'id',
                            'nullable'             => true,
                            'unique'               => false,
                            'onDelete'             => 'CASCADE',
                            'columnDefinition'     => NULL,
                            'tableName'            => 'CmsUser',
                        ),
                    ),
                    'type' => 2,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                    'isCascadeRemove' => true,
                    'isCascadePersist' => true,
                    'isCascadeRefresh' => true,
                    'isCascadeMerge' => true,
                    'isCascadeDetach' => true,
                    'sourceToTargetKeyColumns' => array (
                        'group_id' => 'id',
                    ),
                    'joinColumnFieldNames' => array (
                        'group_id' => 'group_id',
                    ),
                    'targetToSourceKeyColumns' => array (
                        'id' => 'group_id',
                    ),
                    'orphanRemoval' => false,
                    'declaringClass' => $this->cm,
                  ),
            ),
            $this->cm->associationMappings
        );
    }

    public function testCreateManyToOneWithIdentity()
    {
        self::assertIsFluent(
            $this
                ->builder
                ->createManyToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                ->cascadeAll()
                ->fetchExtraLazy()
                ->makePrimaryKey()
                ->build()
        );

        self::assertEquals(
            array(
                'groups' => array(
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'cascade' => array(
                        0 => 'remove',
                        1 => 'persist',
                        2 => 'refresh',
                        3 => 'merge',
                        4 => 'detach',
                    ),
                    'fetch' => 4,
                    'joinColumns' => array(
                        0 => array(
                            'name'                 => 'group_id',
                            'referencedColumnName' => 'id',
                            'nullable'             => true,
                            'unique'               => false,
                            'onDelete'             => 'CASCADE',
                            'columnDefinition'     => NULL,
                            'tableName'            => 'CmsUser',
                        ),
                    ),
                    'type' => 2,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                    'isCascadeRemove' => true,
                    'isCascadePersist' => true,
                    'isCascadeRefresh' => true,
                    'isCascadeMerge' => true,
                    'isCascadeDetach' => true,
                    'sourceToTargetKeyColumns' => array(
                        'group_id' => 'id',
                    ),
                    'joinColumnFieldNames' => array(
                        'group_id' => 'group_id',
                    ),
                    'targetToSourceKeyColumns' => array(
                        'id' => 'group_id',
                    ),
                    'orphanRemoval' => false,
                    'declaringClass' => $this->cm,
                    'id' => true,
                ),
            ),
            $this->cm->associationMappings
        );
    }

    public function testCreateOneToOne()
    {
        self::assertIsFluent(
            $this->builder->createOneToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                ->cascadeAll()
                ->fetchExtraLazy()
                ->build()
        );

        self::assertEquals(
            array(
                'groups' => array (
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'cascade' => array (
                        0 => 'remove',
                        1 => 'persist',
                        2 => 'refresh',
                        3 => 'merge',
                        4 => 'detach',
                    ),
                    'fetch' => 4,
                    'joinColumns' => array (
                        0 => array (
                            'name'                 => 'group_id',
                            'referencedColumnName' => 'id',
                            'nullable'             => true,
                            'unique'               => true,
                            'onDelete'             => 'CASCADE',
                            'columnDefinition'     => NULL,
                            'tableName'            => 'CmsUser',
                        ),
                    ),
                    'type' => 1,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                    'isCascadeRemove' => true,
                    'isCascadePersist' => true,
                    'isCascadeRefresh' => true,
                    'isCascadeMerge' => true,
                    'isCascadeDetach' => true,
                    'sourceToTargetKeyColumns' => array (
                        'group_id' => 'id',
                    ),
                    'joinColumnFieldNames' => array (
                        'group_id' => 'group_id',
                    ),
                    'targetToSourceKeyColumns' => array (
                        'id' => 'group_id',
                    ),
                    'orphanRemoval' => false,
                    'declaringClass' => $this->cm,
                ),
            ),
            $this->cm->associationMappings
        );
    }

    public function testCreateOneToOneWithIdentity()
    {
        self::assertIsFluent(
            $this->builder->createOneToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                ->cascadeAll()
                ->fetchExtraLazy()
                ->makePrimaryKey()
                ->build()
        );

        self::assertEquals(
            array(
                'groups' => array(
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'cascade' => array(
                        0 => 'remove',
                        1 => 'persist',
                        2 => 'refresh',
                        3 => 'merge',
                        4 => 'detach',
                    ),
                    'fetch' => 4,
                    'id' => true,
                    'joinColumns' => array(
                        0 => array(
                            'name'                 => 'group_id',
                            'referencedColumnName' => 'id',
                            'nullable'             => true,
                            'unique'               => false,
                            'onDelete'             => 'CASCADE',
                            'columnDefinition'     => NULL,
                            'tableName'            => 'CmsUser',
                        ),
                    ),
                    'type' => 1,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                    'isCascadeRemove' => true,
                    'isCascadePersist' => true,
                    'isCascadeRefresh' => true,
                    'isCascadeMerge' => true,
                    'isCascadeDetach' => true,
                    'sourceToTargetKeyColumns' => array(
                        'group_id' => 'id',
                    ),
                    'joinColumnFieldNames' => array(
                        'group_id' => 'group_id',
                    ),
                    'targetToSourceKeyColumns' => array(
                        'id' => 'group_id',
                    ),
                    'orphanRemoval' => false,
                    'declaringClass' => $this->cm,
                ),
            ),
            $this->cm->associationMappings
        );
    }

    public function testThrowsExceptionOnCreateOneToOneWithIdentityOnInverseSide()
    {
        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException');

        $this
            ->builder
            ->createOneToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
            ->mappedBy('test')
            ->fetchExtraLazy()
            ->makePrimaryKey()
            ->build();
    }

    public function testCreateManyToMany()
    {
        self::assertIsFluent(
            $this->builder->createManyToMany('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                  ->setJoinTable('groups_users')
                  ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                  ->addInverseJoinColumn('user_id', 'id')
                  ->cascadeAll()
                  ->fetchExtraLazy()
                  ->build()
        );

        self::assertEquals(
            array(
                'groups' => array(
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'cascade' =>
                    array(
                        0 => 'remove',
                        1 => 'persist',
                        2 => 'refresh',
                        3 => 'merge',
                        4 => 'detach',
                    ),
                    'fetch' => 4,
                    'joinTable' => array(
                        'joinColumns' => array(
                            0 => array(
                                'name' => 'group_id',
                                'referencedColumnName' => 'id',
                                'nullable' => true,
                                'unique' => false,
                                'onDelete' => 'CASCADE',
                                'columnDefinition' => NULL,
                            ),
                        ),
                        'inverseJoinColumns' => array(
                            0 => array(
                                'name' => 'user_id',
                                'referencedColumnName' => 'id',
                                'nullable' => true,
                                'unique' => false,
                                'onDelete' => NULL,
                                'columnDefinition' => NULL,
                            ),
                        ),
                        'name' => 'groups_users',
                    ),
                    'type' => 8,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                    'isCascadeRemove' => true,
                    'isCascadePersist' => true,
                    'isCascadeRefresh' => true,
                    'isCascadeMerge' => true,
                    'isCascadeDetach' => true,
                    'isOnDeleteCascade' => true,
                    'relationToSourceKeyColumns' => array(
                        'group_id' => 'id',
                    ),
                    'joinTableColumns' => array(
                        0 => 'group_id',
                        1 => 'user_id',
                    ),
                    'relationToTargetKeyColumns' => array(
                        'user_id' => 'id',
                    ),
                    'orphanRemoval' => false,
                    'declaringClass' => $this->cm,
                ),
            ),
            $this->cm->associationMappings
        );
    }

    public function testThrowsExceptionOnCreateManyToManyWithIdentity()
    {
        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException');

        $this->builder->createManyToMany('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
              ->makePrimaryKey()
              ->setJoinTable('groups_users')
              ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
              ->addInverseJoinColumn('user_id', 'id')
              ->cascadeAll()
              ->fetchExtraLazy()
              ->build();
    }

    public function testCreateOneToMany()
    {
        self::assertIsFluent(
            $this->builder->createOneToMany('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                ->mappedBy('test')
                ->setOrderBy(array('test'))
                ->setIndexBy('test')
                ->build()
        );

        self::assertEquals(
            array(
                'groups' => array(
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'mappedBy' => 'test',
                    'orderBy' => array(
                        0 => 'test',
                    ),
                    'indexBy' => 'test',
                    'type' => 4,
                    'inversedBy' => NULL,
                    'isOwningSide' => false,
                    'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                    'fetch' => 2,
                    'cascade' => array(),
                    'isCascadeRemove' => false,
                    'isCascadePersist' => false,
                    'isCascadeRefresh' => false,
                    'isCascadeMerge' => false,
                    'isCascadeDetach' => false,
                    'orphanRemoval' => false,
                    'declaringClass' => $this->cm,
                ),
            ),
            $this->cm->associationMappings
        );
    }

    public function testThrowsExceptionOnCreateOneToManyWithIdentity()
    {
        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException');

        $this->builder->createOneToMany('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
            ->makePrimaryKey()
            ->mappedBy('test')
            ->setOrderBy(array('test'))
            ->setIndexBy('test')
            ->build();
    }

    public function testOrphanRemovalOnCreateOneToOne()
    {
        self::assertIsFluent(
            $this->builder
                ->createOneToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                ->orphanRemoval()
                ->build()
        );

        self::assertEquals(
            array(
                'groups' => array(
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'cascade' => array (),
                    'fetch' => 2,
                    'joinColumns' => array (
                        0 => array (
                            'name'                 => 'group_id',
                            'referencedColumnName' => 'id',
                            'nullable'             => true,
                            'unique'               => true,
                            'onDelete'             => 'CASCADE',
                            'columnDefinition'     => NULL,
                            'tableName'            => 'CmsUser',
                        ),
                    ),
                    'type' => 1,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                    'isCascadeRemove' => true,
                    'isCascadePersist' => false,
                    'isCascadeRefresh' => false,
                    'isCascadeMerge' => false,
                    'isCascadeDetach' => false,
                    'sourceToTargetKeyColumns' => array (
                      'group_id' => 'id',
                    ),
                    'joinColumnFieldNames' => array (
                      'group_id' => 'group_id',
                    ),
                    'targetToSourceKeyColumns' => array (
                      'id' => 'group_id',
                    ),
                    'orphanRemoval' => true,
                    'declaringClass' => $this->cm,
                ),
            ),
            $this->cm->associationMappings
        );
    }

    public function testOrphanRemovalOnCreateOneToMany()
    {
        self::assertIsFluent(
            $this->builder
                ->createOneToMany('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                ->mappedBy('test')
                ->orphanRemoval()
                ->build()
        );

        self::assertEquals(
            array(
                'groups' => array(
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'mappedBy' => 'test',
                    'type' => 4,
                    'inversedBy' => NULL,
                    'isOwningSide' => false,
                    'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                    'fetch' => 2,
                    'cascade' => array(),
                    'isCascadeRemove' => true,
                    'isCascadePersist' => false,
                    'isCascadeRefresh' => false,
                    'isCascadeMerge' => false,
                    'isCascadeDetach' => false,
                    'orphanRemoval' => true,
                    'declaringClass' => $this->cm,
                ),
            ),
            $this->cm->associationMappings
        );
    }

    public function testExceptionOnOrphanRemovalOnManyToOne()
    {
        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException');

        $this->builder
            ->createManyToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
            ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
            ->orphanRemoval()
            ->build();
    }

    public function testOrphanRemovalOnManyToMany()
    {
        $this->builder
            ->createManyToMany('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
            ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
            ->orphanRemoval()
            ->build();

        self::assertEquals(
            array(
                'groups' => array(
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'cascade' => array(),
                    'fetch' => 2,
                    'joinTable' => array(
                        'joinColumns' => array(
                            0 => array(
                                'name' => 'group_id',
                                'referencedColumnName' => 'id',
                                'nullable' => true,
                                'unique' => false,
                                'onDelete' => 'CASCADE',
                                'columnDefinition' => NULL,
                            ),
                        ),
                        'inverseJoinColumns' => array(
                            0 => array(
                                'name' => 'cmsgroup_id',
                                'referencedColumnName' => 'id',
                                'onDelete' => 'CASCADE'
                            )
                        ),
                        'name' => 'cmsuser_cmsgroup',
                    ),
                    'type' => 8,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                    'isCascadeRemove' => false,
                    'isCascadePersist' => false,
                    'isCascadeRefresh' => false,
                    'isCascadeMerge' => false,
                    'isCascadeDetach' => false,
                    'isOnDeleteCascade' => true,
                    'relationToSourceKeyColumns' => array(
                        'group_id' => 'id',
                    ),
                    'joinTableColumns' => array(
                        0 => 'group_id',
                        1 => 'cmsgroup_id',
                    ),
                    'relationToTargetKeyColumns' => array(
                        'cmsgroup_id' => 'id',
                    ),
                    'orphanRemoval' => true,
                    'declaringClass' => $this->cm,
                ),
            ),
            $this->cm->associationMappings
        );
    }

    public function assertIsFluent($ret)
    {
        self::assertSame($this->builder, $ret, "Return Value has to be same instance as used builder");
    }
}

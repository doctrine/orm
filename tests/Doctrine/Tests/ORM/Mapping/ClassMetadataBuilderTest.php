<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\EmbeddedBuilder;
use Doctrine\ORM\Mapping\Builder\FieldBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\ValueObjects\Name;
use Doctrine\Tests\OrmTestCase;

/**
 * @group DDC-659
 */
class ClassMetadataBuilderTest extends OrmTestCase
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
        $this->cm = new ClassMetadata(CmsUser::class);
        $this->cm->initializeReflection(new RuntimeReflectionService());
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
        self::assertIsFluent($this->builder->addEmbedded('name', Name::class));

        self::assertEquals(
            [
                'name' => [
                    'class'          => Name::class,
                    'columnPrefix'   => null,
                    'declaredField'  => null,
                    'originalField'  => null,
                    'declaringClass' => $this->cm,
                ]
            ],
            $this->cm->embeddedClasses
        );
    }

    /**
     * @group embedded
     */
    public function testAddEmbeddedWithPrefix()
    {
        self::assertIsFluent($this->builder->addEmbedded('name', Name::class, 'nm_'));

        self::assertEquals(
            [
                'name' => [
                    'class'          => 'Doctrine\Tests\Models\ValueObjects\Name',
                    'columnPrefix'   => 'nm_',
                    'declaredField'  => null,
                    'originalField'  => null,
                    'declaringClass' => $this->cm,
                ]
            ],
            $this->cm->embeddedClasses
        );
    }

    /**
     * @group embedded
     */
    public function testCreateEmbeddedWithoutExtraParams()
    {
        $embeddedBuilder = $this->builder->createEmbedded('name', Name::class);

        self::assertInstanceOf(EmbeddedBuilder::class, $embeddedBuilder);
        self::assertFalse(isset($this->cm->embeddedClasses['name']));

        self::assertIsFluent($embeddedBuilder->build());
        self::assertEquals(
            [
                'class'          => Name::class,
                'columnPrefix'   => null,
                'declaredField'  => null,
                'originalField'  => null,
                'declaringClass' => $this->cm,
            ],
            $this->cm->embeddedClasses['name']
        );
    }

    /**
     * @group embedded
     */
    public function testCreateEmbeddedWithColumnPrefix()
    {
        $embeddedBuilder = $this->builder->createEmbedded('name', Name::class);

        self::assertEquals($embeddedBuilder, $embeddedBuilder->setColumnPrefix('nm_'));

        self::assertIsFluent($embeddedBuilder->build());
        self::assertEquals(
            [
                'class'          => Name::class,
                'columnPrefix'   => 'nm_',
                'declaredField'  => null,
                'originalField'  => null,
                'declaringClass' => $this->cm,
            ],
            $this->cm->embeddedClasses['name']
        );
    }

    public function testSetCustomRepositoryClass()
    {
        self::assertIsFluent($this->builder->setCustomRepositoryClass(CmsGroup::class));
        self::assertEquals(CmsGroup::class, $this->cm->customRepositoryClassName);
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
        self::assertIsFluent($this->builder->addIndex(['username', 'name'], 'users_idx'));
        self::assertEquals(['users_idx' => ['columns' => ['username', 'name']]], $this->cm->table['indexes']);
    }

    public function testAddUniqueConstraint()
    {
        self::assertIsFluent($this->builder->addUniqueConstraint(['username', 'name'], 'users_idx'));
        self::assertEquals(['users_idx' => ['columns' => ['username', 'name']]], $this->cm->table['uniqueConstraints']);
    }

    public function testSetPrimaryTableRelated()
    {
        $this->builder->addUniqueConstraint(['username', 'name'], 'users_idx');
        $this->builder->addIndex(['username', 'name'], 'users_idx');
        $this->builder->setTable('users');

        self::assertEquals(
            [
                'name' => 'users',
                'indexes' => ['users_idx' => ['columns' => ['username', 'name']]],
                'uniqueConstraints' => ['users_idx' => ['columns' => ['username', 'name']]],
            ],
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
            [
                'fieldName' => 'discr',
                'name'      => 'discr',
                'type'      => Type::getType('string'),
                'length'    => '124',
                'tableName' => 'CmsUser',
            ],
            $this->cm->discriminatorColumn
        );
    }

    public function testAddDiscriminatorMapClass()
    {
        self::assertIsFluent($this->builder->addDiscriminatorMapClass('test', CmsUser::class));
        self::assertIsFluent($this->builder->addDiscriminatorMapClass('test2', CmsGroup::class));

        self::assertEquals(['test' => CmsUser::class, 'test2' => CmsGroup::class], $this->cm->discriminatorMap);
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
        self::assertIsFluent($this->builder->addField('name', 'string'));
        self::assertEquals(
            [
                'columnName'     => 'name',
                'fieldName'      => 'name',
                'type'           => Type::getType('string'),
                'declaringClass' => $this->cm,
                'tableName'      => 'CmsUser',
            ],
            $this->cm->fieldMappings['name']
        );
    }

    public function testCreateField()
    {
        $fieldBuilder = $this->builder->createField('name', 'string');

        self::assertInstanceOf(FieldBuilder::class, $fieldBuilder);
        self::assertFalse(isset($this->cm->fieldMappings['name']));

        self::assertIsFluent($fieldBuilder->build());
        self::assertEquals(
            [
                'columnName'     => 'name',
                'fieldName'      => 'name',
                'type'           => Type::getType('string'),
                'declaringClass' => $this->cm,
                'tableName'      => 'CmsUser',
            ],
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
            [
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
            ],
            $this->cm->fieldMappings['name']
        );
    }

    public function testCreatePrimaryField()
    {
        $this->builder->createField('id', 'integer')
            ->makePrimaryKey()
            ->generatedValue()
            ->build();

        self::assertEquals(['id'], $this->cm->identifier);
        self::assertEquals(
            [
                'columnName'     => 'id',
                'fieldName'      => 'id',
                'id'             => true,
                'type'           => Type::getType('integer'),
                'declaringClass' => $this->cm,
                'tableName'      => 'CmsUser',
            ],
            $this->cm->fieldMappings['id']
        );
    }

    public function testCreateUnsignedOptionField()
    {
        $this->builder->createField('state', 'integer')
            ->option('unsigned', true)
            ->build();

        self::assertEquals(
            [
                'fieldName'      => 'state',
                'type'           => Type::getType('integer'),
                'options'        => ['unsigned' => true],
                'columnName'     => 'state',
                'declaringClass' => $this->cm,
                'tableName'      => 'CmsUser',
            ],
            $this->cm->fieldMappings['state']
        );
    }

    public function testAddLifecycleEvent()
    {
        $this->builder->addLifecycleEvent('getStatus', 'postLoad');

        self::assertEquals(['postLoad' => ['getStatus']], $this->cm->lifecycleCallbacks);
    }

    public function testCreateManyToOne()
    {
        self::assertIsFluent(
            $this->builder->createManyToOne('groups', CmsGroup::class)
                  ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                  ->cascadeAll()
                  ->fetchExtraLazy()
                  ->build()
        );

        self::assertEquals(
            [
                'groups' => [
                    'fieldName' => 'groups',
                    'targetEntity' => CmsGroup::class,
                    'cascade' => [
                        0 => 'remove',
                        1 => 'persist',
                        2 => 'refresh',
                        3 => 'merge',
                        4 => 'detach',
                    ],
                    'fetch' => 4,
                    'joinColumns' => [
                        0 => [
                            'name'                 => 'group_id',
                            'referencedColumnName' => 'id',
                            'nullable'             => true,
                            'unique'               => false,
                            'onDelete'             => 'CASCADE',
                            'columnDefinition'     => NULL,
                            'tableName'            => 'CmsUser',
                        ],
                    ],
                    'type' => 2,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => CmsUser::class,
                    'isCascadeRemove' => true,
                    'isCascadePersist' => true,
                    'isCascadeRefresh' => true,
                    'isCascadeMerge' => true,
                    'isCascadeDetach' => true,
                    'sourceToTargetKeyColumns' => [
                        'group_id' => 'id',
                    ],
                    'joinColumnFieldNames' => [
                        'group_id' => 'group_id',
                    ],
                    'targetToSourceKeyColumns' => [
                        'id' => 'group_id',
                    ],
                    'orphanRemoval' => false,
                    'declaringClass' => $this->cm,
                  ],
            ],
            $this->cm->associationMappings
        );
    }

    public function testCreateManyToOneWithIdentity()
    {
        self::assertIsFluent(
            $this
                ->builder
                ->createManyToOne('groups', CmsGroup::class)
                ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                ->cascadeAll()
                ->fetchExtraLazy()
                ->makePrimaryKey()
                ->build()
        );

        self::assertEquals(
            [
                'groups' => [
                    'fieldName' => 'groups',
                    'targetEntity' => CmsGroup::class,
                    'cascade' => [
                        0 => 'remove',
                        1 => 'persist',
                        2 => 'refresh',
                        3 => 'merge',
                        4 => 'detach',
                    ],
                    'fetch' => 4,
                    'joinColumns' => [
                        0 => [
                            'name'                 => 'group_id',
                            'referencedColumnName' => 'id',
                            'nullable'             => true,
                            'unique'               => false,
                            'onDelete'             => 'CASCADE',
                            'columnDefinition'     => NULL,
                            'tableName'            => 'CmsUser',
                        ],
                    ],
                    'type' => 2,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => CmsUser::class,
                    'isCascadeRemove' => true,
                    'isCascadePersist' => true,
                    'isCascadeRefresh' => true,
                    'isCascadeMerge' => true,
                    'isCascadeDetach' => true,
                    'sourceToTargetKeyColumns' => ['group_id' => 'id'],
                    'joinColumnFieldNames' => ['group_id' => 'group_id'],
                    'targetToSourceKeyColumns' => ['id' => 'group_id'],
                    'orphanRemoval' => false,
                    'declaringClass' => $this->cm,
                    'id' => true,
                ],
            ],
            $this->cm->associationMappings
        );
    }

    public function testCreateOneToOne()
    {
        self::assertIsFluent(
            $this->builder->createOneToOne('groups', CmsGroup::class)
                ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                ->cascadeAll()
                ->fetchExtraLazy()
                ->build()
        );

        self::assertEquals(
            [
                'groups' => [
                    'fieldName' => 'groups',
                    'targetEntity' => CmsGroup::class,
                    'cascade' => [
                        0 => 'remove',
                        1 => 'persist',
                        2 => 'refresh',
                        3 => 'merge',
                        4 => 'detach',
                    ],
                    'fetch' => 4,
                    'joinColumns' => [
                        0 => [
                            'name'                 => 'group_id',
                            'referencedColumnName' => 'id',
                            'nullable'             => true,
                            'unique'               => true,
                            'onDelete'             => 'CASCADE',
                            'columnDefinition'     => NULL,
                            'tableName'            => 'CmsUser',
                        ],
                    ],
                    'type' => 1,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => CmsUser::class,
                    'isCascadeRemove' => true,
                    'isCascadePersist' => true,
                    'isCascadeRefresh' => true,
                    'isCascadeMerge' => true,
                    'isCascadeDetach' => true,
                    'sourceToTargetKeyColumns' => ['group_id' => 'id'],
                    'joinColumnFieldNames' => ['group_id' => 'group_id'],
                    'targetToSourceKeyColumns' => ['id' => 'group_id'],
                    'orphanRemoval' => false,
                    'declaringClass' => $this->cm,
                ],
            ],
            $this->cm->associationMappings
        );
    }

    public function testCreateOneToOneWithIdentity()
    {
        self::assertIsFluent(
            $this->builder->createOneToOne('groups', CmsGroup::class)
                ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                ->cascadeAll()
                ->fetchExtraLazy()
                ->makePrimaryKey()
                ->build()
        );

        self::assertEquals(
            [
                'groups' => [
                    'fieldName' => 'groups',
                    'targetEntity' => CmsGroup::class,
                    'cascade' => [
                        0 => 'remove',
                        1 => 'persist',
                        2 => 'refresh',
                        3 => 'merge',
                        4 => 'detach',
                    ],
                    'fetch' => 4,
                    'id' => true,
                    'joinColumns' => [
                        0 => [
                            'name'                 => 'group_id',
                            'referencedColumnName' => 'id',
                            'nullable'             => true,
                            'unique'               => false,
                            'onDelete'             => 'CASCADE',
                            'columnDefinition'     => NULL,
                            'tableName'            => 'CmsUser',
                        ],
                    ],
                    'type' => 1,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => CmsUser::class,
                    'isCascadeRemove' => true,
                    'isCascadePersist' => true,
                    'isCascadeRefresh' => true,
                    'isCascadeMerge' => true,
                    'isCascadeDetach' => true,
                    'sourceToTargetKeyColumns' => ['group_id' => 'id'],
                    'joinColumnFieldNames' => ['group_id' => 'group_id'],
                    'targetToSourceKeyColumns' => ['id' => 'group_id'],
                    'orphanRemoval' => false,
                    'declaringClass' => $this->cm,
                ],
            ],
            $this->cm->associationMappings
        );
    }

    public function testThrowsExceptionOnCreateOneToOneWithIdentityOnInverseSide()
    {
        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $this
            ->builder
            ->createOneToOne('groups', CmsGroup::class)
            ->mappedBy('test')
            ->fetchExtraLazy()
            ->makePrimaryKey()
            ->build();
    }

    public function testCreateManyToMany()
    {
        self::assertIsFluent(
            $this->builder->createManyToMany('groups', CmsGroup::class)
                  ->setJoinTable('groups_users')
                  ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                  ->addInverseJoinColumn('user_id', 'id')
                  ->cascadeAll()
                  ->fetchExtraLazy()
                  ->build()
        );

        self::assertEquals(
            [
                'groups' => [
                    'fieldName' => 'groups',
                    'targetEntity' => CmsGroup::class,
                    'cascade' =>
                    [
                        0 => 'remove',
                        1 => 'persist',
                        2 => 'refresh',
                        3 => 'merge',
                        4 => 'detach',
                    ],
                    'fetch' => 4,
                    'joinTable' => [
                        'joinColumns' =>[
                            0 => [
                                'name' => 'group_id',
                                'referencedColumnName' => 'id',
                                'nullable' => true,
                                'unique' => false,
                                'onDelete' => 'CASCADE',
                                'columnDefinition' => NULL,
                            ],
                        ],
                        'inverseJoinColumns' => [
                            0 => [
                                'name' => 'user_id',
                                'referencedColumnName' => 'id',
                                'nullable' => true,
                                'unique' => false,
                                'onDelete' => NULL,
                                'columnDefinition' => NULL,
                            ],
                        ],
                        'name' => 'groups_users',
                    ],
                    'type' => 8,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => CmsUser::class,
                    'isCascadeRemove' => true,
                    'isCascadePersist' => true,
                    'isCascadeRefresh' => true,
                    'isCascadeMerge' => true,
                    'isCascadeDetach' => true,
                    'isOnDeleteCascade' => true,
                    'relationToSourceKeyColumns' => ['group_id' => 'id'],
                    'joinTableColumns' => [
                        0 => 'group_id',
                        1 => 'user_id',
                    ],
                    'relationToTargetKeyColumns' => ['user_id' => 'id'],
                    'orphanRemoval' => false,
                    'declaringClass' => $this->cm,
                ],
            ],
            $this->cm->associationMappings
        );
    }

    public function testThrowsExceptionOnCreateManyToManyWithIdentity()
    {
        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $this->builder->createManyToMany('groups', CmsGroup::class)
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
            $this->builder->createOneToMany('groups', CmsGroup::class)
                ->mappedBy('test')
                ->setOrderBy(['test'])
                ->setIndexBy('test')
                ->build()
        );

        self::assertEquals(
            [
                'groups' => [
                    'fieldName' => 'groups',
                    'targetEntity' => CmsGroup::class,
                    'mappedBy' => 'test',
                    'orderBy' => [
                        0 => 'test',
                    ],
                    'indexBy' => 'test',
                    'type' => 4,
                    'inversedBy' => NULL,
                    'isOwningSide' => false,
                    'sourceEntity' => CmsUser::class,
                    'fetch' => 2,
                    'cascade' => [],
                    'isCascadeRemove' => false,
                    'isCascadePersist' => false,
                    'isCascadeRefresh' => false,
                    'isCascadeMerge' => false,
                    'isCascadeDetach' => false,
                    'orphanRemoval' => false,
                    'declaringClass' => $this->cm,
                ],
            ],
            $this->cm->associationMappings
        );
    }

    public function testThrowsExceptionOnCreateOneToManyWithIdentity()
    {
        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $this->builder->createOneToMany('groups', CmsGroup::class)
            ->makePrimaryKey()
            ->mappedBy('test')
            ->setOrderBy(['test'])
            ->setIndexBy('test')
            ->build();
    }

    public function testOrphanRemovalOnCreateOneToOne()
    {
        self::assertIsFluent(
            $this->builder
                ->createOneToOne('groups', CmsGroup::class)
                ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                ->orphanRemoval()
                ->build()
        );

        self::assertEquals(
            [
                'groups' => [
                    'fieldName' => 'groups',
                    'targetEntity' => CmsGroup::class,
                    'cascade' => [],
                    'fetch' => 2,
                    'joinColumns' => [
                        0 => [
                            'name'                 => 'group_id',
                            'referencedColumnName' => 'id',
                            'nullable'             => true,
                            'unique'               => true,
                            'onDelete'             => 'CASCADE',
                            'columnDefinition'     => NULL,
                            'tableName'            => 'CmsUser',
                        ],
                    ],
                    'type' => 1,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => CmsUser::class,
                    'isCascadeRemove' => true,
                    'isCascadePersist' => false,
                    'isCascadeRefresh' => false,
                    'isCascadeMerge' => false,
                    'isCascadeDetach' => false,
                    'sourceToTargetKeyColumns' => ['group_id' => 'id'],
                    'joinColumnFieldNames' => ['group_id' => 'group_id'],
                    'targetToSourceKeyColumns' => ['id' => 'group_id'],
                    'orphanRemoval' => true,
                    'declaringClass' => $this->cm,
                ],
            ],
            $this->cm->associationMappings
        );
    }

    public function testOrphanRemovalOnCreateOneToMany()
    {
        self::assertIsFluent(
            $this->builder
                ->createOneToMany('groups', CmsGroup::class)
                ->mappedBy('test')
                ->orphanRemoval()
                ->build()
        );

        self::assertEquals(
            [
                'groups' => [
                    'fieldName' => 'groups',
                    'targetEntity' => CmsGroup::class,
                    'mappedBy' => 'test',
                    'type' => 4,
                    'inversedBy' => NULL,
                    'isOwningSide' => false,
                    'sourceEntity' => CmsUser::class,
                    'fetch' => 2,
                    'cascade' => [],
                    'isCascadeRemove' => true,
                    'isCascadePersist' => false,
                    'isCascadeRefresh' => false,
                    'isCascadeMerge' => false,
                    'isCascadeDetach' => false,
                    'orphanRemoval' => true,
                    'declaringClass' => $this->cm,
                ],
            ],
            $this->cm->associationMappings
        );
    }

    public function testExceptionOnOrphanRemovalOnManyToOne()
    {
        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $this->builder
            ->createManyToOne('groups', CmsGroup::class)
            ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
            ->orphanRemoval()
            ->build();
    }

    public function testOrphanRemovalOnManyToMany()
    {
        $this->builder
            ->createManyToMany('groups', CmsGroup::class)
            ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
            ->orphanRemoval()
            ->build();

        self::assertEquals(
            [
                'groups' => [
                    'fieldName' => 'groups',
                    'targetEntity' => CmsGroup::class,
                    'cascade' => [],
                    'fetch' => 2,
                    'joinTable' => [
                        'joinColumns' => [
                            0 => [
                                'name' => 'group_id',
                                'referencedColumnName' => 'id',
                                'nullable' => true,
                                'unique' => false,
                                'onDelete' => 'CASCADE',
                                'columnDefinition' => NULL,
                            ],
                        ],
                        'inverseJoinColumns' => [
                            0 => [
                                'name' => 'cmsgroup_id',
                                'referencedColumnName' => 'id',
                                'onDelete' => 'CASCADE'
                            ]
                        ],
                        'name' => 'cmsuser_cmsgroup',
                    ],
                    'type' => 8,
                    'mappedBy' => NULL,
                    'inversedBy' => NULL,
                    'isOwningSide' => true,
                    'sourceEntity' => CmsUser::class,
                    'isCascadeRemove' => false,
                    'isCascadePersist' => false,
                    'isCascadeRefresh' => false,
                    'isCascadeMerge' => false,
                    'isCascadeDetach' => false,
                    'isOnDeleteCascade' => true,
                    'relationToSourceKeyColumns' => ['group_id' => 'id'],
                    'joinTableColumns' => [
                        0 => 'group_id',
                        1 => 'cmsgroup_id',
                    ],
                    'relationToTargetKeyColumns' => ['cmsgroup_id' => 'id'],
                    'orphanRemoval' => true,
                    'declaringClass' => $this->cm,
                ],
            ],
            $this->cm->associationMappings
        );
    }

    public function assertIsFluent($ret)
    {
        self::assertSame($this->builder, $ret, "Return Value has to be same instance as used builder");
    }
}

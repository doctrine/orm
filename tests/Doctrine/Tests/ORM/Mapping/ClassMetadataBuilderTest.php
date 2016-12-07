<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
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
        $this->cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $this->cm->initializeReflection(new RuntimeReflectionService());
        $this->builder = new ClassMetadataBuilder($this->cm);
    }

    public function testSetMappedSuperClass()
    {
        $this->assertIsFluent($this->builder->setMappedSuperClass());
        $this->assertTrue($this->cm->isMappedSuperclass);
        $this->assertFalse($this->cm->isEmbeddedClass);
    }

    public function testSetEmbedable()
    {
        $this->assertIsFluent($this->builder->setEmbeddable());
        $this->assertTrue($this->cm->isEmbeddedClass);
        $this->assertFalse($this->cm->isMappedSuperclass);
    }

    public function testAddEmbeddedWithOnlyRequiredParams()
    {
        $this->assertIsFluent(
            $this->builder->addEmbedded(
                'name',
                'Doctrine\Tests\Models\ValueObjects\Name'
            )
        );

        $this->assertEquals(
            [
            'name' => [
                'class' => 'Doctrine\Tests\Models\ValueObjects\Name',
                'columnPrefix' => null,
                'declaredField' => null,
                'originalField' => null,
            ]
            ], $this->cm->embeddedClasses);
    }

    public function testAddEmbeddedWithPrefix()
    {
        $this->assertIsFluent(
            $this->builder->addEmbedded(
                'name',
                'Doctrine\Tests\Models\ValueObjects\Name',
                'nm_'
            )
        );

        $this->assertEquals(
            [
            'name' => [
                'class' => 'Doctrine\Tests\Models\ValueObjects\Name',
                'columnPrefix' => 'nm_',
                'declaredField' => null,
                'originalField' => null,
            ]
            ], $this->cm->embeddedClasses);
    }

    public function testCreateEmbeddedWithoutExtraParams()
    {
        $embeddedBuilder = ($this->builder->createEmbedded('name', 'Doctrine\Tests\Models\ValueObjects\Name'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\Builder\EmbeddedBuilder', $embeddedBuilder);

        $this->assertFalse(isset($this->cm->embeddedClasses['name']));

        $this->assertIsFluent($embeddedBuilder->build());
        $this->assertEquals(
            [
                'class' => 'Doctrine\Tests\Models\ValueObjects\Name',
                'columnPrefix' => null,
                'declaredField' => null,
                'originalField' => null
            ],
            $this->cm->embeddedClasses['name']
        );
    }

    public function testCreateEmbeddedWithColumnPrefix()
    {
        $embeddedBuilder = ($this->builder->createEmbedded('name', 'Doctrine\Tests\Models\ValueObjects\Name'));

        $this->assertEquals($embeddedBuilder, $embeddedBuilder->setColumnPrefix('nm_'));

        $this->assertIsFluent($embeddedBuilder->build());

        $this->assertEquals(
            [
                'class' => 'Doctrine\Tests\Models\ValueObjects\Name',
                'columnPrefix' => 'nm_',
                'declaredField' => null,
                'originalField' => null
            ],
            $this->cm->embeddedClasses['name']
        );
    }

    public function testSetCustomRepositoryClass()
    {
        $this->assertIsFluent($this->builder->setCustomRepositoryClass('Doctrine\Tests\Models\CMS\CmsGroup'));
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsGroup', $this->cm->customRepositoryClassName);
    }

    public function testSetReadOnly()
    {
        $this->assertIsFluent($this->builder->setReadOnly());
        $this->assertTrue($this->cm->isReadOnly);
    }

    public function testSetTable()
    {
        $this->assertIsFluent($this->builder->setTable('users'));
        $this->assertEquals('users', $this->cm->table['name']);
    }

    public function testAddIndex()
    {
        $this->assertIsFluent($this->builder->addIndex(['username', 'name'], 'users_idx'));
        $this->assertEquals(['users_idx' => ['columns' => ['username', 'name']]], $this->cm->table['indexes']);
    }

    public function testAddUniqueConstraint()
    {
        $this->assertIsFluent($this->builder->addUniqueConstraint(['username', 'name'], 'users_idx'));
        $this->assertEquals(['users_idx' => ['columns' => ['username', 'name']]], $this->cm->table['uniqueConstraints']);
    }

    public function testSetPrimaryTableRelated()
    {
        $this->builder->addUniqueConstraint(['username', 'name'], 'users_idx');
        $this->builder->addIndex(['username', 'name'], 'users_idx');
        $this->builder->setTable('users');

        $this->assertEquals(
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
        $this->assertIsFluent($this->builder->setJoinedTableInheritance());
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_JOINED, $this->cm->inheritanceType);
    }

    public function testSetInheritanceSingleTable()
    {
        $this->assertIsFluent($this->builder->setSingleTableInheritance());
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE, $this->cm->inheritanceType);
    }

    public function testSetDiscriminatorColumn()
    {
        $this->assertIsFluent($this->builder->setDiscriminatorColumn('discr', 'string', '124'));
        $this->assertEquals(['fieldName' => 'discr', 'name' => 'discr', 'type' => 'string', 'length' => '124'], $this->cm->discriminatorColumn);
    }

    public function testAddDiscriminatorMapClass()
    {
        $this->assertIsFluent($this->builder->addDiscriminatorMapClass('test', 'Doctrine\Tests\Models\CMS\CmsUser'));
        $this->assertIsFluent($this->builder->addDiscriminatorMapClass('test2', 'Doctrine\Tests\Models\CMS\CmsGroup'));

        $this->assertEquals(
            ['test' => 'Doctrine\Tests\Models\CMS\CmsUser', 'test2' => 'Doctrine\Tests\Models\CMS\CmsGroup'], $this->cm->discriminatorMap);
        $this->assertEquals('test', $this->cm->discriminatorValue);
    }

    public function testChangeTrackingPolicyExplicit()
    {
        $this->assertIsFluent($this->builder->setChangeTrackingPolicyDeferredExplicit());
        $this->assertEquals(ClassMetadata::CHANGETRACKING_DEFERRED_EXPLICIT, $this->cm->changeTrackingPolicy);
    }

    public function testChangeTrackingPolicyNotify()
    {
        $this->assertIsFluent($this->builder->setChangeTrackingPolicyNotify());
        $this->assertEquals(ClassMetadata::CHANGETRACKING_NOTIFY, $this->cm->changeTrackingPolicy);
    }

    public function testAddField()
    {
        $this->assertIsFluent($this->builder->addField('name', 'string'));
        $this->assertEquals(['columnName' => 'name', 'fieldName' => 'name', 'type' => 'string'], $this->cm->fieldMappings['name']);
    }

    public function testCreateField()
    {
        $fieldBuilder = ($this->builder->createField('name', 'string'));
        $this->assertInstanceOf('Doctrine\ORM\Mapping\Builder\FieldBuilder', $fieldBuilder);

        $this->assertFalse(isset($this->cm->fieldMappings['name']));
        $this->assertIsFluent($fieldBuilder->build());
        $this->assertEquals(['columnName' => 'name', 'fieldName' => 'name', 'type' => 'string'], $this->cm->fieldMappings['name']);
    }

    public function testCreateVersionedField()
    {
        $this->builder->createField('name', 'integer')->columnName('username')->length(124)->nullable()->columnDefinition('foobar')->unique()->isVersionField()->build();
        $this->assertEquals(
            [
            'columnDefinition' => 'foobar',
            'columnName' => 'username',
            'default' => 1,
            'fieldName' => 'name',
            'length' => 124,
            'type' => 'integer',
            'nullable' => true,
            'unique' => true,
            ], $this->cm->fieldMappings['name']);
    }

    public function testCreatePrimaryField()
    {
        $this->builder->createField('id', 'integer')->makePrimaryKey()->generatedValue()->build();

        $this->assertEquals(['id'], $this->cm->identifier);
        $this->assertEquals(['columnName' => 'id', 'fieldName' => 'id', 'id' => true, 'type' => 'integer'], $this->cm->fieldMappings['id']);
    }

    public function testCreateUnsignedOptionField()
    {
        $this->builder->createField('state', 'integer')->option('unsigned', true)->build();

        $this->assertEquals(
            ['fieldName' => 'state', 'type' => 'integer', 'options' => ['unsigned' => true], 'columnName' => 'state'], $this->cm->fieldMappings['state']);
    }

    public function testAddLifecycleEvent()
    {
        $this->builder->addLifecycleEvent('getStatus', 'postLoad');

        $this->assertEquals(['postLoad' => ['getStatus']], $this->cm->lifecycleCallbacks);
    }

    public function testCreateManyToOne()
    {
        $this->assertIsFluent(
            $this->builder->createManyToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                              ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                              ->cascadeAll()
                              ->fetchExtraLazy()
                              ->build()
        );

        $this->assertEquals(
            [
                'groups' => [
                'fieldName' => 'groups',
                'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                'cascade' => [
                  0 => 'remove',
                  1 => 'persist',
                  2 => 'refresh',
                  3 => 'merge',
                  4 => 'detach',
                ],
                'fetch' => 4,
                'joinColumns' => [
                  0 =>
                  [
                    'name' => 'group_id',
                    'referencedColumnName' => 'id',
                    'nullable' => true,
                    'unique' => false,
                    'onDelete' => 'CASCADE',
                    'columnDefinition' => NULL,
                  ],
                ],
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
                'sourceToTargetKeyColumns' =>
                [
                  'group_id' => 'id',
                ],
                'joinColumnFieldNames' =>
                [
                  'group_id' => 'group_id',
                ],
                'targetToSourceKeyColumns' =>
                [
                  'id' => 'group_id',
                ],
                'orphanRemoval' => false,
                ],
            ], $this->cm->associationMappings);
    }

    public function testCreateManyToOneWithIdentity()
    {
        $this->assertIsFluent(
            $this
                ->builder
                ->createManyToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                ->cascadeAll()
                ->fetchExtraLazy()
                ->makePrimaryKey()
                ->build()
        );

        $this->assertEquals(
            [
                'groups' => [
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                    'cascade' => [
                        0 => 'remove',
                        1 => 'persist',
                        2 => 'refresh',
                        3 => 'merge',
                        4 => 'detach',
                    ],
                    'fetch' => 4,
                    'joinColumns' => [
                        0 =>
                            [
                                'name' => 'group_id',
                                'referencedColumnName' => 'id',
                                'nullable' => true,
                                'unique' => false,
                                'onDelete' => 'CASCADE',
                                'columnDefinition' => NULL,
                            ],
                    ],
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
                    'sourceToTargetKeyColumns' =>
                        [
                            'group_id' => 'id',
                        ],
                    'joinColumnFieldNames' =>
                        [
                            'group_id' => 'group_id',
                        ],
                    'targetToSourceKeyColumns' =>
                        [
                            'id' => 'group_id',
                        ],
                    'orphanRemoval' => false,
                    'id' => true
                ],
            ],
            $this->cm->associationMappings
        );
    }

    public function testCreateOneToOne()
    {
        $this->assertIsFluent(
            $this->builder->createOneToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                              ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                              ->cascadeAll()
                              ->fetchExtraLazy()
                              ->build()
        );

        $this->assertEquals(
            [
                'groups' => [
                'fieldName' => 'groups',
                'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                'cascade' => [
                  0 => 'remove',
                  1 => 'persist',
                  2 => 'refresh',
                  3 => 'merge',
                  4 => 'detach',
                ],
                'fetch' => 4,
                'joinColumns' => [
                  0 =>
                  [
                    'name' => 'group_id',
                    'referencedColumnName' => 'id',
                    'nullable' => true,
                    'unique' => true,
                    'onDelete' => 'CASCADE',
                    'columnDefinition' => NULL,
                  ],
                ],
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
                'sourceToTargetKeyColumns' =>
                [
                  'group_id' => 'id',
                ],
                'joinColumnFieldNames' =>
                [
                  'group_id' => 'group_id',
                ],
                'targetToSourceKeyColumns' =>
                [
                  'id' => 'group_id',
                ],
                'orphanRemoval' => false
                ],
            ], $this->cm->associationMappings);
    }

    public function testCreateOneToOneWithIdentity()
    {
        $this->assertIsFluent(
            $this
                ->builder
                ->createOneToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                ->cascadeAll()
                ->fetchExtraLazy()
                ->makePrimaryKey()
                ->build()
        );

        $this->assertEquals(
            [
                'groups' => [
                    'fieldName' => 'groups',
                    'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
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
                        0 =>
                            [
                                'name' => 'group_id',
                                'referencedColumnName' => 'id',
                                'nullable' => true,
                                'unique' => false,
                                'onDelete' => 'CASCADE',
                                'columnDefinition' => NULL,
                            ],
                    ],
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
                    'sourceToTargetKeyColumns' =>
                        [
                            'group_id' => 'id',
                        ],
                    'joinColumnFieldNames' =>
                        [
                            'group_id' => 'group_id',
                        ],
                    'targetToSourceKeyColumns' =>
                        [
                            'id' => 'group_id',
                        ],
                    'orphanRemoval' => false
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
            ->createOneToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
            ->mappedBy('test')
            ->fetchExtraLazy()
            ->makePrimaryKey()
            ->build();
    }

    public function testCreateManyToMany()
    {
        $this->assertIsFluent(
            $this->builder->createManyToMany('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                              ->setJoinTable('groups_users')
                              ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                              ->addInverseJoinColumn('user_id', 'id')
                              ->cascadeAll()
                              ->fetchExtraLazy()
                              ->build()
        );

        $this->assertEquals(
            [
            'groups' =>
            [
                'fieldName' => 'groups',
                'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                'cascade' =>
                [
                    0 => 'remove',
                    1 => 'persist',
                    2 => 'refresh',
                    3 => 'merge',
                    4 => 'detach',
                ],
                'fetch' => 4,
                'joinTable' =>
                [
                    'joinColumns' =>
                    [
                        0 =>
                        [
                            'name' => 'group_id',
                            'referencedColumnName' => 'id',
                            'nullable' => true,
                            'unique' => false,
                            'onDelete' => 'CASCADE',
                            'columnDefinition' => NULL,
                        ],
                    ],
                    'inverseJoinColumns' =>
                    [
                        0 =>
                        [
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
                'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                'isCascadeRemove' => true,
                'isCascadePersist' => true,
                'isCascadeRefresh' => true,
                'isCascadeMerge' => true,
                'isCascadeDetach' => true,
                'isOnDeleteCascade' => true,
                'relationToSourceKeyColumns' =>
                [
                    'group_id' => 'id',
                ],
                'joinTableColumns' =>
                [
                    0 => 'group_id',
                    1 => 'user_id',
                ],
                'relationToTargetKeyColumns' =>
                [
                    'user_id' => 'id',
                ],
                'orphanRemoval' => false,
            ],
            ], $this->cm->associationMappings);
    }

    public function testThrowsExceptionOnCreateManyToManyWithIdentity()
    {
        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

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
        $this->assertIsFluent(
                $this->builder->createOneToMany('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                        ->mappedBy('test')
                        ->setOrderBy(['test'])
                        ->setIndexBy('test')
                        ->build()
        );

        $this->assertEquals(
            [
            'groups' =>
            [
                'fieldName' => 'groups',
                'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                'mappedBy' => 'test',
                'orderBy' =>
                [
                    0 => 'test',
                ],
                'indexBy' => 'test',
                'type' => 4,
                'inversedBy' => NULL,
                'isOwningSide' => false,
                'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                'fetch' => 2,
                'cascade' =>
                [
                ],
                'isCascadeRemove' => false,
                'isCascadePersist' => false,
                'isCascadeRefresh' => false,
                'isCascadeMerge' => false,
                'isCascadeDetach' => false,
                'orphanRemoval' => false,
            ],
            ], $this->cm->associationMappings);
    }

    public function testThrowsExceptionOnCreateOneToManyWithIdentity()
    {
        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $this->builder->createOneToMany('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                ->makePrimaryKey()
                ->mappedBy('test')
                ->setOrderBy(['test'])
                ->setIndexBy('test')
                ->build();
    }

    public function testOrphanRemovalOnCreateOneToOne()
    {
        $this->assertIsFluent(
            $this->builder
                ->createOneToOne('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                ->orphanRemoval()
                ->build()
        );

        $this->assertEquals(
            [
            'groups' => [
                'fieldName' => 'groups',
                'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                'cascade' => [],
                'fetch' => 2,
                'joinColumns' => [
                  0 =>
                  [
                    'name' => 'group_id',
                    'referencedColumnName' => 'id',
                    'nullable' => true,
                    'unique' => true,
                    'onDelete' => 'CASCADE',
                    'columnDefinition' => NULL,
                  ],
                ],
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
                'sourceToTargetKeyColumns' =>
                [
                  'group_id' => 'id',
                ],
                'joinColumnFieldNames' =>
                [
                  'group_id' => 'group_id',
                ],
                'targetToSourceKeyColumns' =>
                [
                  'id' => 'group_id',
                ],
                'orphanRemoval' => true
            ],
            ], $this->cm->associationMappings);
    }

    public function testOrphanRemovalOnCreateOneToMany()
    {
        $this->assertIsFluent(
            $this->builder
                ->createOneToMany('groups', 'Doctrine\Tests\Models\CMS\CmsGroup')
                ->mappedBy('test')
                ->orphanRemoval()
                ->build()
        );

        $this->assertEquals(
            [
            'groups' =>
            [
                'fieldName' => 'groups',
                'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
                'mappedBy' => 'test',
                'type' => 4,
                'inversedBy' => NULL,
                'isOwningSide' => false,
                'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                'fetch' => 2,
                'cascade' => [],
                'isCascadeRemove' => true,
                'isCascadePersist' => false,
                'isCascadeRefresh' => false,
                'isCascadeMerge' => false,
                'isCascadeDetach' => false,
                'orphanRemoval' => true,
            ],
            ], $this->cm->associationMappings);
    }

    public function testExceptionOnOrphanRemovalOnManyToOne()
    {
        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

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

        $this->assertEquals(
            [
            'groups' => [
                'fieldName' => 'groups',
                'targetEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsGroup',
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
                'sourceEntity' => 'Doctrine\\Tests\\Models\\CMS\\CmsUser',
                'isCascadeRemove' => false,
                'isCascadePersist' => false,
                'isCascadeRefresh' => false,
                'isCascadeMerge' => false,
                'isCascadeDetach' => false,
                'isOnDeleteCascade' => true,
                'relationToSourceKeyColumns' => [
                    'group_id' => 'id',
                ],
                'joinTableColumns' => [
                    0 => 'group_id',
                    1 => 'cmsgroup_id',
                ],
                'relationToTargetKeyColumns' => [
                    'cmsgroup_id' => 'id',
                ],
                'orphanRemoval' => true,
            ],
            ], $this->cm->associationMappings);
    }

    public function assertIsFluent($ret)
    {
        $this->assertSame($this->builder, $ret, "Return Value has to be same instance as used builder");
    }
}

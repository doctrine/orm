<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\EmbeddedBuilder;
use Doctrine\ORM\Mapping\Builder\FieldBuilder;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\ValueObjects\Name;
use Doctrine\Tests\OrmTestCase;

/**
 * @group DDC-659
 */
class ClassMetadataBuilderTest extends OrmTestCase
{
    /** @var ClassMetadata */
    private $cm;
    /** @var ClassMetadataBuilder */
    private $builder;

    protected function setUp(): void
    {
        $this->cm = new ClassMetadata(CmsUser::class);
        $this->cm->initializeReflection(new RuntimeReflectionService());
        $this->builder = new ClassMetadataBuilder($this->cm);
    }

    public function testSetMappedSuperClass(): void
    {
        $this->assertIsFluent($this->builder->setMappedSuperClass());
        $this->assertTrue($this->cm->isMappedSuperclass);
        $this->assertFalse($this->cm->isEmbeddedClass);
    }

    public function testSetEmbedable(): void
    {
        $this->assertIsFluent($this->builder->setEmbeddable());
        $this->assertTrue($this->cm->isEmbeddedClass);
        $this->assertFalse($this->cm->isMappedSuperclass);
    }

    public function testAddEmbeddedWithOnlyRequiredParams(): void
    {
        $this->assertIsFluent($this->builder->addEmbedded('name', Name::class));

        $this->assertEquals(
            [
                'name' => [
                    'class' => Name::class,
                    'columnPrefix' => null,
                    'declaredField' => null,
                    'originalField' => null,
                ],
            ],
            $this->cm->embeddedClasses
        );
    }

    public function testAddEmbeddedWithPrefix(): void
    {
        $this->assertIsFluent(
            $this->builder->addEmbedded(
                'name',
                Name::class,
                'nm_'
            )
        );

        $this->assertEquals(
            [
                'name' => [
                    'class' => Name::class,
                    'columnPrefix' => 'nm_',
                    'declaredField' => null,
                    'originalField' => null,
                ],
            ],
            $this->cm->embeddedClasses
        );
    }

    public function testCreateEmbeddedWithoutExtraParams(): void
    {
        $embeddedBuilder = $this->builder->createEmbedded('name', Name::class);
        $this->assertInstanceOf(EmbeddedBuilder::class, $embeddedBuilder);

        $this->assertFalse(isset($this->cm->embeddedClasses['name']));

        $this->assertIsFluent($embeddedBuilder->build());
        $this->assertEquals(
            [
                'class' => Name::class,
                'columnPrefix' => null,
                'declaredField' => null,
                'originalField' => null,
            ],
            $this->cm->embeddedClasses['name']
        );
    }

    public function testCreateEmbeddedWithColumnPrefix(): void
    {
        $embeddedBuilder = $this->builder->createEmbedded('name', Name::class);

        $this->assertEquals($embeddedBuilder, $embeddedBuilder->setColumnPrefix('nm_'));

        $this->assertIsFluent($embeddedBuilder->build());

        $this->assertEquals(
            [
                'class' => Name::class,
                'columnPrefix' => 'nm_',
                'declaredField' => null,
                'originalField' => null,
            ],
            $this->cm->embeddedClasses['name']
        );
    }

    public function testSetCustomRepositoryClass(): void
    {
        $this->assertIsFluent($this->builder->setCustomRepositoryClass(CmsGroup::class));
        $this->assertEquals(CmsGroup::class, $this->cm->customRepositoryClassName);
    }

    public function testSetReadOnly(): void
    {
        $this->assertIsFluent($this->builder->setReadOnly());
        $this->assertTrue($this->cm->isReadOnly);
    }

    public function testSetTable(): void
    {
        $this->assertIsFluent($this->builder->setTable('users'));
        $this->assertEquals('users', $this->cm->table['name']);
    }

    public function testAddIndex(): void
    {
        $this->assertIsFluent($this->builder->addIndex(['username', 'name'], 'users_idx'));
        $this->assertEquals(['users_idx' => ['columns' => ['username', 'name']]], $this->cm->table['indexes']);
    }

    public function testAddUniqueConstraint(): void
    {
        $this->assertIsFluent($this->builder->addUniqueConstraint(['username', 'name'], 'users_idx'));
        $this->assertEquals(['users_idx' => ['columns' => ['username', 'name']]], $this->cm->table['uniqueConstraints']);
    }

    public function testSetPrimaryTableRelated(): void
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

    public function testSetInheritanceJoined(): void
    {
        $this->assertIsFluent($this->builder->setJoinedTableInheritance());
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_JOINED, $this->cm->inheritanceType);
    }

    public function testSetInheritanceSingleTable(): void
    {
        $this->assertIsFluent($this->builder->setSingleTableInheritance());
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE, $this->cm->inheritanceType);
    }

    public function testSetDiscriminatorColumn(): void
    {
        $this->assertIsFluent($this->builder->setDiscriminatorColumn('discr', 'string', '124'));
        $this->assertEquals(['fieldName' => 'discr', 'name' => 'discr', 'type' => 'string', 'length' => '124'], $this->cm->discriminatorColumn);
    }

    public function testAddDiscriminatorMapClass(): void
    {
        $this->assertIsFluent($this->builder->addDiscriminatorMapClass('test', CmsUser::class));
        $this->assertIsFluent($this->builder->addDiscriminatorMapClass('test2', CmsGroup::class));

        $this->assertEquals(
            ['test' => CmsUser::class, 'test2' => CmsGroup::class],
            $this->cm->discriminatorMap
        );
        $this->assertEquals('test', $this->cm->discriminatorValue);
    }

    public function testChangeTrackingPolicyExplicit(): void
    {
        $this->assertIsFluent($this->builder->setChangeTrackingPolicyDeferredExplicit());
        $this->assertEquals(ClassMetadata::CHANGETRACKING_DEFERRED_EXPLICIT, $this->cm->changeTrackingPolicy);
    }

    public function testChangeTrackingPolicyNotify(): void
    {
        $this->assertIsFluent($this->builder->setChangeTrackingPolicyNotify());
        $this->assertEquals(ClassMetadata::CHANGETRACKING_NOTIFY, $this->cm->changeTrackingPolicy);
    }

    public function testAddField(): void
    {
        $this->assertIsFluent($this->builder->addField('name', 'string'));
        $this->assertEquals(['columnName' => 'name', 'fieldName' => 'name', 'type' => 'string'], $this->cm->fieldMappings['name']);
    }

    public function testCreateField(): void
    {
        $fieldBuilder = $this->builder->createField('name', 'string');
        $this->assertInstanceOf(FieldBuilder::class, $fieldBuilder);

        $this->assertFalse(isset($this->cm->fieldMappings['name']));
        $this->assertIsFluent($fieldBuilder->build());
        $this->assertEquals(['columnName' => 'name', 'fieldName' => 'name', 'type' => 'string'], $this->cm->fieldMappings['name']);
    }

    public function testCreateVersionedField(): void
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
            ],
            $this->cm->fieldMappings['name']
        );
    }

    public function testCreatePrimaryField(): void
    {
        $this->builder->createField('id', 'integer')->makePrimaryKey()->generatedValue()->build();

        $this->assertEquals(['id'], $this->cm->identifier);
        $this->assertEquals(['columnName' => 'id', 'fieldName' => 'id', 'id' => true, 'type' => 'integer'], $this->cm->fieldMappings['id']);
    }

    public function testCreateUnsignedOptionField(): void
    {
        $this->builder->createField('state', 'integer')->option('unsigned', true)->build();

        $this->assertEquals(
            ['fieldName' => 'state', 'type' => 'integer', 'options' => ['unsigned' => true], 'columnName' => 'state'],
            $this->cm->fieldMappings['state']
        );
    }

    public function testAddLifecycleEvent(): void
    {
        $this->builder->addLifecycleEvent('getStatus', 'postLoad');

        $this->assertEquals(['postLoad' => ['getStatus']], $this->cm->lifecycleCallbacks);
    }

    public function testCreateManyToOne(): void
    {
        $this->assertIsFluent(
            $this->builder->createManyToOne('groups', CmsGroup::class)
                              ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                              ->cascadeAll()
                              ->fetchExtraLazy()
                              ->build()
        );

        $this->assertEquals(
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
                        0 =>
                        [
                            'name' => 'group_id',
                            'referencedColumnName' => 'id',
                            'nullable' => true,
                            'unique' => false,
                            'onDelete' => 'CASCADE',
                            'columnDefinition' => null,
                        ],
                    ],
                    'type' => 2,
                    'mappedBy' => null,
                    'inversedBy' => null,
                    'isOwningSide' => true,
                    'sourceEntity' => CmsUser::class,
                    'isCascadeRemove' => true,
                    'isCascadePersist' => true,
                    'isCascadeRefresh' => true,
                    'isCascadeMerge' => true,
                    'isCascadeDetach' => true,
                    'sourceToTargetKeyColumns' =>
                    ['group_id' => 'id'],
                    'joinColumnFieldNames' =>
                    ['group_id' => 'group_id'],
                    'targetToSourceKeyColumns' =>
                    ['id' => 'group_id'],
                    'orphanRemoval' => false,
                ],
            ],
            $this->cm->associationMappings
        );
    }

    public function testCreateManyToOneWithIdentity(): void
    {
        $this->assertIsFluent(
            $this
                ->builder
                ->createManyToOne('groups', CmsGroup::class)
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
                        0 =>
                            [
                                'name' => 'group_id',
                                'referencedColumnName' => 'id',
                                'nullable' => true,
                                'unique' => false,
                                'onDelete' => 'CASCADE',
                                'columnDefinition' => null,
                            ],
                    ],
                    'type' => 2,
                    'mappedBy' => null,
                    'inversedBy' => null,
                    'isOwningSide' => true,
                    'sourceEntity' => CmsUser::class,
                    'isCascadeRemove' => true,
                    'isCascadePersist' => true,
                    'isCascadeRefresh' => true,
                    'isCascadeMerge' => true,
                    'isCascadeDetach' => true,
                    'sourceToTargetKeyColumns' =>
                        ['group_id' => 'id'],
                    'joinColumnFieldNames' =>
                        ['group_id' => 'group_id'],
                    'targetToSourceKeyColumns' =>
                        ['id' => 'group_id'],
                    'orphanRemoval' => false,
                    'id' => true,
                ],
            ],
            $this->cm->associationMappings
        );
    }

    public function testCreateOneToOne(): void
    {
        $this->assertIsFluent(
            $this->builder->createOneToOne('groups', CmsGroup::class)
                              ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                              ->cascadeAll()
                              ->fetchExtraLazy()
                              ->build()
        );

        $this->assertEquals(
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
                        0 =>
                        [
                            'name' => 'group_id',
                            'referencedColumnName' => 'id',
                            'nullable' => true,
                            'unique' => true,
                            'onDelete' => 'CASCADE',
                            'columnDefinition' => null,
                        ],
                    ],
                    'type' => 1,
                    'mappedBy' => null,
                    'inversedBy' => null,
                    'isOwningSide' => true,
                    'sourceEntity' => CmsUser::class,
                    'isCascadeRemove' => true,
                    'isCascadePersist' => true,
                    'isCascadeRefresh' => true,
                    'isCascadeMerge' => true,
                    'isCascadeDetach' => true,
                    'sourceToTargetKeyColumns' =>
                    ['group_id' => 'id'],
                    'joinColumnFieldNames' =>
                    ['group_id' => 'group_id'],
                    'targetToSourceKeyColumns' =>
                    ['id' => 'group_id'],
                    'orphanRemoval' => false,
                ],
            ],
            $this->cm->associationMappings
        );
    }

    public function testCreateOneToOneWithIdentity(): void
    {
        $this->assertIsFluent(
            $this
                ->builder
                ->createOneToOne('groups', CmsGroup::class)
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
                        0 =>
                            [
                                'name' => 'group_id',
                                'referencedColumnName' => 'id',
                                'nullable' => true,
                                'unique' => false,
                                'onDelete' => 'CASCADE',
                                'columnDefinition' => null,
                            ],
                    ],
                    'type' => 1,
                    'mappedBy' => null,
                    'inversedBy' => null,
                    'isOwningSide' => true,
                    'sourceEntity' => CmsUser::class,
                    'isCascadeRemove' => true,
                    'isCascadePersist' => true,
                    'isCascadeRefresh' => true,
                    'isCascadeMerge' => true,
                    'isCascadeDetach' => true,
                    'sourceToTargetKeyColumns' =>
                        ['group_id' => 'id'],
                    'joinColumnFieldNames' =>
                        ['group_id' => 'group_id'],
                    'targetToSourceKeyColumns' =>
                        ['id' => 'group_id'],
                    'orphanRemoval' => false,
                ],
            ],
            $this->cm->associationMappings
        );
    }

    public function testThrowsExceptionOnCreateOneToOneWithIdentityOnInverseSide(): void
    {
        $this->expectException(MappingException::class);

        $this
            ->builder
            ->createOneToOne('groups', CmsGroup::class)
            ->mappedBy('test')
            ->fetchExtraLazy()
            ->makePrimaryKey()
            ->build();
    }

    public function testCreateManyToMany(): void
    {
        $this->assertIsFluent(
            $this->builder->createManyToMany('groups', CmsGroup::class)
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
                                'columnDefinition' => null,
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
                                'onDelete' => null,
                                'columnDefinition' => null,
                            ],
                        ],
                        'name' => 'groups_users',
                    ],
                    'type' => 8,
                    'mappedBy' => null,
                    'inversedBy' => null,
                    'isOwningSide' => true,
                    'sourceEntity' => CmsUser::class,
                    'isCascadeRemove' => true,
                    'isCascadePersist' => true,
                    'isCascadeRefresh' => true,
                    'isCascadeMerge' => true,
                    'isCascadeDetach' => true,
                    'isOnDeleteCascade' => true,
                    'relationToSourceKeyColumns' =>
                    ['group_id' => 'id'],
                    'joinTableColumns' =>
                    [
                        0 => 'group_id',
                        1 => 'user_id',
                    ],
                    'relationToTargetKeyColumns' =>
                    ['user_id' => 'id'],
                    'orphanRemoval' => false,
                ],
            ],
            $this->cm->associationMappings
        );
    }

    public function testThrowsExceptionOnCreateManyToManyWithIdentity(): void
    {
        $this->expectException(MappingException::class);

        $this->builder->createManyToMany('groups', CmsGroup::class)
                          ->makePrimaryKey()
                          ->setJoinTable('groups_users')
                          ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                          ->addInverseJoinColumn('user_id', 'id')
                          ->cascadeAll()
                          ->fetchExtraLazy()
                          ->build();
    }

    public function testCreateOneToMany(): void
    {
        $this->assertIsFluent(
            $this->builder->createOneToMany('groups', CmsGroup::class)
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
                    'targetEntity' => CmsGroup::class,
                    'mappedBy' => 'test',
                    'orderBy' =>
                [0 => 'test'],
                    'indexBy' => 'test',
                    'type' => 4,
                    'inversedBy' => null,
                    'isOwningSide' => false,
                    'sourceEntity' => CmsUser::class,
                    'fetch' => 2,
                    'cascade' =>
                [],
                    'isCascadeRemove' => false,
                    'isCascadePersist' => false,
                    'isCascadeRefresh' => false,
                    'isCascadeMerge' => false,
                    'isCascadeDetach' => false,
                    'orphanRemoval' => false,
                ],
            ],
            $this->cm->associationMappings
        );
    }

    public function testThrowsExceptionOnCreateOneToManyWithIdentity(): void
    {
        $this->expectException(MappingException::class);

        $this->builder->createOneToMany('groups', CmsGroup::class)
                ->makePrimaryKey()
                ->mappedBy('test')
                ->setOrderBy(['test'])
                ->setIndexBy('test')
                ->build();
    }

    public function testOrphanRemovalOnCreateOneToOne(): void
    {
        $this->assertIsFluent(
            $this->builder
                ->createOneToOne('groups', CmsGroup::class)
                ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
                ->orphanRemoval()
                ->build()
        );

        $this->assertEquals(
            [
                'groups' => [
                    'fieldName' => 'groups',
                    'targetEntity' => CmsGroup::class,
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
                            'columnDefinition' => null,
                        ],
                    ],
                    'type' => 1,
                    'mappedBy' => null,
                    'inversedBy' => null,
                    'isOwningSide' => true,
                    'sourceEntity' => CmsUser::class,
                    'isCascadeRemove' => true,
                    'isCascadePersist' => false,
                    'isCascadeRefresh' => false,
                    'isCascadeMerge' => false,
                    'isCascadeDetach' => false,
                    'sourceToTargetKeyColumns' =>
                    ['group_id' => 'id'],
                    'joinColumnFieldNames' =>
                    ['group_id' => 'group_id'],
                    'targetToSourceKeyColumns' =>
                    ['id' => 'group_id'],
                    'orphanRemoval' => true,
                ],
            ],
            $this->cm->associationMappings
        );
    }

    public function testOrphanRemovalOnCreateOneToMany(): void
    {
        $this->assertIsFluent(
            $this->builder
                ->createOneToMany('groups', CmsGroup::class)
                ->mappedBy('test')
                ->orphanRemoval()
                ->build()
        );

        $this->assertEquals(
            [
                'groups' =>
                [
                    'fieldName' => 'groups',
                    'targetEntity' => CmsGroup::class,
                    'mappedBy' => 'test',
                    'type' => 4,
                    'inversedBy' => null,
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
                ],
            ],
            $this->cm->associationMappings
        );
    }

    public function testExceptionOnOrphanRemovalOnManyToOne(): void
    {
        $this->expectException(MappingException::class);

        $this->builder
            ->createManyToOne('groups', CmsGroup::class)
            ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
            ->orphanRemoval()
            ->build();
    }

    public function testOrphanRemovalOnManyToMany(): void
    {
        $this->builder
            ->createManyToMany('groups', CmsGroup::class)
            ->addJoinColumn('group_id', 'id', true, false, 'CASCADE')
            ->orphanRemoval()
            ->build();

        $this->assertEquals(
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
                                'columnDefinition' => null,
                            ],
                        ],
                        'inverseJoinColumns' => [
                            0 => [
                                'name' => 'cmsgroup_id',
                                'referencedColumnName' => 'id',
                                'onDelete' => 'CASCADE',
                            ],
                        ],
                        'name' => 'cmsuser_cmsgroup',
                    ],
                    'type' => 8,
                    'mappedBy' => null,
                    'inversedBy' => null,
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
                ],
            ],
            $this->cm->associationMappings
        );
    }

    public function assertIsFluent($ret): void
    {
        $this->assertSame($this->builder, $ret, 'Return Value has to be same instance as used builder');
    }
}

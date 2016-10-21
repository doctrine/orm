<?php

namespace Doctrine\Tests\ORM\Mapping\Builder;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\DiscriminatorColumnMetadataBuilder;
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
        $discriminatorColumnBuilder = (new DiscriminatorColumnMetadataBuilder())
            ->withColumnName('discr')
            ->withLength(124)
        ;

        self::assertIsFluent($this->builder->setDiscriminatorColumn($discriminatorColumnBuilder->build()));
        self::assertNotNull($this->cm->discriminatorColumn);

        $discrColumn = $this->cm->discriminatorColumn;

        self::assertEquals('CmsUser', $discrColumn->getTableName());
        self::assertEquals('discr', $discrColumn->getColumnName());
        self::assertEquals('string', $discrColumn->getTypeName());
        self::assertEquals(124, $discrColumn->getLength());
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
        self::assertNull($this->cm->getProperty('name'));

        self::assertIsFluent($this->builder->addProperty('name', 'string'));

        self::assertNotNull($this->cm->getProperty('name'));

        $property = $this->cm->getProperty('name');

        self::assertEquals('name', $property->getName());
        self::assertEquals($this->cm, $property->getDeclaringClass());
        self::assertEquals('string', $property->getTypeName());
        self::assertEquals('CmsUser', $property->getTableName());
        self::assertEquals('name', $property->getColumnName());
    }

    public function testCreateField()
    {
        $fieldBuilder = $this->builder->createField('name', 'string');

        self::assertInstanceOf(FieldBuilder::class, $fieldBuilder);
        self::assertNull($this->cm->getProperty('name'));

        self::assertIsFluent($fieldBuilder->build());

        self::assertNotNull($this->cm->getProperty('name'));

        $property = $this->cm->getProperty('name');

        self::assertEquals('name', $property->getName());
        self::assertEquals($this->cm, $property->getDeclaringClass());
        self::assertEquals('string', $property->getTypeName());
        self::assertEquals('CmsUser', $property->getTableName());
        self::assertEquals('name', $property->getColumnName());
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

        self::assertNotNull($this->cm->getProperty('name'));

        $property = $this->cm->getProperty('name');

        self::assertEquals('name', $property->getName());
        self::assertEquals($this->cm, $property->getDeclaringClass());
        self::assertEquals('integer', $property->getTypeName());
        self::assertEquals('CmsUser', $property->getTableName());
        self::assertEquals('username', $property->getColumnName());
        self::assertEquals('foobar', $property->getColumnDefinition());
        self::assertEquals(124, $property->getLength());
        self::assertTrue($property->isNullable());
        self::assertTrue($property->isUnique());
        self::assertEquals(['default' => 1], $property->getOptions());
    }

    public function testCreatePrimaryField()
    {
        $this->builder->createField('id', 'integer')
            ->makePrimaryKey()
            ->generatedValue()
            ->build();

        self::assertNotNull($this->cm->getProperty('id'));

        $property = $this->cm->getProperty('id');

        self::assertEquals(['id'], $this->cm->identifier);
        self::assertEquals('id', $property->getName());
        self::assertEquals($this->cm, $property->getDeclaringClass());
        self::assertEquals('integer', $property->getTypeName());
        self::assertEquals('CmsUser', $property->getTableName());
        self::assertEquals('id', $property->getColumnName());
        self::assertTrue($property->isPrimaryKey());
    }

    public function testCreateUnsignedOptionField()
    {
        $this->builder->createField('state', 'integer')
            ->option('unsigned', true)
            ->build();

        self::assertNotNull($this->cm->getProperty('state'));

        $property = $this->cm->getProperty('state');

        self::assertEquals('state', $property->getName());
        self::assertEquals($this->cm, $property->getDeclaringClass());
        self::assertEquals('integer', $property->getTypeName());
        self::assertEquals('CmsUser', $property->getTableName());
        self::assertEquals('state', $property->getColumnName());
        self::assertEquals(['unsigned' => true], $property->getOptions());
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
                            'columnDefinition'     => null,
                            'tableName'            => 'CmsUser',
                        ],
                    ],
                    'type' => 2,
                    'mappedBy' => null,
                    'inversedBy' => null,
                    'isOwningSide' => true,
                    'sourceEntity' => CmsUser::class,
                    'sourceToTargetKeyColumns' => [
                        'group_id' => 'id',
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
                    'sourceToTargetKeyColumns' => ['group_id' => 'id'],
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
                            'columnDefinition'     => null,
                            'tableName'            => 'CmsUser',
                        ],
                    ],
                    'type' => 1,
                    'mappedBy' => null,
                    'inversedBy' => null,
                    'isOwningSide' => true,
                    'sourceEntity' => CmsUser::class,
                    'sourceToTargetKeyColumns' => ['group_id' => 'id'],
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
                    'sourceToTargetKeyColumns' => ['group_id' => 'id'],
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
                    'relationToSourceKeyColumns' => ['group_id' => 'id'],
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
                    'cascade' => [0 => 'remove'],
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
                    'sourceToTargetKeyColumns' => ['group_id' => 'id'],
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
                    'cascade' => [0 => 'remove'],
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
                    'relationToSourceKeyColumns' => ['group_id' => 'id'],
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

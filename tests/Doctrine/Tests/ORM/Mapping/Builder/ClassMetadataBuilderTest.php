<?php

namespace Doctrine\Tests\ORM\Mapping\Builder;

use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\Mapping\Builder\ClassMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\DiscriminatorColumnMetadataBuilder;
use Doctrine\ORM\Mapping\Builder\EmbeddedBuilder;
use Doctrine\ORM\Mapping\Builder\FieldBuilder;
use Doctrine\ORM\Mapping\ChangeTrackingPolicy;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\ValueObjects\Name;
use Doctrine\ORM\Mapping\FetchMode;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\JoinColumnMetadata;
use Doctrine\ORM\Mapping\JoinTableMetadata;
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
    public function testAsMappedSuperClass()
    {
        self::assertIsFluent($this->builder->asMappedSuperClass());
        self::assertTrue($this->cm->isMappedSuperclass);
        self::assertFalse($this->cm->isEmbeddedClass);
    }

    /**
     * @group embedded
     */
    public function testAsEmbeddable()
    {
        self::assertIsFluent($this->builder->asEmbeddable());
        self::assertTrue($this->cm->isEmbeddedClass);
        self::assertFalse($this->cm->isMappedSuperclass);
    }

    public function testAsReadOnly()
    {
        self::assertIsFluent($this->builder->asReadOnly());
        self::assertTrue($this->cm->isReadOnly());
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

    public function testSetInheritanceJoined()
    {
        self::assertIsFluent($this->builder->setJoinedTableInheritance());
        self::assertEquals(InheritanceType::JOINED, $this->cm->inheritanceType);
    }

    public function testSetInheritanceSingleTable()
    {
        self::assertIsFluent($this->builder->setSingleTableInheritance());
        self::assertEquals(InheritanceType::SINGLE_TABLE, $this->cm->inheritanceType);
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
        self::assertEquals(ChangeTrackingPolicy::DEFERRED_EXPLICIT, $this->cm->changeTrackingPolicy);
    }

    public function testChangeTrackingPolicyNotify()
    {
        self::assertIsFluent($this->builder->setChangeTrackingPolicyNotify());
        self::assertEquals(ChangeTrackingPolicy::NOTIFY, $this->cm->changeTrackingPolicy);
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
        $joinColumn = new JoinColumnMetadata();

        $joinColumn->setTableName('CmsUser');
        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('CASCADE');

        $association = $this->builder->createManyToOne('groups', CmsGroup::class)
            ->withJoinColumn($joinColumn)
            ->withCascade(['ALL'])
            ->withFetchMode(FetchMode::EXTRA_LAZY)
            ->build()
        ;

        $this->cm->addProperty($association);

        self::assertEquals(
            [
                'groups' => $association
            ],
            $this->cm->getProperties()
        );
    }

    public function testCreateManyToOneWithIdentity()
    {
        $joinColumn = new JoinColumnMetadata();

        $joinColumn->setTableName('CmsUser');
        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('CASCADE');

        $association = $this
            ->builder
            ->createManyToOne('groups', CmsGroup::class)
            ->withJoinColumn($joinColumn)
            ->withCascade(['ALL'])
            ->withFetchMode(FetchMode::EXTRA_LAZY)
            ->withPrimaryKey(true)
            ->build()
        ;

        $this->cm->addProperty($association);

        self::assertEquals(
            [
                'groups' => $association
            ],
            $this->cm->getProperties()
        );
    }

    public function testCreateOneToOne()
    {
        $joinColumn = new JoinColumnMetadata();

        $joinColumn->setTableName('CmsUser');
        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('CASCADE');
        $joinColumn->setUnique(true);

        $association = $this->builder->createOneToOne('groups', CmsGroup::class)
            ->withJoinColumn($joinColumn)
            ->withCascade(['ALL'])
            ->withFetchMode(FetchMode::EXTRA_LAZY)
            ->build()
        ;

        $this->cm->addProperty($association);

        self::assertEquals(
            [
                'groups' => $association
            ],
            $this->cm->getProperties()
        );
    }

    public function testCreateOneToOneWithIdentity()
    {
        $joinColumn = new JoinColumnMetadata();

        $joinColumn->setTableName('CmsUser');
        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('CASCADE');

        $association = $this->builder->createOneToOne('groups', CmsGroup::class)
            ->withJoinColumn($joinColumn)
            ->withCascade(['ALL'])
            ->withFetchMode(FetchMode::EXTRA_LAZY)
            ->withPrimaryKey(true)
            ->build()
        ;

        $this->cm->addProperty($association);

        self::assertEquals(
            [
                'groups' => $association
            ],
            $this->cm->getProperties()
        );
    }

    public function testThrowsExceptionOnCreateOneToOneWithIdentityOnInverseSide()
    {
        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $association = $this
            ->builder
            ->createOneToOne('groups', CmsGroup::class)
            ->withMappedBy('test')
            ->withFetchMode(FetchMode::EXTRA_LAZY)
            ->withPrimaryKey(true)
            ->build();

        $this->cm->addProperty($association);
    }

    public function testCreateManyToMany()
    {
        $joinColumn = new JoinColumnMetadata();

        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('CASCADE');

        $inverseJoinColumn = new JoinColumnMetadata();

        $inverseJoinColumn->setColumnName('user_id');
        $inverseJoinColumn->setReferencedColumnName('id');

        $joinTable = new JoinTableMetadata();

        $joinTable->setName('groups_users');
        $joinTable->addJoinColumn($joinColumn);
        $joinTable->addInverseJoinColumn($inverseJoinColumn);

        $association = $this->builder->createManyToMany('groups', CmsGroup::class)
            ->withJoinTable($joinTable)
            ->withCascade(['ALL'])
            ->withFetchMode(FetchMode::EXTRA_LAZY)
            ->build()
        ;

        $this->cm->addProperty($association);

        self::assertEquals(
            [
                'groups' => $association
            ],
            $this->cm->getProperties()
        );
    }

    public function testThrowsExceptionOnCreateManyToManyWithIdentity()
    {
        $joinColumn = new JoinColumnMetadata();

        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('CASCADE');

        $inverseJoinColumn = new JoinColumnMetadata();

        $inverseJoinColumn->setColumnName('user_id');
        $inverseJoinColumn->setReferencedColumnName('id');

        $joinTable = new JoinTableMetadata();

        $joinTable->setName('groups_users');
        $joinTable->addJoinColumn($joinColumn);
        $joinTable->addInverseJoinColumn($inverseJoinColumn);

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $association = $this->builder->createManyToMany('groups', CmsGroup::class)
            ->withJoinTable($joinTable)
            ->withCascade(['ALL'])
            ->withFetchMode(FetchMode::EXTRA_LAZY)
            ->withPrimaryKey(true)
            ->build()
        ;

        $this->cm->addProperty($association);
    }

    public function testCreateOneToMany()
    {
        $association = $this->builder->createOneToMany('groups', CmsGroup::class)
            ->withMappedBy('test')
            ->withOrderBy('test', 'ASC')
            ->withIndexedBy('test')
            ->build()
        ;

        $this->cm->addProperty($association);

        self::assertEquals(
            [
                'groups' => $association
            ],
            $this->cm->getProperties()
        );
    }

    public function testThrowsExceptionOnCreateOneToManyWithIdentity()
    {
        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $association = $this->builder->createOneToMany('groups', CmsGroup::class)
            ->withPrimaryKey(true)
            ->withMappedBy('test')
            ->withOrderBy('test', 'ASC')
            ->withIndexedBy('test')
            ->build();

        $this->cm->addProperty($association);
    }

    public function testOrphanRemovalOnCreateOneToOne()
    {
        $joinColumn = new JoinColumnMetadata();

        $joinColumn->setTableName('CmsUser');
        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('CASCADE');
        $joinColumn->setUnique(true);

        $association = $this->builder
            ->createOneToOne('groups', CmsGroup::class)
            ->withJoinColumn($joinColumn)
            ->withOrphanRemoval(true)
            ->build()
        ;

        $this->cm->addProperty($association);

        self::assertEquals(
            [
                'groups' => $association
            ],
            $this->cm->getProperties()
        );
    }

    public function testOrphanRemovalOnCreateOneToMany()
    {
        $association = $this->builder
            ->createOneToMany('groups', CmsGroup::class)
            ->withMappedBy('test')
            ->withOrphanRemoval(true)
            ->build()
        ;

        $this->cm->addProperty($association);

        self::assertEquals(
            [
                'groups' => $association
            ],
            $this->cm->getProperties()
        );
    }

    public function testExceptionOnOrphanRemovalOnManyToOne()
    {
        $joinColumn = new JoinColumnMetadata();

        $joinColumn->setTableName('CmsUser');
        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('CASCADE');
        $joinColumn->setUnique(true);

        $this->expectException(\Doctrine\ORM\Mapping\MappingException::class);

        $association = $this->builder
            ->createManyToOne('groups', CmsGroup::class)
            ->withJoinColumn($joinColumn)
            ->withOrphanRemoval(true)
            ->build();

        $this->cm->addProperty($association);
    }

    public function testOrphanRemovalOnManyToMany()
    {
        $joinColumn = new JoinColumnMetadata();

        $joinColumn->setColumnName('group_id');
        $joinColumn->setReferencedColumnName('id');
        $joinColumn->setOnDelete('CASCADE');

        $inverseJoinColumn = new JoinColumnMetadata();

        $inverseJoinColumn->setColumnName('cmsgroup_id');
        $inverseJoinColumn->setReferencedColumnName('id');
        $inverseJoinColumn->setOnDelete('CASCADE');

        $joinTable = new JoinTableMetadata();

        $joinTable->setName('cmsuser_cmsgroup');
        $joinTable->addJoinColumn($joinColumn);
        $joinTable->addInverseJoinColumn($inverseJoinColumn);

        $association = $this->builder
            ->createManyToMany('groups', CmsGroup::class)
            ->withJoinTable($joinTable)
            ->withOrphanRemoval(true)
            ->build();

        $this->cm->addProperty($association);

        self::assertEquals(
            [
                'groups' => $association
            ],
            $this->cm->getProperties()
        );
    }

    public function assertIsFluent($ret)
    {
        self::assertSame($this->builder, $ret, "Return Value has to be same instance as used builder");
    }
}

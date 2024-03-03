<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\UnderscoreNamingStrategy;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\Tools\ToolEvents;
use Doctrine\Persistence\Mapping\Driver\StaticPHPDriver;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Tests\Models\CMS\CmsAddress;
use Doctrine\Tests\Models\CMS\CmsArticle;
use Doctrine\Tests\Models\CMS\CmsComment;
use Doctrine\Tests\Models\CMS\CmsEmployee;
use Doctrine\Tests\Models\CMS\CmsGroup;
use Doctrine\Tests\Models\CMS\CmsPhonenumber;
use Doctrine\Tests\Models\CMS\CmsUser;
use Doctrine\Tests\Models\CompositeKeyInheritance\JoinedDerivedChildClass;
use Doctrine\Tests\Models\CompositeKeyInheritance\JoinedDerivedIdentityClass;
use Doctrine\Tests\Models\CompositeKeyInheritance\JoinedDerivedRootClass;
use Doctrine\Tests\Models\Enums\Card;
use Doctrine\Tests\Models\Enums\Suit;
use Doctrine\Tests\Models\Forum\ForumAvatar;
use Doctrine\Tests\Models\Forum\ForumUser;
use Doctrine\Tests\Models\NullDefault\NullDefaultColumn;
use Doctrine\Tests\OrmTestCase;
use PHPUnit\Framework\Attributes\Group;

use function count;
use function current;

class SchemaToolTest extends OrmTestCase
{
    public function testAddUniqueIndexForUniqueFieldAttribute(): void
    {
        $em         = $this->getTestEntityManager();
        $schemaTool = new SchemaTool($em);

        $classes = [
            $em->getClassMetadata(CmsAddress::class),
            $em->getClassMetadata(CmsArticle::class),
            $em->getClassMetadata(CmsComment::class),
            $em->getClassMetadata(CmsEmployee::class),
            $em->getClassMetadata(CmsGroup::class),
            $em->getClassMetadata(CmsPhonenumber::class),
            $em->getClassMetadata(CmsUser::class),
        ];

        $schema = $schemaTool->getSchemaFromMetadata($classes);

        self::assertTrue($schema->hasTable('cms_users'), 'Table cms_users should exist.');
        self::assertTrue($schema->getTable('cms_users')->columnsAreIndexed(['username']), 'username column should be indexed.');
    }

    public function testAttributeOptionsArgument(): void
    {
        $em         = $this->getTestEntityManager();
        $schemaTool = new SchemaTool($em);

        $schema = $schemaTool->getSchemaFromMetadata(
            [$em->getClassMetadata(TestEntityWithAttributeOptionsArgument::class)],
        );
        $table  = $schema->getTable('TestEntityWithAttributeOptionsArgument');

        foreach ([$table->getOptions(), $table->getColumn('test')->getPlatformOptions()] as $options) {
            self::assertArrayHasKey('foo', $options);
            self::assertSame('bar', $options['foo']);
            self::assertArrayHasKey('baz', $options);
            self::assertSame(['key' => 'val'], $options['baz']);
        }
    }

    #[Group('DDC-200')]
    public function testPassColumnDefinitionToJoinColumn(): void
    {
        $customColumnDef = 'MEDIUMINT(6) UNSIGNED NOT NULL';

        $em         = $this->getTestEntityManager();
        $schemaTool = new SchemaTool($em);

        $avatar                                        = $em->getClassMetadata(ForumAvatar::class);
        $avatar->fieldMappings['id']->columnDefinition = $customColumnDef;
        $user                                          = $em->getClassMetadata(ForumUser::class);

        $classes = [$avatar, $user];

        $schema = $schemaTool->getSchemaFromMetadata($classes);

        self::assertTrue($schema->hasTable('forum_users'));
        $table = $schema->getTable('forum_users');
        self::assertTrue($table->hasColumn('avatar_id'));
        self::assertEquals($customColumnDef, $table->getColumn('avatar_id')->getColumnDefinition());
    }

    #[Group('6830')]
    public function testPassColumnOptionsToJoinColumn(): void
    {
        $em       = $this->getTestEntityManager();
        $category = $em->getClassMetadata(GH6830Category::class);
        $board    = $em->getClassMetadata(GH6830Board::class);

        $schemaTool = new SchemaTool($em);
        $schema     = $schemaTool->getSchemaFromMetadata([$category, $board]);

        self::assertTrue($schema->hasTable('GH6830Category'));
        self::assertTrue($schema->hasTable('GH6830Board'));

        $tableCategory = $schema->getTable('GH6830Category');
        $tableBoard    = $schema->getTable('GH6830Board');

        self::assertTrue($tableBoard->hasColumn('category_id'));

        self::assertSame(
            $tableCategory->getColumn('id')->getFixed(),
            $tableBoard->getColumn('category_id')->getFixed(),
            'Foreign key/join column should have the same value of option `fixed` as the referenced column',
        );

        self::assertEquals(
            $tableCategory->getColumn('id')->getPlatformOptions(),
            $tableBoard->getColumn('category_id')->getPlatformOptions(),
            'Foreign key/join column should have the same custom options as the referenced column',
        );

        self::assertEquals(
            ['collation' => 'latin1_bin', 'foo' => 'bar'],
            $tableBoard->getColumn('category_id')->getPlatformOptions(),
        );
    }

    #[Group('DDC-283')]
    public function testPostGenerateEvents(): void
    {
        $listener = new GenerateSchemaEventListener();

        $em = $this->getTestEntityManager();
        $em->getEventManager()->addEventListener(
            [ToolEvents::postGenerateSchemaTable, ToolEvents::postGenerateSchema],
            $listener,
        );
        $schemaTool = new SchemaTool($em);

        $classes = [
            $em->getClassMetadata(CmsAddress::class),
            $em->getClassMetadata(CmsArticle::class),
            $em->getClassMetadata(CmsComment::class),
            $em->getClassMetadata(CmsEmployee::class),
            $em->getClassMetadata(CmsGroup::class),
            $em->getClassMetadata(CmsPhonenumber::class),
            $em->getClassMetadata(CmsUser::class),
        ];

        $schema = $schemaTool->getSchemaFromMetadata($classes);

        self::assertEquals(count($classes), $listener->tableCalls);
        self::assertTrue($listener->schemaCalled);
    }

    public function testNullDefaultNotAddedToPlatformOptions(): void
    {
        $em         = $this->getTestEntityManager();
        $schemaTool = new SchemaTool($em);

        self::assertSame([], $schemaTool->getSchemaFromMetadata([$em->getClassMetadata(NullDefaultColumn::class)])
            ->getTable('NullDefaultColumn')
            ->getColumn('nullDefault')
            ->getPlatformOptions());
    }

    public function testEnumTypeAddedToCustomSchemaOptions(): void
    {
        $em         = $this->getTestEntityManager();
        $schemaTool = new SchemaTool($em);

        $platformOptions = $schemaTool->getSchemaFromMetadata([$em->getClassMetadata(Card::class)])
            ->getTable('Card')
            ->getColumn('suit')
            ->getPlatformOptions();

        self::assertArrayHasKey('enumType', $platformOptions);
        self::assertSame(Suit::class, $platformOptions['enumType']);
    }

    #[Group('DDC-3671')]
    public function testSchemaHasProperIndexesFromUniqueConstraintAttribute(): void
    {
        $em         = $this->getTestEntityManager();
        $schemaTool = new SchemaTool($em);
        $classes    = [
            $em->getClassMetadata(UniqueConstraintAttributeModel::class),
        ];

        $schema = $schemaTool->getSchemaFromMetadata($classes);

        self::assertTrue($schema->hasTable('unique_constraint_attribute_table'));
        $table = $schema->getTable('unique_constraint_attribute_table');

        self::assertCount(2, $table->getIndexes());
        self::assertTrue($table->hasIndex('primary'));
        self::assertTrue($table->hasIndex('uniq_hash'));
    }

    public function testRemoveUniqueIndexOverruledByPrimaryKey(): void
    {
        $em         = $this->getTestEntityManager();
        $schemaTool = new SchemaTool($em);
        $classes    = [
            $em->getClassMetadata(FirstEntity::class),
            $em->getClassMetadata(SecondEntity::class),
        ];

        $schema = $schemaTool->getSchemaFromMetadata($classes);

        self::assertTrue($schema->hasTable('first_entity'), 'Table first_entity should exist.');

        $indexes = $schema->getTable('first_entity')->getIndexes();

        self::assertCount(1, $indexes, 'there should be only one index');
        self::assertTrue(current($indexes)->isPrimary(), 'index should be primary');
    }

    public function testSetDiscriminatorColumnWithoutLength(): void
    {
        $em         = $this->getTestEntityManager();
        $schemaTool = new SchemaTool($em);
        $metadata   = $em->getClassMetadata(FirstEntity::class);

        $metadata->setInheritanceType(ClassMetadata::INHERITANCE_TYPE_SINGLE_TABLE);
        $metadata->setDiscriminatorColumn(['name' => 'discriminator', 'type' => 'string']);

        $schema = $schemaTool->getSchemaFromMetadata([$metadata]);

        self::assertTrue($schema->hasTable('first_entity'));
        $table = $schema->getTable('first_entity');

        self::assertTrue($table->hasColumn('discriminator'));
        $column = $table->getColumn('discriminator');

        self::assertEquals(255, $column->getLength());
    }

    public function testDerivedCompositeKey(): void
    {
        $em         = $this->getTestEntityManager();
        $schemaTool = new SchemaTool($em);

        $schema = $schemaTool->getSchemaFromMetadata(
            [
                $em->getClassMetadata(JoinedDerivedIdentityClass::class),
                $em->getClassMetadata(JoinedDerivedRootClass::class),
                $em->getClassMetadata(JoinedDerivedChildClass::class),
            ],
        );

        self::assertTrue($schema->hasTable('joined_derived_identity'));
        self::assertTrue($schema->hasTable('joined_derived_root'));
        self::assertTrue($schema->hasTable('joined_derived_child'));

        $rootTable = $schema->getTable('joined_derived_root');
        self::assertNotNull($rootTable->getPrimaryKey());
        self::assertSame(['keyPart1_id', 'keyPart2'], $rootTable->getPrimaryKey()->getColumns());

        $childTable = $schema->getTable('joined_derived_child');
        self::assertNotNull($childTable->getPrimaryKey());
        self::assertSame(['keyPart1_id', 'keyPart2'], $childTable->getPrimaryKey()->getColumns());

        $childTableForeignKeys = $childTable->getForeignKeys();

        self::assertCount(2, $childTableForeignKeys);

        $expectedColumns = [
            'joined_derived_identity' => [['keyPart1_id'], ['id']],
            'joined_derived_root'     => [['keyPart1_id', 'keyPart2'], ['keyPart1_id', 'keyPart2']],
        ];

        foreach ($childTableForeignKeys as $foreignKey) {
            self::assertArrayHasKey($foreignKey->getForeignTableName(), $expectedColumns);

            [$localColumns, $foreignColumns] = $expectedColumns[$foreignKey->getForeignTableName()];

            self::assertSame($localColumns, $foreignKey->getLocalColumns());
            self::assertSame($foreignColumns, $foreignKey->getForeignColumns());
        }
    }

    public function testIndexesBasedOnFields(): void
    {
        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setNamingStrategy(new UnderscoreNamingStrategy());

        $schemaTool = new SchemaTool($em);
        $metadata   = $em->getClassMetadata(IndexByFieldEntity::class);
        $schema     = $schemaTool->getSchemaFromMetadata([$metadata]);
        $table      = $schema->getTable('field_index');

        self::assertEquals(['index', 'field_name'], $table->getIndex('index')->getColumns());
        self::assertEquals(['index', 'table'], $table->getIndex('uniq')->getColumns());
    }

    public function testIncorrectIndexesBasedOnFields(): void
    {
        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setNamingStrategy(new UnderscoreNamingStrategy());

        $schemaTool    = new SchemaTool($em);
        $mappingDriver = new StaticPHPDriver([]);
        $class         = new ClassMetadata(IncorrectIndexByFieldEntity::class);

        $class->initializeReflection(new RuntimeReflectionService());
        $mappingDriver->loadMetadataForClass(IncorrectIndexByFieldEntity::class, $class);

        $this->expectException(MappingException::class);
        $schemaTool->getSchemaFromMetadata([$class]);
    }

    public function testIncorrectUniqueConstraintsBasedOnFields(): void
    {
        $em = $this->getTestEntityManager();
        $em->getConfiguration()->setNamingStrategy(new UnderscoreNamingStrategy());

        $schemaTool    = new SchemaTool($em);
        $mappingDriver = new StaticPHPDriver([]);
        $class         = new ClassMetadata(IncorrectUniqueConstraintByFieldEntity::class);

        $class->initializeReflection(new RuntimeReflectionService());
        $mappingDriver->loadMetadataForClass(IncorrectUniqueConstraintByFieldEntity::class, $class);

        $this->expectException(MappingException::class);
        $schemaTool->getSchemaFromMetadata([$class]);
    }

    #[Group('schema-configuration')]
    public function testConfigurationSchemaIgnoredEntity(): void
    {
        $em         = $this->getTestEntityManager();
        $schemaTool = new SchemaTool($em);

        $classes = [
            $em->getClassMetadata(FirstEntity::class),
            $em->getClassMetadata(SecondEntity::class),
        ];

        $schema = $schemaTool->getSchemaFromMetadata($classes);

        self::assertTrue($schema->hasTable('first_entity'), 'Table first_entity should exist.');
        self::assertTrue($schema->hasTable('second_entity'), 'Table second_entity should exist.');

        $em->getConfiguration()->setSchemaIgnoreClasses([
            SecondEntity::class,
        ]);

        $schema = $schemaTool->getSchemaFromMetadata($classes);

        self::assertTrue($schema->hasTable('first_entity'), 'Table first_entity should exist.');
        self::assertFalse($schema->hasTable('second_entity'), 'Table second_entity should not exist.');
    }

    #[Group('GH-11314')]
    public function testLoadUniqueConstraintWithoutName(): void
    {
        $em     = $this->getTestEntityManager();
        $entity = $em->getClassMetadata(GH11314Entity::class);

        $schemaTool = new SchemaTool($em);
        $schema     = $schemaTool->getSchemaFromMetadata([$entity]);

        self::assertTrue($schema->hasTable('GH11314Entity'));

        $tableEntity = $schema->getTable('GH11314Entity');

        self::assertTrue($tableEntity->hasIndex('uniq_2d81a3ed5bf54558875f7fd5'));

        $tableIndex = $tableEntity->getIndex('uniq_2d81a3ed5bf54558875f7fd5');

        self::assertTrue($tableIndex->isUnique());
        self::assertSame(['field', 'anotherField'], $tableIndex->getColumns());
    }
}

#[Table(options: ['foo' => 'bar', 'baz' => ['key' => 'val']])]
#[Entity]
class TestEntityWithAttributeOptionsArgument
{
    #[Id]
    #[Column]
    private int $id;

    #[Column(type: 'string', options: ['foo' => 'bar', 'baz' => ['key' => 'val']])]
    private string $test;
}

class GenerateSchemaEventListener
{
    /** @var int */
    public $tableCalls = 0;

    /** @var bool */
    public $schemaCalled = false;

    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $eventArgs): void
    {
        $this->tableCalls++;
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $eventArgs): void
    {
        $this->schemaCalled = true;
    }
}

#[Table(name: 'unique_constraint_attribute_table')]
#[UniqueConstraint(name: 'uniq_hash', columns: ['hash'])]
#[Entity]
class UniqueConstraintAttributeModel
{
    #[Id]
    #[Column]
    private int $id;

    #[Column(name: 'hash', type: 'string', length: 8, nullable: false, unique: true)]
    private string $hash;
}

#[Table(name: 'first_entity')]
#[Entity]
class FirstEntity
{
    /** @var int */
    #[Id]
    #[Column(name: 'id')]
    public $id;

    /** @var SecondEntity */
    #[OneToOne(targetEntity: 'SecondEntity')]
    #[JoinColumn(name: 'id', referencedColumnName: 'first_entity_id')]
    public $secondEntity;

    /** @var string */
    #[Column(name: 'name')]
    public $name;
}

#[Table(name: 'second_entity')]
#[Entity]
class SecondEntity
{
    /** @var int */
    #[Id]
    #[Column(name: 'first_entity_id')]
    public $firstEntityId;

    /** @var string */
    #[Column(name: 'name')]
    public $name;
}

#[Entity]
class GH6830Board
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    public $id;

    /** @var GH6830Category */
    #[ManyToOne(targetEntity: GH6830Category::class, inversedBy: 'boards')]
    #[JoinColumn(name: 'category_id', referencedColumnName: 'id')]
    public $category;
}

#[Entity]
class GH6830Category
{
    /** @var string */
    #[Id]
    #[Column(type: 'string', length: 8, options: ['fixed' => true, 'collation' => 'latin1_bin', 'foo' => 'bar'])]
    public $id;

    /** @psalm-var Collection<int, GH6830Board> */
    #[OneToMany(targetEntity: GH6830Board::class, mappedBy: 'category')]
    public $boards;
}

#[Table(name: 'field_index')]
#[Index(name: 'index', fields: ['index', 'fieldName'])]
#[UniqueConstraint(name: 'uniq', fields: ['index', 'table'])]
#[Entity]
class IndexByFieldEntity
{
    /** @var int */
    #[Id]
    #[Column(type: 'integer')]
    public $id;

    /** @var string */
    #[Column]
    public $index;

    /** @var string */
    #[Column]
    public $table;

    /** @var string */
    #[Column]
    public $fieldName;
}

#[Entity]
#[UniqueConstraint(columns: ['field', 'anotherField'])]
class GH11314Entity
{
    #[Column]
    #[Id]
    private int $id;

    #[Column(name: 'field')]
    private string $field;

    #[Column(name: 'anotherField')]
    private string $anotherField;
}

class IncorrectIndexByFieldEntity
{
    /** @var int */
    public $id;

    /** @var string */
    public $index;

    /** @var string */
    public $table;

    /** @var string */
    public $fieldName;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->mapField(
            [
                'id'                 => true,
                'fieldName'          => 'id',
            ],
        );

        $metadata->mapField(['fieldName' => 'index']);

        $metadata->mapField(['fieldName' => 'table']);

        $metadata->mapField(['fieldName' => 'fieldName']);

        $metadata->setPrimaryTable(
            [
                'indexes' => [
                    ['columns' => ['index'], 'fields' => ['fieldName']],
                ],
            ],
        );
    }
}

class IncorrectUniqueConstraintByFieldEntity
{
    /** @var int */
    public $id;

    /** @var string */
    public $index;

    /** @var string */
    public $table;

    /** @var string */
    public $fieldName;

    public static function loadMetadata(ClassMetadata $metadata): void
    {
        $metadata->mapField(
            [
                'id'                 => true,
                'fieldName'          => 'id',
            ],
        );

        $metadata->mapField(['fieldName' => 'index']);

        $metadata->mapField(['fieldName' => 'table']);

        $metadata->mapField(['fieldName' => 'fieldName']);

        $metadata->setPrimaryTable(
            [
                'uniqueConstraints' => [
                    ['columns' => ['index'], 'fields' => ['fieldName']],
                ],
            ],
        );
    }
}

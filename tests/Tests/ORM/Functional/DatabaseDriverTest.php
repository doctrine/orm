<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Name\Identifier;
use Doctrine\DBAL\Schema\Name\UnqualifiedName;
use Doctrine\DBAL\Schema\PrimaryKeyConstraint;
use Doctrine\DBAL\Schema\Table;
use PHPUnit\Framework\Attributes\Group;

use function array_change_key_case;
use function class_exists;
use function count;
use function strtolower;

use const CASE_LOWER;

class DatabaseDriverTest extends DatabaseDriverTestCase
{
    /** @var AbstractSchemaManager */
    protected $schemaManager = null;

    protected function setUp(): void
    {
        $this->useModelSet('cms');

        parent::setUp();

        $this->schemaManager = $this->createSchemaManager();
    }

    #[Group('DDC-2059')]
    public function testIssue2059(): void
    {
        $user = new Table('ddc2059_user');
        $user->addColumn('id', 'integer');

        if (class_exists(PrimaryKeyConstraint::class)) {
            $user->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [new UnqualifiedName(Identifier::unquoted('id'))], true));
        } else {
            $user->setPrimaryKey(['id']);
        }

        $project = new Table('ddc2059_project');
        $project->addColumn('id', 'integer');
        $project->addColumn('user_id', 'integer');
        $project->addColumn('user', 'string');

        if (class_exists(PrimaryKeyConstraint::class)) {
            $project->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [new UnqualifiedName(Identifier::unquoted('id'))], true));
        } else {
            $project->setPrimaryKey(['id']);
        }

        $project->addForeignKeyConstraint('ddc2059_user', ['user_id'], ['id']);

        $metadata = $this->convertToClassMetadata([$project, $user], []);

        self::assertTrue(isset($metadata['Ddc2059Project']->fieldMappings['user']));
        self::assertTrue(isset($metadata['Ddc2059Project']->associationMappings['user2']));
    }

    public function testLoadMetadataFromDatabase(): void
    {
        $table = new Table('dbdriver_foo');
        $table->addColumn('id', 'integer');

        if (class_exists(PrimaryKeyConstraint::class)) {
            $table->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [new UnqualifiedName(Identifier::unquoted('id'))], true));
        } else {
            $table->setPrimaryKey(['id']);
        }

        $table->addColumn('bar', 'string', ['notnull' => false, 'length' => 200]);

        $this->dropAndCreateTable($table);

        $metadatas = $this->extractClassMetadata(['DbdriverFoo']);

        self::assertArrayHasKey('DbdriverFoo', $metadatas);
        $metadata = $metadatas['DbdriverFoo'];

        self::assertArrayHasKey('id', $metadata->fieldMappings);
        self::assertEquals('id', $metadata->fieldMappings['id']->fieldName);
        self::assertEquals('id', strtolower($metadata->fieldMappings['id']->columnName));
        self::assertEquals('integer', (string) $metadata->fieldMappings['id']->type);

        self::assertArrayHasKey('bar', $metadata->fieldMappings);
        self::assertEquals('bar', $metadata->fieldMappings['bar']->fieldName);
        self::assertEquals('bar', strtolower($metadata->fieldMappings['bar']->columnName));
        self::assertEquals('string', (string) $metadata->fieldMappings['bar']->type);
        self::assertEquals(200, $metadata->fieldMappings['bar']->length);
        self::assertTrue($metadata->fieldMappings['bar']->nullable);
    }

    public function testLoadMetadataWithForeignKeyFromDatabase(): void
    {
        $tableB = new Table('dbdriver_bar');
        $tableB->addColumn('id', 'integer');

        if (class_exists(PrimaryKeyConstraint::class)) {
            $tableB->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [new UnqualifiedName(Identifier::unquoted('id'))], true));
        } else {
            $tableB->setPrimaryKey(['id']);
        }

        $this->dropAndCreateTable($tableB);

        $tableA = new Table('dbdriver_baz');
        $tableA->addColumn('id', 'integer');

        if (class_exists(PrimaryKeyConstraint::class)) {
            $tableA->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [new UnqualifiedName(Identifier::unquoted('id'))], true));
        } else {
            $tableA->setPrimaryKey(['id']);
        }

        $tableA->addColumn('bar_id', 'integer');
        $tableA->addForeignKeyConstraint('dbdriver_bar', ['bar_id'], ['id']);

        $this->dropAndCreateTable($tableA);

        $metadatas = $this->extractClassMetadata(['DbdriverBar', 'DbdriverBaz']);

        self::assertArrayHasKey('DbdriverBaz', $metadatas);
        $bazMetadata = $metadatas['DbdriverBaz'];

        self::assertArrayNotHasKey('barId', $bazMetadata->fieldMappings, "The foreign Key field should not be inflected as 'barId' field, its an association.");
        self::assertArrayHasKey('id', $bazMetadata->fieldMappings);

        $bazMetadata->associationMappings = array_change_key_case($bazMetadata->associationMappings, CASE_LOWER);

        self::assertArrayHasKey('bar', $bazMetadata->associationMappings);
        self::assertTrue($bazMetadata->associationMappings['bar']->isManyToOne());
    }

    public function testDetectManyToManyTables(): void
    {
        $metadatas = $this->extractClassMetadata(['CmsUsers', 'CmsGroups', 'CmsTags']);

        self::assertArrayHasKey('CmsUsers', $metadatas, 'CmsUsers entity was not detected.');
        self::assertArrayHasKey('CmsGroups', $metadatas, 'CmsGroups entity was not detected.');
        self::assertArrayHasKey('CmsTags', $metadatas, 'CmsTags entity was not detected.');

        self::assertEquals(3, count($metadatas['CmsUsers']->associationMappings));
        self::assertArrayHasKey('group', $metadatas['CmsUsers']->associationMappings);
        self::assertEquals(1, count($metadatas['CmsGroups']->associationMappings));
        self::assertArrayHasKey('user', $metadatas['CmsGroups']->associationMappings);
        self::assertEquals(1, count($metadatas['CmsTags']->associationMappings));
        self::assertArrayHasKey('user', $metadatas['CmsGroups']->associationMappings);
    }

    public function testIgnoreManyToManyTableWithoutFurtherForeignKeyDetails(): void
    {
        $tableB = new Table('dbdriver_bar');
        $tableB->addColumn('id', 'integer');

        if (class_exists(PrimaryKeyConstraint::class)) {
            $tableB->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [new UnqualifiedName(Identifier::unquoted('id'))], true));
        } else {
            $tableB->setPrimaryKey(['id']);
        }

        $tableA = new Table('dbdriver_baz');
        $tableA->addColumn('id', 'integer');

        if (class_exists(PrimaryKeyConstraint::class)) {
            $tableA->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [new UnqualifiedName(Identifier::unquoted('id'))], true));
        } else {
            $tableA->setPrimaryKey(['id']);
        }

        $tableMany = new Table('dbdriver_bar_baz');
        $tableMany->addColumn('bar_id', 'integer');
        $tableMany->addColumn('baz_id', 'integer');
        $tableMany->addForeignKeyConstraint('dbdriver_bar', ['bar_id'], ['id']);

        $metadatas = $this->convertToClassMetadata([$tableA, $tableB], [$tableMany]);

        self::assertEquals(0, count($metadatas['DbdriverBaz']->associationMappings), 'no association mappings should be detected.');
    }

    public function testLoadMetadataFromDatabaseDetail(): void
    {
        $table = new Table('dbdriver_foo');

        $table->addColumn('id', 'integer', ['unsigned' => true]);

        if (class_exists(PrimaryKeyConstraint::class)) {
            $table->addPrimaryKeyConstraint(new PrimaryKeyConstraint(null, [new UnqualifiedName(Identifier::unquoted('id'))], true));
        } else {
            $table->setPrimaryKey(['id']);
        }

        $table->addColumn('column_unsigned', 'integer', ['unsigned' => true]);
        $table->addColumn('column_comment', 'string', ['length' => 16, 'comment' => 'test_comment']);
        $table->addColumn('column_default', 'string', ['length' => 16, 'default' => 'test_default']);
        $table->addColumn('column_decimal', 'decimal', ['precision' => 4, 'scale' => 3]);

        $table->addColumn('column_index1', 'string', ['length' => 16]);
        $table->addColumn('column_index2', 'string', ['length' => 16]);
        $table->addIndex(['column_index1', 'column_index2'], 'index1');

        $table->addColumn('column_unique_index1', 'string', ['length' => 16]);
        $table->addColumn('column_unique_index2', 'string', ['length' => 16]);
        $table->addUniqueIndex(['column_unique_index1', 'column_unique_index2'], 'unique_index1');

        $this->dropAndCreateTable($table);

        $metadatas = $this->extractClassMetadata(['DbdriverFoo']);

        self::assertArrayHasKey('DbdriverFoo', $metadatas);

        $metadata = $metadatas['DbdriverFoo'];

        self::assertArrayHasKey('id', $metadata->fieldMappings);
        self::assertEquals('id', $metadata->fieldMappings['id']->fieldName);
        self::assertEquals('id', strtolower($metadata->fieldMappings['id']->columnName));
        self::assertEquals('integer', (string) $metadata->fieldMappings['id']->type);

        if (self::supportsUnsignedInteger($this->_em->getConnection()->getDatabasePlatform())) {
            self::assertArrayHasKey('columnUnsigned', $metadata->fieldMappings);
            self::assertTrue($metadata->fieldMappings['columnUnsigned']->options['unsigned']);
        }

        self::assertArrayHasKey('columnComment', $metadata->fieldMappings);
        self::assertEquals('test_comment', $metadata->fieldMappings['columnComment']->options['comment']);

        self::assertArrayHasKey('columnDefault', $metadata->fieldMappings);
        self::assertEquals('test_default', $metadata->fieldMappings['columnDefault']->options['default']);

        self::assertArrayHasKey('columnDecimal', $metadata->fieldMappings);
        self::assertEquals(4, $metadata->fieldMappings['columnDecimal']->precision);
        self::assertEquals(3, $metadata->fieldMappings['columnDecimal']->scale);

        self::assertNotEmpty($metadata->table['indexes']['index1']['columns']);
        self::assertEquals(
            ['column_index1', 'column_index2'],
            $metadata->table['indexes']['index1']['columns'],
        );

        self::assertNotEmpty($metadata->table['uniqueConstraints']['unique_index1']['columns']);
        self::assertEquals(
            ['column_unique_index1', 'column_unique_index2'],
            $metadata->table['uniqueConstraints']['unique_index1']['columns'],
        );
    }

    private static function supportsUnsignedInteger(AbstractPlatform $platform): bool
    {
        // FIXME: Condition here is fugly.
        // NOTE: PostgreSQL and SQL SERVER do not support UNSIGNED integer

        return ! $platform instanceof SQLServerPlatform
            && ! $platform instanceof PostgreSQLPlatform;
    }
}

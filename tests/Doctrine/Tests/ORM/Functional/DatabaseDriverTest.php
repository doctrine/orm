<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Platforms\SQLServer2012Platform;
use Doctrine\DBAL\Platforms\SQLServerPlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\ORM\Mapping\ClassMetadataInfo;

use function array_change_key_case;
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

    /**
     * @group DDC-2059
     */
    public function testIssue2059(): void
    {
        if (! $this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            self::markTestSkipped('Platform does not support foreign keys.');
        }

        $user = new Table('ddc2059_user');
        $user->addColumn('id', 'integer');
        $user->setPrimaryKey(['id']);
        $project = new Table('ddc2059_project');
        $project->addColumn('id', 'integer');
        $project->addColumn('user_id', 'integer');
        $project->addColumn('user', 'string');
        $project->setPrimaryKey(['id']);
        $project->addForeignKeyConstraint('ddc2059_user', ['user_id'], ['id']);

        $metadata = $this->convertToClassMetadata([$project, $user], []);

        self::assertTrue(isset($metadata['Ddc2059Project']->fieldMappings['user']));
        self::assertTrue(isset($metadata['Ddc2059Project']->associationMappings['user2']));
    }

    public function testLoadMetadataFromDatabase(): void
    {
        if (! $this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            self::markTestSkipped('Platform does not support foreign keys.');
        }

        $table = new Table('dbdriver_foo');
        $table->addColumn('id', 'integer');
        $table->setPrimaryKey(['id']);
        $table->addColumn('bar', 'string', ['notnull' => false, 'length' => 200]);

        $this->schemaManager->dropAndCreateTable($table);

        $metadatas = $this->extractClassMetadata(['DbdriverFoo']);

        self::assertArrayHasKey('DbdriverFoo', $metadatas);
        $metadata = $metadatas['DbdriverFoo'];

        self::assertArrayHasKey('id', $metadata->fieldMappings);
        self::assertEquals('id', $metadata->fieldMappings['id']['fieldName']);
        self::assertEquals('id', strtolower($metadata->fieldMappings['id']['columnName']));
        self::assertEquals('integer', (string) $metadata->fieldMappings['id']['type']);

        self::assertArrayHasKey('bar', $metadata->fieldMappings);
        self::assertEquals('bar', $metadata->fieldMappings['bar']['fieldName']);
        self::assertEquals('bar', strtolower($metadata->fieldMappings['bar']['columnName']));
        self::assertEquals('string', (string) $metadata->fieldMappings['bar']['type']);
        self::assertEquals(200, $metadata->fieldMappings['bar']['length']);
        self::assertTrue($metadata->fieldMappings['bar']['nullable']);
    }

    public function testLoadMetadataWithForeignKeyFromDatabase(): void
    {
        if (! $this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            self::markTestSkipped('Platform does not support foreign keys.');
        }

        $tableB = new Table('dbdriver_bar');
        $tableB->addColumn('id', 'integer');
        $tableB->setPrimaryKey(['id']);

        $this->schemaManager->dropAndCreateTable($tableB);

        $tableA = new Table('dbdriver_baz');
        $tableA->addColumn('id', 'integer');
        $tableA->setPrimaryKey(['id']);
        $tableA->addColumn('bar_id', 'integer');
        $tableA->addForeignKeyConstraint('dbdriver_bar', ['bar_id'], ['id']);

        $this->schemaManager->dropAndCreateTable($tableA);

        $metadatas = $this->extractClassMetadata(['DbdriverBar', 'DbdriverBaz']);

        self::assertArrayHasKey('DbdriverBaz', $metadatas);
        $bazMetadata = $metadatas['DbdriverBaz'];

        self::assertArrayNotHasKey('barId', $bazMetadata->fieldMappings, "The foreign Key field should not be inflected as 'barId' field, its an association.");
        self::assertArrayHasKey('id', $bazMetadata->fieldMappings);

        $bazMetadata->associationMappings = array_change_key_case($bazMetadata->associationMappings, CASE_LOWER);

        self::assertArrayHasKey('bar', $bazMetadata->associationMappings);
        self::assertEquals(ClassMetadataInfo::MANY_TO_ONE, $bazMetadata->associationMappings['bar']['type']);
    }

    public function testDetectManyToManyTables(): void
    {
        if (! $this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            self::markTestSkipped('Platform does not support foreign keys.');
        }

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
        $tableB->setPrimaryKey(['id']);

        $tableA = new Table('dbdriver_baz');
        $tableA->addColumn('id', 'integer');
        $tableA->setPrimaryKey(['id']);

        $tableMany = new Table('dbdriver_bar_baz');
        $tableMany->addColumn('bar_id', 'integer');
        $tableMany->addColumn('baz_id', 'integer');
        $tableMany->addForeignKeyConstraint('dbdriver_bar', ['bar_id'], ['id']);

        $metadatas = $this->convertToClassMetadata([$tableA, $tableB], [$tableMany]);

        self::assertEquals(0, count($metadatas['DbdriverBaz']->associationMappings), 'no association mappings should be detected.');
    }

    public function testLoadMetadataFromDatabaseDetail(): void
    {
        if (! $this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            self::markTestSkipped('Platform does not support foreign keys.');
        }

        $table = new Table('dbdriver_foo');

        $table->addColumn('id', 'integer', ['unsigned' => true]);
        $table->setPrimaryKey(['id']);
        $table->addColumn('column_unsigned', 'integer', ['unsigned' => true]);
        $table->addColumn('column_comment', 'string', ['comment' => 'test_comment']);
        $table->addColumn('column_default', 'string', ['default' => 'test_default']);
        $table->addColumn('column_decimal', 'decimal', ['precision' => 4, 'scale' => 3]);

        $table->addColumn('column_index1', 'string');
        $table->addColumn('column_index2', 'string');
        $table->addIndex(['column_index1', 'column_index2'], 'index1');

        $table->addColumn('column_unique_index1', 'string');
        $table->addColumn('column_unique_index2', 'string');
        $table->addUniqueIndex(['column_unique_index1', 'column_unique_index2'], 'unique_index1');

        $this->schemaManager->dropAndCreateTable($table);

        $metadatas = $this->extractClassMetadata(['DbdriverFoo']);

        self::assertArrayHasKey('DbdriverFoo', $metadatas);

        $metadata = $metadatas['DbdriverFoo'];

        self::assertArrayHasKey('id', $metadata->fieldMappings);
        self::assertEquals('id', $metadata->fieldMappings['id']['fieldName']);
        self::assertEquals('id', strtolower($metadata->fieldMappings['id']['columnName']));
        self::assertEquals('integer', (string) $metadata->fieldMappings['id']['type']);

        if (self::supportsUnsignedInteger($this->_em->getConnection()->getDatabasePlatform())) {
            self::assertArrayHasKey('columnUnsigned', $metadata->fieldMappings);
            self::assertTrue($metadata->fieldMappings['columnUnsigned']['options']['unsigned']);
        }

        self::assertArrayHasKey('columnComment', $metadata->fieldMappings);
        self::assertEquals('test_comment', $metadata->fieldMappings['columnComment']['options']['comment']);

        self::assertArrayHasKey('columnDefault', $metadata->fieldMappings);
        self::assertEquals('test_default', $metadata->fieldMappings['columnDefault']['options']['default']);

        self::assertArrayHasKey('columnDecimal', $metadata->fieldMappings);
        self::assertEquals(4, $metadata->fieldMappings['columnDecimal']['precision']);
        self::assertEquals(3, $metadata->fieldMappings['columnDecimal']['scale']);

        self::assertNotEmpty($metadata->table['indexes']['index1']['columns']);
        self::assertEquals(
            ['column_index1', 'column_index2'],
            $metadata->table['indexes']['index1']['columns']
        );

        self::assertNotEmpty($metadata->table['uniqueConstraints']['unique_index1']['columns']);
        self::assertEquals(
            ['column_unique_index1', 'column_unique_index2'],
            $metadata->table['uniqueConstraints']['unique_index1']['columns']
        );
    }

    private static function supportsUnsignedInteger(AbstractPlatform $platform): bool
    {
        // FIXME: Condition here is fugly.
        // NOTE: PostgreSQL and SQL SERVER do not support UNSIGNED integer

        return ! $platform instanceof SQLServer2012Platform
            && ! $platform instanceof SQLServerPlatform
            && ! $platform instanceof PostgreSQL94Platform
            && ! $platform instanceof PostgreSQLPlatform;
    }
}

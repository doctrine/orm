<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

use function array_filter;
use function current;
use function method_exists;
use function sprintf;
use function str_contains;
use function str_starts_with;

/** @group GH7875 */
final class GH7875Test extends OrmFunctionalTestCase
{
    /** @after */
    public function cleanUpSchema(): void
    {
        $connection = $this->_em->getConnection();

        $connection->executeStatement('DROP TABLE IF EXISTS gh7875_my_entity');
        $connection->executeStatement('DROP TABLE IF EXISTS gh7875_my_other_entity');

        $platform = $connection->getDatabasePlatform();
        if ($platform instanceof PostgreSQLPlatform) {
            $connection->executeStatement('DROP SEQUENCE IF EXISTS gh7875_my_entity_id_seq');
            $connection->executeStatement('DROP SEQUENCE IF EXISTS gh7875_my_other_entity_id_seq');
        }
    }

    /**
     * @param string[] $sqls
     *
     * @return string[]
     */
    private function filterCreateTable(array $sqls, string $tableName): array
    {
        return array_filter($sqls, static function (string $sql) use ($tableName): bool {
            return str_starts_with($sql, sprintf('CREATE TABLE %s (', $tableName));
        });
    }

    public function testUpdateSchemaSql(): void
    {
        $classes = [GH7875MyEntity::class];

        $sqls = $this->filterCreateTable($this->getUpdateSchemaSqlForModels(...$classes), 'gh7875_my_entity');

        self::assertCount(1, $sqls);

        $this->_em->getConnection()->executeStatement(current($sqls));

        $sqls = array_filter($this->getUpdateSchemaSqlForModels(...$classes), static function (string $sql): bool {
            return str_contains($sql, ' gh7875_my_entity ');
        });

        self::assertSame([], $sqls);

        $classes[] = GH7875MyOtherEntity::class;

        $sqls = $this->getUpdateSchemaSqlForModels(...$classes);

        self::assertCount(0, $this->filterCreateTable($sqls, 'gh7875_my_entity'));
        self::assertCount(1, $this->filterCreateTable($sqls, 'gh7875_my_other_entity'));
    }

    /** @return array<array<string|callable|null>> */
    public function provideUpdateSchemaSqlWithSchemaAssetFilter(): array
    {
        return [
            ['/^(?!my_enti)/', null],
            [
                null,
                static function ($assetName): bool {
                    return $assetName !== 'gh7875_my_entity';
                },
            ],
        ];
    }

    /** @dataProvider provideUpdateSchemaSqlWithSchemaAssetFilter */
    public function testUpdateSchemaSqlWithSchemaAssetFilter(?string $filterRegex, ?callable $filterCallback): void
    {
        if ($filterRegex && ! method_exists(Configuration::class, 'setFilterSchemaAssetsExpression')) {
            self::markTestSkipped(sprintf('Test require %s::setFilterSchemaAssetsExpression method', Configuration::class));
        }

        $class = GH7875MyEntity::class;

        $this->createSchemaForModels($class);

        $config = $this->_em->getConnection()->getConfiguration();
        if ($filterRegex) {
            $config->setFilterSchemaAssetsExpression($filterRegex);
        } else {
            $config->setSchemaAssetsFilter($filterCallback);
        }

        $previousFilter = $config->getSchemaAssetsFilter();

        $sqls = $this->getUpdateSchemaSqlForModels($class);
        $sqls = array_filter($sqls, static function (string $sql): bool {
            return str_contains($sql, ' gh7875_my_entity ');
        });

        self::assertCount(0, $sqls);
        self::assertSame($previousFilter, $config->getSchemaAssetsFilter());
    }
}

/**
 * @Entity
 * @Table(name="gh7875_my_entity")
 */
class GH7875MyEntity
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
}

/**
 * @Entity
 * @Table(name="gh7875_my_other_entity")
 */
class GH7875MyOtherEntity
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;
}

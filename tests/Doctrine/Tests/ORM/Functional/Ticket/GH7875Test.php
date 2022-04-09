<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\OrmFunctionalTestCase;

use function array_filter;
use function current;
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
        $classes = [$this->_em->getClassMetadata(GH7875MyEntity::class)];

        $tool = new SchemaTool($this->_em);
        $sqls = $this->filterCreateTable($tool->getUpdateSchemaSql($classes), 'gh7875_my_entity');

        self::assertCount(1, $sqls);

        $this->_em->getConnection()->executeStatement(current($sqls));

        $sqls = array_filter($tool->getUpdateSchemaSql($classes), static function (string $sql): bool {
            return str_contains($sql, ' gh7875_my_entity ');
        });

        self::assertSame([], $sqls);

        $classes[] = $this->_em->getClassMetadata(GH7875MyOtherEntity::class);

        $sqls = $tool->getUpdateSchemaSql($classes);

        self::assertCount(0, $this->filterCreateTable($sqls, 'gh7875_my_entity'));
        self::assertCount(1, $this->filterCreateTable($sqls, 'gh7875_my_other_entity'));
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

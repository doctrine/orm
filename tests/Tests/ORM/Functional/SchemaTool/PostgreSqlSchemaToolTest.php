<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function array_filter;
use function array_slice;
use function implode;
use function str_starts_with;

class PostgreSqlSchemaToolTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $platform = $this->_em->getConnection()->getDatabasePlatform();
        if (! $platform instanceof PostgreSQLPlatform) {
            self::markTestSkipped('The ' . self::class . ' requires the use of postgresql.');
        }
    }

    #[Group('DDC-1657')]
    public function testUpdateSchemaWithPostgreSQLSchema(): void
    {
        $sql = $this->getUpdateSchemaSqlForModels(
            DDC1657Screen::class,
            DDC1657Avatar::class,
        );
        $sql = array_filter($sql, static fn ($sql) => str_starts_with($sql, 'DROP SEQUENCE stonewood.'));

        self::assertCount(0, $sql, implode("\n", $sql));
    }

    public function testUpdateSchemaWithJoinColumnWithOptions(): void
    {
        $sql = $this->getUpdateSchemaSqlForModels(
            TestEntityWithJoinColumnWithOptions::class,
            TestEntityWithJoinColumnWithOptionsRelation::class,
        );

        $this->assertSame([
            'CREATE TABLE test (id INT NOT NULL, testRelation1_id INT DEFAULT NULL, testRelation2_id INT DEFAULT NULL, PRIMARY KEY(id))',
            'CREATE UNIQUE INDEX UNIQ_D87F7E0C331521C6 ON test (testRelation1_id)',
            'CREATE UNIQUE INDEX UNIQ_D87F7E0C21A08E28 ON test (testRelation2_id)',
            'CREATE TABLE test_relation (id INT NOT NULL, PRIMARY KEY(id))',
            'ALTER TABLE test ADD CONSTRAINT FK_D87F7E0C331521C6 FOREIGN KEY (testRelation1_id) REFERENCES test_relation (id) DEFERRABLE INITIALLY DEFERRED',
            'ALTER TABLE test ADD CONSTRAINT FK_D87F7E0C21A08E28 FOREIGN KEY (testRelation2_id) REFERENCES test_relation (id) NOT DEFERRABLE INITIALLY IMMEDIATE',
        ], array_slice($sql, 0, 5));

        foreach ($sql as $query) {
            $this->_em->getConnection()->executeQuery($query);
        }

        $sql = $this->getUpdateSchemaSqlForModels(
            TestEntityWithJoinColumnWithOptions::class,
            TestEntityWithJoinColumnWithOptionsRelation::class,
        );

        $this->assertSame([], $sql);
    }
}

#[Table('test')]
#[Entity]
class TestEntityWithJoinColumnWithOptions
{
    #[Id]
    #[Column]
    private int $id;

    #[OneToOne(targetEntity: TestEntityWithJoinColumnWithOptionsRelation::class)]
    #[JoinColumn(options: ['deferrable' => true, 'deferred' => true])]
    private TestEntityWithJoinColumnWithOptionsRelation $testRelation1;

    #[OneToOne(targetEntity: TestEntityWithJoinColumnWithOptionsRelation::class)]
    #[JoinColumn]
    private TestEntityWithJoinColumnWithOptionsRelation $testRelation2;
}

#[Table('test_relation')]
#[Entity]
class TestEntityWithJoinColumnWithOptionsRelation
{
    #[Id]
    #[Column]
    private int $id;
}

#[Table(name: 'stonewood.screen')]
#[Entity]
class DDC1657Screen
{
    /**
     * Identifier
     */
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    #[Column(name: 'pk', type: 'integer', nullable: false)]
    private int $pk;

    /**
     * Title
     */
    #[Column(name: 'title', type: 'string', length: 255, nullable: false)]
    private string $title;

    /**
     * Path
     */
    #[Column(name: 'path', type: 'string', length: 255, nullable: false)]
    private string $path;

    /**
     * Register date
     *
     * @var Date
     */
    #[Column(name: 'ddate', type: 'date', nullable: false)]
    private $ddate;

    /**
     * Avatar
     *
     * @var Stonewood\Model\Entity\Avatar
     */
    #[ManyToOne(targetEntity: 'DDC1657Avatar')]
    #[JoinColumn(name: 'pk_avatar', referencedColumnName: 'pk', nullable: true, onDelete: 'CASCADE')]
    private $avatar;
}

#[Table(name: 'stonewood.avatar')]
#[Entity]
class DDC1657Avatar
{
    /**
     * Identifier
     */
    #[Id]
    #[GeneratedValue(strategy: 'IDENTITY')]
    #[Column(name: 'pk', type: 'integer', nullable: false)]
    private int $pk;
}

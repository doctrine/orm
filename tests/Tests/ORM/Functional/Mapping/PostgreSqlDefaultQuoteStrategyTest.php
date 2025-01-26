<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Mapping;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Driver\AttributeDriver;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Tests\OrmFunctionalTestCase;

use function method_exists;

class PostgreSqlDefaultQuoteStrategyTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $platform = $this->_em->getConnection()->getDatabasePlatform();
        if (! $platform instanceof PostgreSQLPlatform) {
            self::markTestSkipped(self::class . ' requires the use of postgresql.');
        }

        $this->_em         = $this->getEntityManager(
            null,
            new AttributeDriver([__DIR__], true)
        );
        $this->_schemaTool = new SchemaTool($this->_em);
    }

    public function testQuotingStillAllowsInterpretingDotAsSchemaSeparator(): void
    {
        $this->createSchemaForModels(Product::class);
        $schemaManager = $this->createSchemaManager();
        if (! method_exists($schemaManager, 'listSchemaNames')) {
            self::markTestSkipped('This test requires DBAL 3.1 or newer.');
        }

        self::assertContains(
            'inventory',
            $schemaManager->listSchemaNames(),
            '"inventory" should be interpreted as a schema name, even when quoted.'
        );
        self::assertContains(
            'inventory."Products"',
            $schemaManager->listTableNames(),
            '"Products" should be interpreted as a table name, even when quoted.'
        );
    }
}

#[ORM\Entity]
#[ORM\Table(name: '`inventory`.`Products`')] // Using backticks to make sure capitalization is preserved
class Product
{
    /** @var int|null */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public $id = null;
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Tests\Models\Company\CompanyManager;
use Doctrine\Tests\OrmFunctionalTestCase;

use function count;

/**
 * Functional tests for the Class Table Inheritance mapping strategy.
 */
class CompanySchemaTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('company');
        parent::setUp();
    }

    /**
     * @group DDC-966
     */
    public function testGeneratedSchema(): Schema
    {
        $schema = $this->_em->getConnection()->getSchemaManager()->createSchema();

        $this->assertTrue($schema->hasTable('company_contracts'));

        return $schema;
    }

    /**
     * @group DDC-966
     * @depends testGeneratedSchema
     */
    public function testSingleTableInheritance(Schema $schema): void
    {
        $table = $schema->getTable('company_contracts');

        // Check nullability constraints
        $this->assertTrue($table->getColumn('id')->getNotnull());
        $this->assertTrue($table->getColumn('completed')->getNotnull());
        $this->assertFalse($table->getColumn('salesPerson_id')->getNotnull());
        $this->assertTrue($table->getColumn('discr')->getNotnull());
        $this->assertFalse($table->getColumn('fixPrice')->getNotnull());
        $this->assertFalse($table->getColumn('hoursWorked')->getNotnull());
        $this->assertFalse($table->getColumn('pricePerHour')->getNotnull());
        $this->assertFalse($table->getColumn('maxPrice')->getNotnull());
    }

    /**
     * @group DBAL-115
     */
    public function testDropPartSchemaWithForeignKeys(): void
    {
        if (! $this->_em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            $this->markTestSkipped('Foreign Key test');
        }

        $sql = $this->_schemaTool->getDropSchemaSQL(
            [
                $this->_em->getClassMetadata(CompanyManager::class),
            ]
        );
        $this->assertEquals(4, count($sql));
    }
}

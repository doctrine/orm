<?php

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Tests\Models\Company\CompanyManager;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Functional tests for the Class Table Inheritance mapping strategy.
 *
 * @author robo
 */
class CompanySchemaTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('company');
        parent::setUp();
    }

    /**
     * @group DDC-966
     * @return Schema
     */
    public function testGeneratedSchema()
    {
        $schema = $this->em->getConnection()->getSchemaManager()->createSchema();

        self::assertTrue($schema->hasTable('company_contracts'));

        return $schema;
    }

    /**
     * @group DDC-966
     * @depends testGeneratedSchema
     */
    public function testSingleTableInheritance(Schema $schema)
    {
        $table = $schema->getTable('company_contracts');

        // Check nullability constraints
        self::assertTrue($table->getColumn('id')->getNotnull());
        self::assertTrue($table->getColumn('completed')->getNotnull());
        self::assertFalse($table->getColumn('salesPerson_id')->getNotnull());
        self::assertTrue($table->getColumn('discr')->getNotnull());
        self::assertFalse($table->getColumn('fixPrice')->getNotnull());
        self::assertFalse($table->getColumn('hoursWorked')->getNotnull());
        self::assertFalse($table->getColumn('pricePerHour')->getNotnull());
        self::assertFalse($table->getColumn('maxPrice')->getNotnull());
    }

    /**
     * @group DBAL-115
     */
    public function testDropPartSchemaWithForeignKeys()
    {
        if (!$this->em->getConnection()->getDatabasePlatform()->supportsForeignKeyConstraints()) {
            $this->markTestSkipped("Foreign Key test");
        }

        $sql = $this->schemaTool->getDropSchemaSQL(
            [
            $this->em->getClassMetadata(CompanyManager::class),
            ]
        );
        self::assertEquals(4, count($sql));
    }
}

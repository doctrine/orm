<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Tests\OrmFunctionalTestCase;

use function method_exists;

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

    /** @group DDC-966 */
    public function testGeneratedSchema(): Schema
    {
        $method = method_exists(AbstractSchemaManager::class, 'introspectSchema') ?
            'introspectSchema' :
            'createSchema';
        $schema = $this->createSchemaManager()->$method();

        self::assertTrue($schema->hasTable('company_contracts'));

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
        self::assertTrue($table->getColumn('id')->getNotnull());
        self::assertTrue($table->getColumn('completed')->getNotnull());
        self::assertFalse($table->getColumn('salesPerson_id')->getNotnull());
        self::assertTrue($table->getColumn('discr')->getNotnull());
        self::assertFalse($table->getColumn('fixPrice')->getNotnull());
        self::assertFalse($table->getColumn('hoursWorked')->getNotnull());
        self::assertFalse($table->getColumn('pricePerHour')->getNotnull());
        self::assertFalse($table->getColumn('maxPrice')->getNotnull());
    }
}

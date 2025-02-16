<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\SchemaTool;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;

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

    #[Group('DDC-966')]
    public function testGeneratedSchema(): Schema
    {
        $schema = $this->createSchemaManager()->introspectSchema();

        self::assertTrue($schema->hasTable('company_contracts'));

        return $schema;
    }

    #[Depends('testGeneratedSchema')]
    #[Group('DDC-966')]
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

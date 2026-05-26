<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Company\CompanyAuction;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\Models\Company\CompanyManager;
use Doctrine\Tests\OrmFunctionalTestCase;
use RuntimeException;

use function array_column;
use function array_filter;
use function array_values;
use function str_starts_with;
use function strtoupper;

/**
 * Functional tests for the order of the UPDATE sql statements during a flush() procedure.
 *
 * Covers: https://github.com/doctrine/orm/issues/11345
 */
class GH11345Test extends OrmFunctionalTestCase
{
    private int $employeeId;
    private int $managerId;
    private int $auction1Id;
    private int $auction2Id;
    private int $auction3Id;

    protected function setUp(): void
    {
        $this->useModelSet('company');

        parent::setUp();

        $this->generateFixture();
    }

    public function testUpdateSqlStatementsAreIssuedInAlphabeticOrderByEntityClass(): void
    {
        // Load entities in an order that is not alphabetical
        $manager  = $this->_em->find(CompanyManager::class, $this->managerId)
            ?? throw new RuntimeException('Could not find expected entity');
        $employee = $this->_em->find(CompanyEmployee::class, $this->employeeId)
            ?? throw new RuntimeException('Could not find expected entity');
        $auction  = $this->_em->find(CompanyAuction::class, $this->auction1Id)
            ?? throw new RuntimeException('Could not find expected entity');

        // Make some changes to the entities
        $manager->setTitle('new title');
        $employee->setSalary(100_000);
        $auction->setData('new data');

        // Reset the log so only the flush() queries are captured.
        $this->getQueryLog()->reset()->enable();

        $this->_em->flush();

        // Collect only UPDATE statements in execution order.
        $updateSqls = array_values(array_filter(
            array_column($this->getQueryLog()->queries, 'sql'),
            static fn (string $sql) => str_starts_with(strtoupper($sql), 'UPDATE'),
        ));

        self::assertCount(3, $updateSqls);

        // Assert that sqls were run in alphabetic order by the class names (which also results in the same order by the
        // table names)
        self::assertStringContainsString('company_auctions', $updateSqls[0]);
        self::assertStringContainsString('company_employees', $updateSqls[1]);
        self::assertStringContainsString('company_managers', $updateSqls[2]);
    }

    public function testUpdateSqlStatementsAreIssuedInAlphabeticOrderByEntityIdForSameClassName(): void
    {
        // Load entities in an order that is not alphabetical
        $auction2 = $this->_em->find(CompanyAuction::class, $this->auction2Id)
            ?? throw new RuntimeException('Could not find expected entity');
        $auction1 = $this->_em->find(CompanyAuction::class, $this->auction1Id)
            ?? throw new RuntimeException('Could not find expected entity');
        $auction3 = $this->_em->find(CompanyAuction::class, $this->auction3Id)
            ?? throw new RuntimeException('Could not find expected entity');

        // Make some changes to the entities
        $auction1->setData('new data');
        $auction2->setData('new data');
        $auction3->setData('new data');

        // Reset the log so only the flush() queries are captured.
        $this->getQueryLog()->reset()->enable();

        $this->_em->flush();

        // Collect only UPDATE statements in execution order.
        $updates = array_values(array_filter(
            $this->getQueryLog()->queries,
            static fn (array $q) => str_starts_with(strtoupper($q['sql']), 'UPDATE'),
        ));

        self::assertCount(3, $updates);

        // Assert that the params of each UPDATE contain the expected ID in ascending order.
        self::assertContains($this->auction1Id, $updates[0]['params']);
        self::assertContains($this->auction2Id, $updates[1]['params']);
        self::assertContains($this->auction3Id, $updates[2]['params']);
    }

    public function generateFixture(): void
    {
        $employee = new CompanyEmployee();
        $employee->setName('name');
        $employee->setDepartment('department');
        $employee->setSalary(50_000);

        $manager = new CompanyManager();
        $manager->setName('manager name');
        $manager->setDepartment('management');
        $manager->setSalary(80_000);
        $manager->setTitle('manager title');

        $auction1 = new CompanyAuction();
        $auction1->setData('lorem ipsum');

        $auction2 = new CompanyAuction();
        $auction2->setData('dolor sit');

        $auction3 = new CompanyAuction();
        $auction3->setData('dolor consectetur');

        $this->_em->persist($employee);
        $this->_em->persist($manager);
        $this->_em->persist($auction1);
        $this->_em->persist($auction2);
        $this->_em->persist($auction3);
        $this->_em->flush();

        $this->employeeId = $employee->getId();
        $this->managerId  = $manager->getId();
        $this->auction1Id = $auction1->getId();
        $this->auction2Id = $auction2->getId();
        $this->auction3Id = $auction3->getId();

        $this->_em->clear();
    }
}

<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * Functional tests for the Class Table Inheritance mapping strategy.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class DDC331Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('company');
        parent::setUp();
    }

    /**
     * @group DDC-331
     */
    public function testSelectFieldOnRootEntity()
    {
        $q = $this->em->createQuery('SELECT e.name FROM Doctrine\Tests\Models\Company\CompanyEmployee e');

        self::assertSQLEquals(
            'SELECT t0."name" AS c0 FROM "company_employees" t1 INNER JOIN "company_persons" t0 ON t1."id" = t0."id" LEFT JOIN "company_managers" t2 ON t1."id" = t2."id"',
            $q->getSQL()
        );
    }
}

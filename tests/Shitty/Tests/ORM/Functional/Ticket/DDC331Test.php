<?php

namespace Shitty\Tests\ORM\Functional\Ticket;

use Shitty\Tests\Models\Company\CompanyPerson,
    Shitty\Tests\Models\Company\CompanyEmployee,
    Shitty\Tests\Models\Company\CompanyManager,
    Shitty\Tests\Models\Company\CompanyOrganization,
    Shitty\Tests\Models\Company\CompanyEvent,
    Shitty\Tests\Models\Company\CompanyAuction,
    Shitty\Tests\Models\Company\CompanyRaffle,
    Shitty\Tests\Models\Company\CompanyCar;

/**
 * Functional tests for the Class Table Inheritance mapping strategy.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class DDC331Test extends \Shitty\Tests\OrmFunctionalTestCase
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
        $q = $this->_em->createQuery('SELECT e.name FROM Doctrine\Tests\Models\Company\CompanyEmployee e');
        $this->assertEquals(
            strtolower('SELECT c0_.name AS name_0 FROM company_employees c1_ INNER JOIN company_persons c0_ ON c1_.id = c0_.id LEFT JOIN company_managers c2_ ON c1_.id = c2_.id'),
            strtolower($q->getSql())
        );
    }
}

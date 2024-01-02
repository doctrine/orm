<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\Group;

use function strtolower;

/**
 * Functional tests for the Class Table Inheritance mapping strategy.
 */
class DDC331Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('company');

        parent::setUp();
    }

    #[Group('DDC-331')]
    public function testSelectFieldOnRootEntity(): void
    {
        $q = $this->_em->createQuery('SELECT e.name FROM Doctrine\Tests\Models\Company\CompanyEmployee e');
        self::assertEquals(
            strtolower('SELECT c0_.name AS name_0 FROM company_employees c1_ INNER JOIN company_persons c0_ ON c1_.id = c0_.id LEFT JOIN company_managers c2_ ON c1_.id = c2_.id'),
            strtolower($q->getSQL()),
        );
    }
}

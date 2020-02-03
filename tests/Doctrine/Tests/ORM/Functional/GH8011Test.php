<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Functional tests for ordering with arithmetic expression.
 *
 * @group GH8011
 */
class GH8011Test extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('company');
        parent::setUp();

        $this->generateFixture();
    }

    public function testOrderWithArithmeticExpression()
    {
        $dql = 'SELECT p, ' .
            '(SELECT SUM(p2.salary) FROM Doctrine\Tests\Models\Company\CompanyEmployee p2 WHERE p2.department = p.department) AS HIDDEN s ' .
            'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
            'ORDER BY s + s DESC';

        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($result));
    }

    public function generateFixture()
    {
        $person1 = new CompanyEmployee();
        $person1->setName('Benjamin E.');
        $person1->setDepartment('IT');
        $person1->setSalary(200000);

        $person2 = new CompanyEmployee();
        $person2->setName('Guilherme B.');
        $person2->setDepartment('IT2');
        $person2->setSalary(400000);

        $this->_em->persist($person1);
        $this->_em->persist($person2);
        $this->_em->flush();
        $this->_em->clear();
    }
}

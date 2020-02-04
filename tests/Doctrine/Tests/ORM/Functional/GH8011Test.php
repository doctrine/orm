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

    public function testOrderWithArithmeticExpressionWithSingleValuedPathExpression()
    {
        $dql = 'SELECT p ' .
            'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
            'ORDER BY p.id + p.id ASC';

        /** @var CompanyEmployee[] $result */
        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($result));
        $this->assertEquals('Benjamin E.', $result[0]->getName());
        $this->assertEquals('Guilherme B.', $result[1]->getName());
    }

    public function testOrderWithArithmeticExpressionWithLiteralAndSingleValuedPathExpression()
    {
        $dql = 'SELECT p ' .
            'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
            'ORDER BY 1 + p.id ASC';

        /** @var CompanyEmployee[] $result */
        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($result));
        $this->assertEquals('Benjamin E.', $result[0]->getName());
        $this->assertEquals('Guilherme B.', $result[1]->getName());
    }

    public function testOrderWithArithmeticExpressionWithSingleValuedPathExpressionAndLiteral()
    {
        $dql = 'SELECT p ' .
            'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
            'ORDER BY p.id + 1 ASC';

        /** @var CompanyEmployee[] $result */
        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($result));
        $this->assertEquals('Benjamin E.', $result[0]->getName());
        $this->assertEquals('Guilherme B.', $result[1]->getName());
    }

    public function testOrderWithArithmeticExpressionWithResultVariableAndLiteral()
    {
        $dql = 'SELECT p,  p.salary AS HIDDEN s ' .
            'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
            'ORDER BY s + 1 DESC';

        /** @var CompanyEmployee[] $result */
        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($result));
        $this->assertEquals('Guilherme B.', $result[0]->getName());
        $this->assertEquals('Benjamin E.', $result[1]->getName());
    }

    public function testOrderWithArithmeticExpressionWithLiteralAndResultVariable()
    {
        $dql = 'SELECT p,  p.salary AS HIDDEN s ' .
            'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
            'ORDER BY 1 + s DESC';

        /** @var CompanyEmployee[] $result */
        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($result));
        $this->assertEquals('Guilherme B.', $result[0]->getName());
        $this->assertEquals('Benjamin E.', $result[1]->getName());
    }

    public function testOrderWithArithmeticExpressionWithResultVariableAndSingleValuedPathExpression()
    {
        $dql = 'SELECT p,  p.salary AS HIDDEN s ' .
            'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
            'ORDER BY s + p.id DESC';

        /** @var CompanyEmployee[] $result */
        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($result));
        $this->assertEquals('Guilherme B.', $result[0]->getName());
        $this->assertEquals('Benjamin E.', $result[1]->getName());
    }

    public function testOrderWithArithmeticExpressionWithSingleValuedPathExpressionAndResultVariable()
    {
        $dql = 'SELECT p,  p.salary AS HIDDEN s ' .
            'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
            'ORDER BY p.id + s DESC';

        /** @var CompanyEmployee[] $result */
        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($result));
        $this->assertEquals('Guilherme B.', $result[0]->getName());
        $this->assertEquals('Benjamin E.', $result[1]->getName());
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

<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\OrmFunctionalTestCase;
use function count;

/**
 * Functional tests for ordering with arithmetic expression.
 *
 * @group GH8011
 */
class GH8011Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('company');

        parent::setUp();

        $this->generateFixture();
    }

    private function skipIfPostgres(string $test): void
    {
        $platform = $this->_em->getConnection()->getDatabasePlatform();
        if ($platform instanceof PostgreSQLPlatform) {
            self::markTestSkipped(
                'The ' . $test . ' test does not work on postgresql (see https://github.com/doctrine/orm/pull/8012).'
            );
        }
    }

    public function testOrderWithArithmeticExpressionWithSingleValuedPathExpression(): void
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

    public function testOrderWithArithmeticExpressionWithLiteralAndSingleValuedPathExpression(): void
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

    public function testOrderWithArithmeticExpressionWithLiteralAndSingleValuedPathExpression2(): void
    {
        $dql = 'SELECT p ' .
               'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
               'ORDER BY ((1 + p.id)) ASC';

        /** @var CompanyEmployee[] $result */
        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($result));
        $this->assertEquals('Benjamin E.', $result[0]->getName());
        $this->assertEquals('Guilherme B.', $result[1]->getName());
    }

    public function testOrderWithArithmeticExpressionWithSingleValuedPathExpressionAndLiteral(): void
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

    public function testOrderWithArithmeticExpressionWithResultVariableAndLiteral(): void
    {
        $this->skipIfPostgres(__FUNCTION__);

        $dql = 'SELECT p, p.salary AS HIDDEN s ' .
               'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
               'ORDER BY s + 1 DESC';

        /** @var CompanyEmployee[] $result */
        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($result));
        $this->assertEquals('Guilherme B.', $result[0]->getName());
        $this->assertEquals('Benjamin E.', $result[1]->getName());
    }

    public function testOrderWithArithmeticExpressionWithResultVariableAndLiteral2(): void
    {
        $this->skipIfPostgres(__FUNCTION__);

        $dql = 'SELECT p, p.salary AS HIDDEN s ' .
               'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
               'ORDER BY ((s + 1)) DESC';

        /** @var CompanyEmployee[] $result */
        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($result));
        $this->assertEquals('Guilherme B.', $result[0]->getName());
        $this->assertEquals('Benjamin E.', $result[1]->getName());
    }

    public function testOrderWithArithmeticExpressionWithLiteralAndResultVariable(): void
    {
        $this->skipIfPostgres(__FUNCTION__);

        $dql = 'SELECT p, p.salary AS HIDDEN s ' .
               'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
               'ORDER BY 1 + s DESC';

        /** @var CompanyEmployee[] $result */
        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($result));
        $this->assertEquals('Guilherme B.', $result[0]->getName());
        $this->assertEquals('Benjamin E.', $result[1]->getName());
    }

    public function testOrderWithArithmeticExpressionWithLiteralAndResultVariable2(): void
    {
        $this->skipIfPostgres(__FUNCTION__);

        $dql = 'SELECT p, p.salary AS HIDDEN s ' .
               'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
               'ORDER BY ((1 + s)) DESC';

        /** @var CompanyEmployee[] $result */
        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($result));
        $this->assertEquals('Guilherme B.', $result[0]->getName());
        $this->assertEquals('Benjamin E.', $result[1]->getName());
    }

    public function testOrderWithArithmeticExpressionWithResultVariableAndSingleValuedPathExpression(): void
    {
        $this->skipIfPostgres(__FUNCTION__);

        $dql = 'SELECT p, p.salary AS HIDDEN s ' .
               'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
               'ORDER BY s + p.id DESC';

        /** @var CompanyEmployee[] $result */
        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($result));
        $this->assertEquals('Guilherme B.', $result[0]->getName());
        $this->assertEquals('Benjamin E.', $result[1]->getName());
    }

    public function testOrderWithArithmeticExpressionWithResultVariableAndSingleValuedPathExpression2(): void
    {
        $this->skipIfPostgres(__FUNCTION__);

        $dql = 'SELECT p, p.salary AS HIDDEN s ' .
               'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
               'ORDER BY ((s + p.id)) DESC';

        /** @var CompanyEmployee[] $result */
        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($result));
        $this->assertEquals('Guilherme B.', $result[0]->getName());
        $this->assertEquals('Benjamin E.', $result[1]->getName());
    }

    public function testOrderWithArithmeticExpressionWithSingleValuedPathExpressionAndResultVariable(): void
    {
        $this->skipIfPostgres(__FUNCTION__);

        $dql = 'SELECT p, p.salary AS HIDDEN s ' .
               'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
               'ORDER BY p.id + s DESC';

        /** @var CompanyEmployee[] $result */
        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($result));
        $this->assertEquals('Guilherme B.', $result[0]->getName());
        $this->assertEquals('Benjamin E.', $result[1]->getName());
    }

    public function testOrderWithArithmeticExpressionWithLiteralAndResultVariableUsingHiddenResultVariable(): void
    {
        $dql = 'SELECT p, p.salary AS HIDDEN s, 1 + s AS HIDDEN _order ' .
               'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
               'ORDER BY _order DESC';

        /** @var CompanyEmployee[] $result */
        $result = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($result));
        $this->assertEquals('Guilherme B.', $result[0]->getName());
        $this->assertEquals('Benjamin E.', $result[1]->getName());
    }

    public function generateFixture(): void
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

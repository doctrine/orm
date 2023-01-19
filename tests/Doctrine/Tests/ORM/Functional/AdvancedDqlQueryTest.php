<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\IterableTester;
use Doctrine\Tests\Models\Company\CompanyCar;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\Models\Company\CompanyManager;
use Doctrine\Tests\OrmFunctionalTestCase;

use function count;

/**
 * Functional Query tests.
 */
class AdvancedDqlQueryTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('company');

        parent::setUp();

        $this->generateFixture();
    }

    public function testAggregateWithHavingClause(): void
    {
        $dql = 'SELECT p.department, AVG(p.salary) AS avgSalary ' .
               'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
               'GROUP BY p.department HAVING SUM(p.salary) > 200000 ORDER BY p.department';

        $result = $this->_em->createQuery($dql)->getScalarResult();

        self::assertEquals(2, count($result));
        self::assertEquals('IT', $result[0]['department']);
        self::assertEquals(150000, $result[0]['avgSalary']);
        self::assertEquals('IT2', $result[1]['department']);
        self::assertEquals(600000, $result[1]['avgSalary']);
    }

    public function testCommentsInDQL(): void
    {
        //same test than testAggregateWithHavingClause but with comments into the DQL
        $dql = "SELECT p.department, AVG(p.salary) AS avgSalary -- comment end of line
-- comment with 'strange chars', & $
  FROM Doctrine\\Tests\\Models\\Company\\CompanyEmployee p
  -- comment beginning of line GROUP BY
GROUP BY p.department HAVING SUM(p.salary) > 200000 ORDER BY p.department -- comment end of line";

        $result = $this->_em->createQuery($dql)->getScalarResult();

        self::assertCount(2, $result);
        self::assertEquals('IT', $result[0]['department']);
        self::assertEquals(150000, $result[0]['avgSalary']);
        self::assertEquals('IT2', $result[1]['department']);
        self::assertEquals(600000, $result[1]['avgSalary']);
    }

    public function testUnnamedScalarResultsAreOneBased(): void
    {
        $dql = 'SELECT p.department, AVG(p.salary) ' .
               'FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
               'GROUP BY p.department HAVING SUM(p.salary) > 200000 ORDER BY p.department';

        $result = $this->_em->createQuery($dql)->getScalarResult();

        self::assertCount(2, $result);
        self::assertEquals(150000, $result[0][1]);
        self::assertEquals(600000, $result[1][1]);
    }

    public function testOrderByResultVariableCollectionSize(): void
    {
        $dql = 'SELECT p.name, size(p.friends) AS friends ' .
               'FROM Doctrine\Tests\Models\Company\CompanyPerson p ' .
               'WHERE p.friends IS NOT EMPTY ' .
               'ORDER BY friends DESC, p.name DESC';

        $result = $this->_em->createQuery($dql)->getScalarResult();

        self::assertCount(4, $result);

        self::assertEquals('Jonathan W.', $result[0]['name']);
        self::assertEquals(3, $result[0]['friends']);

        self::assertEquals('Guilherme B.', $result[1]['name']);
        self::assertEquals(2, $result[1]['friends']);

        self::assertEquals('Benjamin E.', $result[2]['name']);
        self::assertEquals(2, $result[2]['friends']);

        self::assertEquals('Roman B.', $result[3]['name']);
        self::assertEquals(1, $result[3]['friends']);
    }

    public function testOrderBySimpleCaseExpression(): void
    {
        $dql = <<<'DQL'
            SELECT p.name
            FROM Doctrine\Tests\Models\Company\CompanyEmployee p
            ORDER BY CASE p.name
            WHEN 'Jonathan W.' THEN 1
            WHEN 'Roman B.' THEN 2
            WHEN 'Guilherme B.' THEN 3
            ELSE 4
            END DESC
DQL;

        $result = $this->_em->createQuery($dql)->getScalarResult();

        self::assertCount(4, $result);

        self::assertEquals('Benjamin E.', $result[0]['name']);
        self::assertEquals('Guilherme B.', $result[1]['name']);
        self::assertEquals('Roman B.', $result[2]['name']);
        self::assertEquals('Jonathan W.', $result[3]['name']);
    }

    public function testOrderByGeneralCaseExpression(): void
    {
        $dql = <<<'DQL'
            SELECT p.name
            FROM Doctrine\Tests\Models\Company\CompanyEmployee p
            ORDER BY CASE
            WHEN p.name='Jonathan W.' THEN 1
            WHEN p.name='Roman B.' THEN 2
            WHEN p.name='Guilherme B.' THEN 3
            ELSE 4
            END DESC
DQL;

        $result = $this->_em->createQuery($dql)->getScalarResult();

        self::assertCount(4, $result);

        self::assertEquals('Benjamin E.', $result[0]['name']);
        self::assertEquals('Guilherme B.', $result[1]['name']);
        self::assertEquals('Roman B.', $result[2]['name']);
        self::assertEquals('Jonathan W.', $result[3]['name']);
    }

    public function testIsNullAssociation(): void
    {
        $dql    = 'SELECT p FROM Doctrine\Tests\Models\Company\CompanyPerson p ' .
               'WHERE p.spouse IS NULL';
        $query  = $this->_em->createQuery($dql);
        $result = $query->getResult();

        self::assertCount(2, $result);
        self::assertGreaterThan(0, $result[0]->getId());
        self::assertNull($result[0]->getSpouse());

        self::assertGreaterThan(0, $result[1]->getId());
        self::assertNull($result[1]->getSpouse());

        $this->_em->clear();

        IterableTester::assertResultsAreTheSame($query);
    }

    public function testSelectSubselect(): void
    {
        $dql    = 'SELECT p, (SELECT c.brand FROM Doctrine\Tests\Models\Company\CompanyCar c WHERE p.car = c) brandName ' .
               'FROM Doctrine\Tests\Models\Company\CompanyManager p';
        $query  = $this->_em->createQuery($dql);
        $result = $query->getArrayResult();

        self::assertCount(1, $result);
        self::assertEquals('Caramba', $result[0]['brandName']);

        $this->_em->clear();
    }

    public function testInSubselect(): void
    {
        $dql    = <<<'DQL'
SELECT p.name FROM Doctrine\Tests\Models\Company\CompanyPerson p
WHERE p.name IN (SELECT n.name FROM Doctrine\Tests\Models\Company\CompanyPerson n WHERE n.name = 'Roman B.')
DQL;
        $query  = $this->_em->createQuery($dql);
        $result = $query->getScalarResult();

        self::assertCount(1, $result);
        self::assertEquals('Roman B.', $result[0]['name']);

        $this->_em->clear();

        IterableTester::assertResultsAreTheSame($query);
    }

    public function testGroupByMultipleFields(): void
    {
        $dql    = 'SELECT p.department, p.name, count(p.id) FROM Doctrine\Tests\Models\Company\CompanyEmployee p ' .
               'GROUP BY p.department, p.name';
        $query  = $this->_em->createQuery($dql);
        $result = $query->getResult();

        self::assertCount(4, $result);

        $this->_em->clear();

        IterableTester::assertResultsAreTheSame($query);
    }

    public function testUpdateAs(): void
    {
        $dql = 'UPDATE Doctrine\Tests\Models\Company\CompanyEmployee AS p SET p.salary = 1';
        $this->_em->createQuery($dql)->execute();

        $query = $this->_em->createQuery(
            'SELECT count(p.id) FROM Doctrine\Tests\Models\Company\CompanyEmployee p WHERE p.salary = 1'
        );
        self::assertGreaterThan(0, $query->getResult());

        $this->_em->clear();

        IterableTester::assertResultsAreTheSame($query);
    }

    public function testDeleteAs(): void
    {
        $dql = 'DELETE Doctrine\Tests\Models\Company\CompanyEmployee AS p';
        $this->_em->createQuery($dql)->getResult();

        $dql    = 'SELECT count(p) FROM Doctrine\Tests\Models\Company\CompanyEmployee p';
        $result = $this->_em->createQuery($dql)->getSingleScalarResult();

        self::assertEquals(0, $result);
    }

    public function generateFixture(): void
    {
        $car = new CompanyCar('Caramba');

        $manager1 = new CompanyManager();
        $manager1->setName('Roman B.');
        $manager1->setTitle('Foo');
        $manager1->setDepartment('IT');
        $manager1->setSalary(100000);
        $manager1->setCar($car);

        $person2 = new CompanyEmployee();
        $person2->setName('Benjamin E.');
        $person2->setDepartment('IT');
        $person2->setSalary(200000);

        $person3 = new CompanyEmployee();
        $person3->setName('Guilherme B.');
        $person3->setDepartment('IT2');
        $person3->setSalary(400000);

        $person4 = new CompanyEmployee();
        $person4->setName('Jonathan W.');
        $person4->setDepartment('IT2');
        $person4->setSalary(800000);

        $person2->setSpouse($person3);

        $manager1->addFriend($person4);
        $person2->addFriend($person3);
        $person2->addFriend($person4);
        $person3->addFriend($person4);

        $this->_em->persist($car);
        $this->_em->persist($manager1);
        $this->_em->persist($person2);
        $this->_em->persist($person3);
        $this->_em->persist($person4);
        $this->_em->flush();
        $this->_em->clear();
    }
}

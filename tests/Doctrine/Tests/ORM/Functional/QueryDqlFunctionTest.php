<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\Company\CompanyManager;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * Functional Query tests.
 *
 * @author robo
 */
class QueryDqlFunctionTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('company');
        parent::setUp();

        $this->generateFixture();
    }

    public function testAggregateSum()
    {
        $salarySum = $this->em->createQuery('SELECT SUM(m.salary) AS salary FROM Doctrine\Tests\Models\Company\CompanyManager m')
                               ->getSingleResult();

        self::assertEquals(1500000, $salarySum['salary']);
    }

    public function testAggregateAvg()
    {
        $salaryAvg = $this->em->createQuery('SELECT AVG(m.salary) AS salary FROM Doctrine\Tests\Models\Company\CompanyManager m')
                               ->getSingleResult();

        self::assertEquals(375000, round($salaryAvg['salary'], 0));
    }

    public function testAggregateMin()
    {
        $salary = $this->em->createQuery('SELECT MIN(m.salary) AS salary FROM Doctrine\Tests\Models\Company\CompanyManager m')
                               ->getSingleResult();

        self::assertEquals(100000, $salary['salary']);
    }

    public function testAggregateMax()
    {
        $salary = $this->em->createQuery('SELECT MAX(m.salary) AS salary FROM Doctrine\Tests\Models\Company\CompanyManager m')
                               ->getSingleResult();

        self::assertEquals(800000, $salary['salary']);
    }

    public function testAggregateCount()
    {
        $managerCount = $this->em->createQuery('SELECT COUNT(m.id) AS managers FROM Doctrine\Tests\Models\Company\CompanyManager m')
                               ->getSingleResult();

        self::assertEquals(4, $managerCount['managers']);
    }

    public function testFunctionAbs()
    {
        $result = $this->em->createQuery('SELECT m, ABS(m.salary * -1) AS abs FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC')
                         ->getResult();

        self::assertEquals(4, count($result));
        self::assertEquals(100000, $result[0]['abs']);
        self::assertEquals(200000, $result[1]['abs']);
        self::assertEquals(400000, $result[2]['abs']);
        self::assertEquals(800000, $result[3]['abs']);
    }

    public function testFunctionConcat()
    {
        $arg = $this->em->createQuery('SELECT m, CONCAT(m.name, m.department) AS namedep FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC')
                         ->getArrayResult();

        self::assertEquals(4, count($arg));
        self::assertEquals('Roman B.IT', $arg[0]['namedep']);
        self::assertEquals('Benjamin E.HR', $arg[1]['namedep']);
        self::assertEquals('Guilherme B.Complaint Department', $arg[2]['namedep']);
        self::assertEquals('Jonathan W.Administration', $arg[3]['namedep']);
    }

    public function testFunctionLength()
    {
        $result = $this->em->createQuery('SELECT m, LENGTH(CONCAT(m.name, m.department)) AS namedeplength FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC')
                         ->getArrayResult();

        self::assertEquals(4, count($result));
        self::assertEquals(10, $result[0]['namedeplength']);
        self::assertEquals(13, $result[1]['namedeplength']);
        self::assertEquals(32, $result[2]['namedeplength']);
        self::assertEquals(25, $result[3]['namedeplength']);
    }

    public function testFunctionLocate()
    {
        $dql = "SELECT m, LOCATE('e', LOWER(m.name)) AS loc, LOCATE('e', LOWER(m.name), 7) AS loc2 ".
               "FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC";

        $result = $this->em->createQuery($dql)
                         ->getArrayResult();

        self::assertEquals(4, count($result));
        self::assertEquals(0, $result[0]['loc']);
        self::assertEquals(2, $result[1]['loc']);
        self::assertEquals(6, $result[2]['loc']);
        self::assertEquals(0, $result[3]['loc']);
        self::assertEquals(0, $result[0]['loc2']);
        self::assertEquals(10, $result[1]['loc2']);
        self::assertEquals(9, $result[2]['loc2']);
        self::assertEquals(0, $result[3]['loc2']);
    }

    public function testFunctionLower()
    {
        $result = $this->em->createQuery("SELECT m, LOWER(m.name) AS lowername FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC")
                         ->getArrayResult();

        self::assertEquals(4, count($result));
        self::assertEquals('roman b.', $result[0]['lowername']);
        self::assertEquals('benjamin e.', $result[1]['lowername']);
        self::assertEquals('guilherme b.', $result[2]['lowername']);
        self::assertEquals('jonathan w.', $result[3]['lowername']);
    }

    public function testFunctionMod()
    {
        $result = $this->em->createQuery("SELECT m, MOD(m.salary, 3500) AS amod FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC")
                         ->getArrayResult();

        self::assertEquals(4, count($result));
        self::assertEquals(2000, $result[0]['amod']);
        self::assertEquals(500, $result[1]['amod']);
        self::assertEquals(1000, $result[2]['amod']);
        self::assertEquals(2000, $result[3]['amod']);
    }

    public function testFunctionSqrt()
    {
        $result = $this->em->createQuery("SELECT m, SQRT(m.salary) AS sqrtsalary FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC")
                         ->getArrayResult();

        self::assertEquals(4, count($result));
        self::assertEquals(316, round($result[0]['sqrtsalary']));
        self::assertEquals(447,  round($result[1]['sqrtsalary']));
        self::assertEquals(632, round($result[2]['sqrtsalary']));
        self::assertEquals(894, round($result[3]['sqrtsalary']));
    }

    public function testFunctionUpper()
    {
        $result = $this->em->createQuery("SELECT m, UPPER(m.name) AS uppername FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC")
                         ->getArrayResult();

        self::assertEquals(4, count($result));
        self::assertEquals('ROMAN B.', $result[0]['uppername']);
        self::assertEquals('BENJAMIN E.', $result[1]['uppername']);
        self::assertEquals('GUILHERME B.', $result[2]['uppername']);
        self::assertEquals('JONATHAN W.', $result[3]['uppername']);
    }

    public function testFunctionSubstring()
    {
        $dql = "SELECT m, SUBSTRING(m.name, 1, 3) AS str1, SUBSTRING(m.name, 5) AS str2 ".
                "FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.name";

        $result = $this->em->createQuery($dql)
                         ->getArrayResult();

        self::assertEquals(4, count($result));
        self::assertEquals('Ben', $result[0]['str1']);
        self::assertEquals('Gui', $result[1]['str1']);
        self::assertEquals('Jon', $result[2]['str1']);
        self::assertEquals('Rom', $result[3]['str1']);
        
        self::assertEquals('amin E.', $result[0]['str2']);
        self::assertEquals('herme B.', $result[1]['str2']);
        self::assertEquals('than W.', $result[2]['str2']);
        self::assertEquals('n B.', $result[3]['str2']);
    }

    public function testFunctionTrim()
    {
        $dql = "SELECT m, TRIM(TRAILING '.' FROM m.name) AS str1, ".
               " TRIM(LEADING '.' FROM m.name) AS str2, TRIM(CONCAT(' ', CONCAT(m.name, ' '))) AS str3 ".
               "FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC";

        $result = $this->em->createQuery($dql)->getArrayResult();

        self::assertEquals(4, count($result));
        self::assertEquals('Roman B', $result[0]['str1']);
        self::assertEquals('Benjamin E', $result[1]['str1']);
        self::assertEquals('Guilherme B', $result[2]['str1']);
        self::assertEquals('Jonathan W', $result[3]['str1']);
        self::assertEquals('Roman B.', $result[0]['str2']);
        self::assertEquals('Benjamin E.', $result[1]['str2']);
        self::assertEquals('Guilherme B.', $result[2]['str2']);
        self::assertEquals('Jonathan W.', $result[3]['str2']);
        self::assertEquals('Roman B.', $result[0]['str3']);
        self::assertEquals('Benjamin E.', $result[1]['str3']);
        self::assertEquals('Guilherme B.', $result[2]['str3']);
        self::assertEquals('Jonathan W.', $result[3]['str3']);
    }

    public function testOperatorAdd()
    {
        $result = $this->em->createQuery('SELECT m, m.salary+2500 AS add FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC')
                ->getResult();

        self::assertEquals(4, count($result));
        self::assertEquals(102500, $result[0]['add']);
        self::assertEquals(202500, $result[1]['add']);
        self::assertEquals(402500, $result[2]['add']);
        self::assertEquals(802500, $result[3]['add']);
    }

    public function testOperatorSub()
    {
        $result = $this->em->createQuery('SELECT m, m.salary-2500 AS sub FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC')
                ->getResult();

        self::assertEquals(4, count($result));
        self::assertEquals(97500, $result[0]['sub']);
        self::assertEquals(197500, $result[1]['sub']);
        self::assertEquals(397500, $result[2]['sub']);
        self::assertEquals(797500, $result[3]['sub']);
    }

    public function testOperatorMultiply()
    {
        $result = $this->em->createQuery('SELECT m, m.salary*2 AS op FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC')
                ->getResult();

        self::assertEquals(4, count($result));
        self::assertEquals(200000, $result[0]['op']);
        self::assertEquals(400000, $result[1]['op']);
        self::assertEquals(800000, $result[2]['op']);
        self::assertEquals(1600000, $result[3]['op']);
    }

    /**
     * @group test
     */
    public function testOperatorDiv()
    {
        $result = $this->em->createQuery('SELECT m, (m.salary/0.5) AS op FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC')
                ->getResult();

        self::assertEquals(4, count($result));
        self::assertEquals(200000, $result[0]['op']);
        self::assertEquals(400000, $result[1]['op']);
        self::assertEquals(800000, $result[2]['op']);
        self::assertEquals(1600000, $result[3]['op']);
    }

    public function testConcatFunction()
    {
        $arg = $this->em->createQuery('SELECT CONCAT(m.name, m.department) AS namedep FROM Doctrine\Tests\Models\Company\CompanyManager m order by namedep desc')
                ->getArrayResult();

        self::assertEquals(4, count($arg));
        self::assertEquals('Roman B.IT', $arg[0]['namedep']);
        self::assertEquals('Jonathan W.Administration', $arg[1]['namedep']);
        self::assertEquals('Guilherme B.Complaint Department', $arg[2]['namedep']);
        self::assertEquals('Benjamin E.HR', $arg[3]['namedep']);
    }

    /**
     * @group DDC-1014
     */
    public function testDateDiff()
    {
        $query = $this->em->createQuery("SELECT DATE_DIFF(CURRENT_TIMESTAMP(), DATE_ADD(CURRENT_TIMESTAMP(), 10, 'day')) AS diff FROM Doctrine\Tests\Models\Company\CompanyManager m");
        $arg = $query->getArrayResult();

        self::assertEquals(-10, $arg[0]['diff'], "Should be roughly -10 (or -9)", 1);

        $query = $this->em->createQuery("SELECT DATE_DIFF(DATE_ADD(CURRENT_TIMESTAMP(), 10, 'day'), CURRENT_TIMESTAMP()) AS diff FROM Doctrine\Tests\Models\Company\CompanyManager m");
        $arg = $query->getArrayResult();

        self::assertEquals(10, $arg[0]['diff'], "Should be roughly 10 (or 9)", 1);
    }

    /**
     * @group DDC-1014
     */
    public function testDateAdd()
    {
        $arg = $this->em->createQuery("SELECT DATE_ADD(CURRENT_TIMESTAMP(), 10, 'day') AS add FROM Doctrine\Tests\Models\Company\CompanyManager m")
                ->getArrayResult();

        self::assertTrue(strtotime($arg[0]['add']) > 0);

        $arg = $this->em->createQuery("SELECT DATE_ADD(CURRENT_TIMESTAMP(), 10, 'month') AS add FROM Doctrine\Tests\Models\Company\CompanyManager m")
                ->getArrayResult();

        self::assertTrue(strtotime($arg[0]['add']) > 0);
    }

    public function testDateAddSecond()
    {
        $dql     = "SELECT CURRENT_TIMESTAMP() now, DATE_ADD(CURRENT_TIMESTAMP(), 10, 'second') AS add FROM Doctrine\Tests\Models\Company\CompanyManager m";
        $query   = $this->em->createQuery($dql)->setMaxResults(1);
        $result  = $query->getArrayResult();

        self::assertCount(1, $result);
        self::assertArrayHasKey('now', $result[0]);
        self::assertArrayHasKey('add', $result[0]);

        $now  = strtotime($result[0]['now']);
        $add  = strtotime($result[0]['add']);
        $diff = $add - $now;

        self::assertSQLEquals(10, $diff);
    }

    /**
     * @group DDC-1014
     */
    public function testDateSub()
    {
        $arg = $this->em->createQuery("SELECT DATE_SUB(CURRENT_TIMESTAMP(), 10, 'day') AS add FROM Doctrine\Tests\Models\Company\CompanyManager m")
                ->getArrayResult();

        self::assertTrue(strtotime($arg[0]['add']) > 0);

        $arg = $this->em->createQuery("SELECT DATE_SUB(CURRENT_TIMESTAMP(), 10, 'month') AS add FROM Doctrine\Tests\Models\Company\CompanyManager m")
                ->getArrayResult();

        self::assertTrue(strtotime($arg[0]['add']) > 0);
    }

    /**
     * @group DDC-1213
     */
    public function testBitOrComparison()
    {
        $dql    = 'SELECT m, ' .
                    'BIT_OR(4, 2) AS bit_or,' .
                    'BIT_OR( (m.salary/100000) , 2 ) AS salary_bit_or ' .
                    'FROM Doctrine\Tests\Models\Company\CompanyManager m ' .
                'ORDER BY ' .
                    'm.id ' ;
        $result = $this->em->createQuery($dql)->getArrayResult();

        self::assertEquals(4 | 2, $result[0]['bit_or']);
        self::assertEquals(4 | 2, $result[1]['bit_or']);
        self::assertEquals(4 | 2, $result[2]['bit_or']);
        self::assertEquals(4 | 2, $result[3]['bit_or']);

        self::assertEquals(($result[0][0]['salary']/100000) | 2, $result[0]['salary_bit_or']);
        self::assertEquals(($result[1][0]['salary']/100000) | 2, $result[1]['salary_bit_or']);
        self::assertEquals(($result[2][0]['salary']/100000) | 2, $result[2]['salary_bit_or']);
        self::assertEquals(($result[3][0]['salary']/100000) | 2, $result[3]['salary_bit_or']);
    }

    /**
    * @group DDC-1213
    */
    public function testBitAndComparison()
    {
        $dql    = 'SELECT m, ' .
                    'BIT_AND(4, 2) AS bit_and,' .
                    'BIT_AND( (m.salary/100000) , 2 ) AS salary_bit_and ' .
                    'FROM Doctrine\Tests\Models\Company\CompanyManager m ' .
                'ORDER BY ' .
                    'm.id ' ;
        $result = $this->em->createQuery($dql)->getArrayResult();

        self::assertEquals(4 & 2, $result[0]['bit_and']);
        self::assertEquals(4 & 2, $result[1]['bit_and']);
        self::assertEquals(4 & 2, $result[2]['bit_and']);
        self::assertEquals(4 & 2, $result[3]['bit_and']);

        self::assertEquals(($result[0][0]['salary']/100000) & 2, $result[0]['salary_bit_and']);
        self::assertEquals(($result[1][0]['salary']/100000) & 2, $result[1]['salary_bit_and']);
        self::assertEquals(($result[2][0]['salary']/100000) & 2, $result[2]['salary_bit_and']);
        self::assertEquals(($result[3][0]['salary']/100000) & 2, $result[3]['salary_bit_and']);
    }

    protected function generateFixture()
    {
        $manager1 = new CompanyManager();
        $manager1->setName('Roman B.');
        $manager1->setTitle('Foo');
        $manager1->setDepartment('IT');
        $manager1->setSalary(100000);

        $manager2 = new CompanyManager();
        $manager2->setName('Benjamin E.');
        $manager2->setTitle('Foo');
        $manager2->setDepartment('HR');
        $manager2->setSalary(200000);

        $manager3 = new CompanyManager();
        $manager3->setName('Guilherme B.');
        $manager3->setTitle('Foo');
        $manager3->setDepartment('Complaint Department');
        $manager3->setSalary(400000);

        $manager4 = new CompanyManager();
        $manager4->setName('Jonathan W.');
        $manager4->setTitle('Foo');
        $manager4->setDepartment('Administration');
        $manager4->setSalary(800000);

        $this->em->persist($manager1);
        $this->em->persist($manager2);
        $this->em->persist($manager3);
        $this->em->persist($manager4);
        $this->em->flush();
        $this->em->clear();
    }
}

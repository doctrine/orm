<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use DateTimeImmutable;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\ORM\AbstractQuery;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\Models\Company\CompanyManager;
use Doctrine\Tests\OrmFunctionalTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use function round;
use function sprintf;

/**
 * Functional Query tests.
 */
class QueryDqlFunctionTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('company');

        parent::setUp();

        $this->generateFixture();
    }

    public function testAggregateSum(): void
    {
        $salarySum = $this->_em->createQuery('SELECT SUM(m.salary) AS salary FROM Doctrine\Tests\Models\Company\CompanyManager m')
                               ->getSingleResult();

        self::assertEquals(1_500_000, $salarySum['salary']);
    }

    public function testAggregateAvg(): void
    {
        $salaryAvg = $this->_em->createQuery('SELECT AVG(m.salary) AS salary FROM Doctrine\Tests\Models\Company\CompanyManager m')
                               ->getSingleResult();

        self::assertEquals(375000, round((float) $salaryAvg['salary'], 0));
    }

    public function testAggregateMin(): void
    {
        $salary = $this->_em->createQuery('SELECT MIN(m.salary) AS salary FROM Doctrine\Tests\Models\Company\CompanyManager m')
                               ->getSingleResult();

        self::assertEquals(100000, $salary['salary']);
    }

    public function testAggregateMax(): void
    {
        $salary = $this->_em->createQuery('SELECT MAX(m.salary) AS salary FROM Doctrine\Tests\Models\Company\CompanyManager m')
                               ->getSingleResult();

        self::assertEquals(800000, $salary['salary']);
    }

    public function testAggregateCount(): void
    {
        $managerCount = $this->_em->createQuery('SELECT COUNT(m.id) AS managers FROM Doctrine\Tests\Models\Company\CompanyManager m')
                               ->getSingleResult();

        self::assertEquals(4, $managerCount['managers']);
    }

    public function testFunctionAbs(): void
    {
        $result = $this->_em->createQuery('SELECT m, ABS(m.salary * -1) AS abs FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC')
                         ->getResult();

        self::assertCount(4, $result);
        self::assertEquals(100000, $result[0]['abs']);
        self::assertEquals(200000, $result[1]['abs']);
        self::assertEquals(400000, $result[2]['abs']);
        self::assertEquals(800000, $result[3]['abs']);
    }

    public function testFunctionConcat(): void
    {
        $arg = $this->_em->createQuery('SELECT m, CONCAT(m.name, m.department) AS namedep FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC')
                         ->getArrayResult();

        self::assertCount(4, $arg);
        self::assertEquals('Roman B.IT', $arg[0]['namedep']);
        self::assertEquals('Benjamin E.HR', $arg[1]['namedep']);
        self::assertEquals('Guilherme B.Complaint Department', $arg[2]['namedep']);
        self::assertEquals('Jonathan W.Administration', $arg[3]['namedep']);
    }

    public function testFunctionLength(): void
    {
        $result = $this->_em->createQuery('SELECT m, LENGTH(CONCAT(m.name, m.department)) AS namedeplength FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC')
                         ->getArrayResult();

        self::assertCount(4, $result);
        self::assertEquals(10, $result[0]['namedeplength']);
        self::assertEquals(13, $result[1]['namedeplength']);
        self::assertEquals(32, $result[2]['namedeplength']);
        self::assertEquals(25, $result[3]['namedeplength']);
    }

    public function testFunctionLocate(): void
    {
        $dql = "SELECT m, LOCATE('e', LOWER(m.name)) AS loc, LOCATE('e', LOWER(m.name), 7) AS loc2 " .
               'FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC';

        $result = $this->_em->createQuery($dql)
                         ->getArrayResult();

        self::assertCount(4, $result);
        self::assertEquals(0, $result[0]['loc']);
        self::assertEquals(2, $result[1]['loc']);
        self::assertEquals(6, $result[2]['loc']);
        self::assertEquals(0, $result[3]['loc']);
        self::assertEquals(0, $result[0]['loc2']);
        self::assertEquals(10, $result[1]['loc2']);
        self::assertEquals(9, $result[2]['loc2']);
        self::assertEquals(0, $result[3]['loc2']);
    }

    public function testFunctionLower(): void
    {
        $result = $this->_em->createQuery('SELECT m, LOWER(m.name) AS lowername FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC')
                         ->getArrayResult();

        self::assertCount(4, $result);
        self::assertEquals('roman b.', $result[0]['lowername']);
        self::assertEquals('benjamin e.', $result[1]['lowername']);
        self::assertEquals('guilherme b.', $result[2]['lowername']);
        self::assertEquals('jonathan w.', $result[3]['lowername']);
    }

    public function testFunctionMod(): void
    {
        $result = $this->_em->createQuery('SELECT m, MOD(m.salary, 3500) AS amod FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC')
                         ->getArrayResult();

        self::assertCount(4, $result);
        self::assertEquals(2000, $result[0]['amod']);
        self::assertEquals(500, $result[1]['amod']);
        self::assertEquals(1000, $result[2]['amod']);
        self::assertEquals(2000, $result[3]['amod']);
    }

    public function testFunctionSqrt(): void
    {
        $result = $this->_em->createQuery('SELECT m, SQRT(m.salary) AS sqrtsalary FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC')
                         ->getArrayResult();

        self::assertCount(4, $result);
        self::assertEquals(316, round((float) $result[0]['sqrtsalary']));
        self::assertEquals(447, round((float) $result[1]['sqrtsalary']));
        self::assertEquals(632, round((float) $result[2]['sqrtsalary']));
        self::assertEquals(894, round((float) $result[3]['sqrtsalary']));
    }

    public function testFunctionUpper(): void
    {
        $result = $this->_em->createQuery('SELECT m, UPPER(m.name) AS uppername FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC')
                         ->getArrayResult();

        self::assertCount(4, $result);
        self::assertEquals('ROMAN B.', $result[0]['uppername']);
        self::assertEquals('BENJAMIN E.', $result[1]['uppername']);
        self::assertEquals('GUILHERME B.', $result[2]['uppername']);
        self::assertEquals('JONATHAN W.', $result[3]['uppername']);
    }

    public function testFunctionSubstring(): void
    {
        $dql = 'SELECT m, SUBSTRING(m.name, 1, 3) AS str1, SUBSTRING(m.name, 5) AS str2 ' .
                'FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.name';

        $result = $this->_em->createQuery($dql)
                         ->getArrayResult();

        self::assertCount(4, $result);
        self::assertEquals('Ben', $result[0]['str1']);
        self::assertEquals('Gui', $result[1]['str1']);
        self::assertEquals('Jon', $result[2]['str1']);
        self::assertEquals('Rom', $result[3]['str1']);

        self::assertEquals('amin E.', $result[0]['str2']);
        self::assertEquals('herme B.', $result[1]['str2']);
        self::assertEquals('than W.', $result[2]['str2']);
        self::assertEquals('n B.', $result[3]['str2']);
    }

    public function testFunctionTrim(): void
    {
        $dql = "SELECT m, TRIM(TRAILING '.' FROM m.name) AS str1, " .
               " TRIM(LEADING '.' FROM m.name) AS str2, TRIM(CONCAT(' ', CONCAT(m.name, ' '))) AS str3 " .
               'FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC';

        $result = $this->_em->createQuery($dql)->getArrayResult();

        self::assertCount(4, $result);
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

    public function testOperatorAdd(): void
    {
        $result = $this->_em->createQuery('SELECT m, m.salary+2500 AS add FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC')
                ->getResult();

        self::assertCount(4, $result);
        self::assertEquals(102500, $result[0]['add']);
        self::assertEquals(202500, $result[1]['add']);
        self::assertEquals(402500, $result[2]['add']);
        self::assertEquals(802500, $result[3]['add']);
    }

    public function testOperatorSub(): void
    {
        $result = $this->_em->createQuery('SELECT m, m.salary-2500 AS sub FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC')
                ->getResult();

        self::assertCount(4, $result);
        self::assertEquals(97500, $result[0]['sub']);
        self::assertEquals(197500, $result[1]['sub']);
        self::assertEquals(397500, $result[2]['sub']);
        self::assertEquals(797500, $result[3]['sub']);
    }

    public function testOperatorMultiply(): void
    {
        $result = $this->_em->createQuery('SELECT m, m.salary*2 AS op FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC')
                ->getResult();

        self::assertCount(4, $result);
        self::assertEquals(200000, $result[0]['op']);
        self::assertEquals(400000, $result[1]['op']);
        self::assertEquals(800000, $result[2]['op']);
        self::assertEquals(1_600_000, $result[3]['op']);
    }

    public function testOperatorDiv(): void
    {
        $result = $this->_em->createQuery('SELECT m, (m.salary/0.5) AS op FROM Doctrine\Tests\Models\Company\CompanyManager m ORDER BY m.salary ASC')
                ->getResult();

        self::assertCount(4, $result);
        self::assertEquals(200000, $result[0]['op']);
        self::assertEquals(400000, $result[1]['op']);
        self::assertEquals(800000, $result[2]['op']);
        self::assertEquals(1_600_000, $result[3]['op']);
    }

    public function testConcatFunction(): void
    {
        $arg = $this->_em->createQuery('SELECT CONCAT(m.name, m.department) AS namedep FROM Doctrine\Tests\Models\Company\CompanyManager m order by namedep desc')
                ->getArrayResult();

        self::assertCount(4, $arg);
        self::assertEquals('Roman B.IT', $arg[0]['namedep']);
        self::assertEquals('Jonathan W.Administration', $arg[1]['namedep']);
        self::assertEquals('Guilherme B.Complaint Department', $arg[2]['namedep']);
        self::assertEquals('Benjamin E.HR', $arg[3]['namedep']);
    }

    #[Group('DDC-1014')]
    public function testDateDiff(): void
    {
        $query = $this->_em->createQuery("SELECT DATE_DIFF(CURRENT_TIMESTAMP(), DATE_ADD(CURRENT_TIMESTAMP(), 10, 'day')) AS diff FROM Doctrine\Tests\Models\Company\CompanyManager m");
        $arg   = $query->getArrayResult();

        self::assertEqualsWithDelta(-10, $arg[0]['diff'], 1, 'Should be roughly -10 (or -9)');

        $query = $this->_em->createQuery("SELECT DATE_DIFF(DATE_ADD(CURRENT_TIMESTAMP(), 10, 'day'), CURRENT_TIMESTAMP()) AS diff FROM Doctrine\Tests\Models\Company\CompanyManager m");
        $arg   = $query->getArrayResult();

        self::assertEqualsWithDelta(10, $arg[0]['diff'], 1, 'Should be roughly 10 (or 9)');
    }

    #[DataProvider('dateAddSubProvider')]
    #[Group('DDC-1014')]
    #[Group('DDC-2938')]
    public function testDateAdd(string $unit, int $amount, int $delta = 0): void
    {
        $query = sprintf(
            'SELECT CURRENT_TIMESTAMP() as now, DATE_ADD(CURRENT_TIMESTAMP(), %d, \'%s\') AS add FROM %s m',
            $amount,
            $unit,
            CompanyManager::class,
        );

        $result = $this->_em->createQuery($query)
                            ->setMaxResults(1)
                            ->getSingleResult(AbstractQuery::HYDRATE_ARRAY);

        self::assertArrayHasKey('now', $result);
        self::assertArrayHasKey('add', $result);

        $now       = new DateTimeImmutable($result['now']);
        $inOneUnit = $now->modify(sprintf('+%d %s', $amount, $unit));
        if (
            $unit === 'month'
            && $inOneUnit->format('m') === $now->modify('+2 month')->format('m')
            && ! $this->_em->getConnection()->getDatabasePlatform() instanceof SQLitePlatform
        ) {
            $inOneUnit = new DateTimeImmutable('last day of next month');
        }

        self::assertEqualsWithDelta(
            $inOneUnit,
            new DateTimeImmutable($result['add']),
            $delta,
        );
    }

    #[DataProvider('dateAddSubProvider')]
    #[Group('DDC-1014')]
    #[Group('DDC-2938')]
    public function testDateSub(string $unit, int $amount, int $delta = 0): void
    {
        $query = sprintf(
            'SELECT CURRENT_TIMESTAMP() as now, DATE_SUB(CURRENT_TIMESTAMP(), %d, \'%s\') AS sub FROM %s m',
            $amount,
            $unit,
            CompanyManager::class,
        );

        $result = $this->_em->createQuery($query)
                            ->setMaxResults(1)
                            ->getSingleResult(AbstractQuery::HYDRATE_ARRAY);

        self::assertArrayHasKey('now', $result);
        self::assertArrayHasKey('sub', $result);

        $now        = new DateTimeImmutable($result['now']);
        $oneUnitAgo = $now->modify(sprintf('-%d %s', $amount, $unit));
        if (
            $unit === 'month'
            && $oneUnitAgo->format('m') === $now->format('m')
            && ! $this->_em->getConnection()->getDatabasePlatform() instanceof SQLitePlatform
        ) {
            $oneUnitAgo = new DateTimeImmutable('last day of previous month');
        }

        self::assertEqualsWithDelta(
            $oneUnitAgo,
            new DateTimeImmutable($result['sub']),
            $delta,
        );
    }

    public static function dateAddSubProvider(): array
    {
        $secondsInDay = 86400;

        return [
            'year'   => ['year', 1, $secondsInDay],
            'month'  => ['month', 1, $secondsInDay],
            'week'   => ['week', 1, $secondsInDay],
            'day'    => ['day', 2, $secondsInDay],
            'hour'   => ['hour', 1, 3600],
            'minute' => ['minute', 1, 60],
            'second' => ['second', 10, 10],
        ];
    }

    #[Group('DDC-1213')]
    public function testBitOrComparison(): void
    {
        $dql    = 'SELECT m, ' .
                    'BIT_OR(4, 2) AS bit_or,' .
                    'BIT_OR( (m.salary/100000) , 2 ) AS salary_bit_or ' .
                    'FROM Doctrine\Tests\Models\Company\CompanyManager m ' .
                'ORDER BY ' .
                    'm.id ';
        $result = $this->_em->createQuery($dql)->getArrayResult();

        self::assertEquals(4 | 2, $result[0]['bit_or']);
        self::assertEquals(4 | 2, $result[1]['bit_or']);
        self::assertEquals(4 | 2, $result[2]['bit_or']);
        self::assertEquals(4 | 2, $result[3]['bit_or']);

        self::assertEquals($result[0][0]['salary'] / 100000 | 2, $result[0]['salary_bit_or']);
        self::assertEquals($result[1][0]['salary'] / 100000 | 2, $result[1]['salary_bit_or']);
        self::assertEquals($result[2][0]['salary'] / 100000 | 2, $result[2]['salary_bit_or']);
        self::assertEquals($result[3][0]['salary'] / 100000 | 2, $result[3]['salary_bit_or']);
    }

    #[Group('DDC-1213')]
    public function testBitAndComparison(): void
    {
        $dql    = 'SELECT m, ' .
                    'BIT_AND(4, 2) AS bit_and,' .
                    'BIT_AND( (m.salary/100000) , 2 ) AS salary_bit_and ' .
                    'FROM Doctrine\Tests\Models\Company\CompanyManager m ' .
                'ORDER BY ' .
                    'm.id ';
        $result = $this->_em->createQuery($dql)->getArrayResult();

        self::assertEquals(4 & 2, $result[0]['bit_and']);
        self::assertEquals(4 & 2, $result[1]['bit_and']);
        self::assertEquals(4 & 2, $result[2]['bit_and']);
        self::assertEquals(4 & 2, $result[3]['bit_and']);

        self::assertEquals($result[0][0]['salary'] / 100000 & 2, $result[0]['salary_bit_and']);
        self::assertEquals($result[1][0]['salary'] / 100000 & 2, $result[1]['salary_bit_and']);
        self::assertEquals($result[2][0]['salary'] / 100000 & 2, $result[2]['salary_bit_and']);
        self::assertEquals($result[3][0]['salary'] / 100000 & 2, $result[3]['salary_bit_and']);
    }

    public function testInArithmeticExpression1(): void
    {
        $dql = <<<'SQL'
            SELECT m, m.name AS m_name
            FROM Doctrine\Tests\Models\Company\CompanyManager m
            WHERE m.salary IN (800000 / 8, 100000 * 2)
            ORDER BY m.name DESC
SQL;

        $result = $this->_em->createQuery($dql)->getArrayResult();

        self::assertCount(2, $result);
        self::assertEquals('Roman B.', $result[0]['m_name']);
        self::assertEquals('Benjamin E.', $result[1]['m_name']);
    }

    public function testInArithmeticExpression2(): void
    {
        $this->_em->getConfiguration()->addCustomStringFunction('FOO', static function ($funcName) {
            return new NoOp($funcName); // See Doctrine/Tests/ORM/Functional/CustomFunctionsTest
        });

        $dql = <<<'SQL'
            SELECT m, m.name AS m_name
            FROM Doctrine\Tests\Models\Company\CompanyManager m
            WHERE m.department IN (FOO('Administration'))
SQL;

        $result = $this->_em->createQuery($dql)->getArrayResult();

        self::assertCount(1, $result);
        self::assertEquals('Jonathan W.', $result[0]['m_name']);
    }

    protected function generateFixture(): void
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

        $this->_em->persist($manager1);
        $this->_em->persist($manager2);
        $this->_em->persist($manager3);
        $this->_em->persist($manager4);
        $this->_em->flush();
        $this->_em->clear();
    }

    #[Group('GH-11240')]
    public function testDateAddWithColumnInterval(): void
    {
        $query = sprintf(
            'SELECT DATE_ADD(CURRENT_TIMESTAMP(), m.salary, \'day\') AS add FROM %s m',
            CompanyEmployee::class,
        );

        $result = $this->_em->createQuery($query)
            ->setMaxResults(1)
            ->getSingleResult(AbstractQuery::HYDRATE_ARRAY);

        self::assertArrayHasKey('add', $result);
    }

    #[Group('GH-11240')]
    public function testDateSubWithColumnInterval(): void
    {
        $query = sprintf(
            'SELECT DATE_SUB(CURRENT_TIMESTAMP(), m.salary, \'day\') AS add FROM %s m',
            CompanyEmployee::class,
        );

        $result = $this->_em->createQuery($query)
            ->setMaxResults(1)
            ->getSingleResult(AbstractQuery::HYDRATE_ARRAY);

        self::assertArrayHasKey('add', $result);
    }
}

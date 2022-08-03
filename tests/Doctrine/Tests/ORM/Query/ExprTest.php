<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Expr;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\OrmTestCase;
use Generator;

/**
 * Test case for the DQL Expr class used for generating DQL snippets through
 * a programmatic interface
 *
 * @link        http://www.phpdoctrine.org
 */
class ExprTest extends OrmTestCase
{
    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var Expr */
    private $expr;

    protected function setUp(): void
    {
        $this->entityManager = $this->getTestEntityManager();
        $this->expr          = new Expr();
    }

    public function testAvgExpr(): void
    {
        $this->assertEquals('AVG(u.id)', (string) $this->expr->avg('u.id'));
    }

    public function testMaxExpr(): void
    {
        $this->assertEquals('MAX(u.id)', (string) $this->expr->max('u.id'));
    }

    public function testMinExpr(): void
    {
        $this->assertEquals('MIN(u.id)', (string) $this->expr->min('u.id'));
    }

    public function testCountExpr(): void
    {
        $this->assertEquals('MAX(u.id)', (string) $this->expr->max('u.id'));
    }

    public function testCountDistinctExpr(): void
    {
        $this->assertEquals('COUNT(DISTINCT u.id)', (string) $this->expr->countDistinct('u.id'));
    }

    public function testCountDistinctExprMulti(): void
    {
        $this->assertEquals('COUNT(DISTINCT u.id, u.name)', (string) $this->expr->countDistinct('u.id', 'u.name'));
    }

    public function testExistsExpr(): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u')->from('User', 'u')->where('u.name = ?1');

        $this->assertEquals('EXISTS(SELECT u FROM User u WHERE u.name = ?1)', (string) $this->expr->exists($qb));
    }

    public function testAllExpr(): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u')->from('User', 'u')->where('u.name = ?1');

        $this->assertEquals('ALL(SELECT u FROM User u WHERE u.name = ?1)', (string) $this->expr->all($qb));
    }

    public function testSomeExpr(): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u')->from('User', 'u')->where('u.name = ?1');

        $this->assertEquals('SOME(SELECT u FROM User u WHERE u.name = ?1)', (string) $this->expr->some($qb));
    }

    public function testAnyExpr(): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u')->from('User', 'u')->where('u.name = ?1');

        $this->assertEquals('ANY(SELECT u FROM User u WHERE u.name = ?1)', (string) $this->expr->any($qb));
    }

    public function testNotExpr(): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u')->from('User', 'u')->where('u.name = ?1');

        $this->assertEquals('NOT(SELECT u FROM User u WHERE u.name = ?1)', (string) $this->expr->not($qb));
    }

    public function testAndExpr(): void
    {
        $this->assertEquals('1 = 1 AND 2 = 2', (string) $this->expr->andX((string) $this->expr->eq(1, 1), (string) $this->expr->eq(2, 2)));
    }

    public function testIntelligentParenthesisPreventionAndExpr(): void
    {
        $this->assertEquals(
            '1 = 1 AND 2 = 2',
            (string) $this->expr->andX($this->expr->orX($this->expr->andX($this->expr->eq(1, 1))), (string) $this->expr->eq(2, 2))
        );
    }

    public function testOrExpr(): void
    {
        $this->assertEquals('1 = 1 OR 2 = 2', (string) $this->expr->orX((string) $this->expr->eq(1, 1), (string) $this->expr->eq(2, 2)));
    }

    public function testAbsExpr(): void
    {
        $this->assertEquals('ABS(1)', (string) $this->expr->abs(1));
    }

    public function testProdExpr(): void
    {
        $this->assertEquals('1 * 2', (string) $this->expr->prod(1, 2));
    }

    public function testDiffExpr(): void
    {
        $this->assertEquals('1 - 2', (string) $this->expr->diff(1, 2));
    }

    public function testSumExpr(): void
    {
        $this->assertEquals('1 + 2', (string) $this->expr->sum(1, 2));
    }

    public function testQuotientExpr(): void
    {
        $this->assertEquals('10 / 2', (string) $this->expr->quot(10, 2));
    }

    public function testScopeInArithmeticExpr(): void
    {
        $this->assertEquals('(100 - 20) / 2', (string) $this->expr->quot($this->expr->diff(100, 20), 2));
        $this->assertEquals('100 - (20 / 2)', (string) $this->expr->diff(100, $this->expr->quot(20, 2)));
    }

    public function testSquareRootExpr(): void
    {
        $this->assertEquals('SQRT(1)', (string) $this->expr->sqrt(1));
    }

    public function testEqualExpr(): void
    {
        $this->assertEquals('1 = 1', (string) $this->expr->eq(1, 1));
    }

    public function testLikeExpr(): void
    {
        $this->assertEquals('a.description LIKE :description', (string) $this->expr->like('a.description', ':description'));
    }

    public function testNotLikeExpr(): void
    {
        $this->assertEquals('a.description NOT LIKE :description', (string) $this->expr->notLike('a.description', ':description'));
    }

    public function testConcatExpr(): void
    {
        $this->assertEquals('CONCAT(u.first_name, u.last_name)', (string) $this->expr->concat('u.first_name', 'u.last_name'));
        $this->assertEquals('CONCAT(u.first_name, u.middle_name, u.last_name)', (string) $this->expr->concat('u.first_name', 'u.middle_name', 'u.last_name'));
    }

    public function testSubstringExpr(): void
    {
        $this->assertEquals('SUBSTRING(a.title, 0, 25)', (string) $this->expr->substring('a.title', 0, 25));
    }

    /**
     * @group regression
     * @group DDC-612
     */
    public function testSubstringExprAcceptsTwoArguments(): void
    {
        $this->assertEquals('SUBSTRING(a.title, 5)', (string) $this->expr->substring('a.title', 5));
    }

    public function testLowerExpr(): void
    {
        $this->assertEquals('LOWER(u.first_name)', (string) $this->expr->lower('u.first_name'));
    }

    public function testUpperExpr(): void
    {
        $this->assertEquals('UPPER(u.first_name)', (string) $this->expr->upper('u.first_name'));
    }

    public function testLengthExpr(): void
    {
        $this->assertEquals('LENGTH(u.first_name)', (string) $this->expr->length('u.first_name'));
    }

    public function testGreaterThanExpr(): void
    {
        $this->assertEquals('5 > 2', (string) $this->expr->gt(5, 2));
    }

    public function testLessThanExpr(): void
    {
        $this->assertEquals('2 < 5', (string) $this->expr->lt(2, 5));
    }

    public function testStringLiteralExpr(): void
    {
        $this->assertEquals("'word'", (string) $this->expr->literal('word'));
    }

    public function testNumericLiteralExpr(): void
    {
        $this->assertEquals(5, (string) $this->expr->literal(5));
    }

    /**
     * @group regression
     * @group DDC-610
     */
    public function testLiteralExprProperlyQuotesStrings(): void
    {
        $this->assertEquals("'00010001'", (string) $this->expr->literal('00010001'));
    }

    public function testGreaterThanOrEqualToExpr(): void
    {
        $this->assertEquals('5 >= 2', (string) $this->expr->gte(5, 2));
    }

    public function testLessThanOrEqualTo(): void
    {
        $this->assertEquals('2 <= 5', (string) $this->expr->lte(2, 5));
    }

    public function testBetweenExpr(): void
    {
        $this->assertEquals('u.id BETWEEN 3 AND 6', (string) $this->expr->between('u.id', 3, 6));
    }

    public function testTrimExpr(): void
    {
        $this->assertEquals('TRIM(u.id)', (string) $this->expr->trim('u.id'));
    }

    public function testIsNullExpr(): void
    {
        $this->assertEquals('u.id IS NULL', (string) $this->expr->isNull('u.id'));
    }

    public function testIsNotNullExpr(): void
    {
        $this->assertEquals('u.id IS NOT NULL', (string) $this->expr->isNotNull('u.id'));
    }

    public function testIsInstanceOfExpr(): void
    {
        $this->assertEquals('u INSTANCE OF Doctrine\Tests\Models\Company\CompanyEmployee', (string) $this->expr->isInstanceOf('u', CompanyEmployee::class));
    }

    public function testIsMemberOfExpr(): void
    {
        $this->assertEquals(':groupId MEMBER OF u.groups', (string) $this->expr->isMemberOf(':groupId', 'u.groups'));
    }

    public function provideIterableValue(): Generator
    {
        $gen = static function () {
            yield from [1, 2, 3];
        };

        yield 'simple_array' => [[1, 2, 3]];
        yield 'generator' => [$gen()];
    }

    public function provideLiteralIterableValue(): Generator
    {
        $gen = static function () {
            yield from ['foo', 'bar'];
        };

        yield 'simple_array' => [['foo', 'bar']];
        yield 'generator' => [$gen()];
    }

    /**
     * @dataProvider provideIterableValue
     */
    public function testInExpr(iterable $value): void
    {
        self::assertEquals('u.id IN(1, 2, 3)', (string) $this->expr->in('u.id', $value));
    }

    /**
     * @dataProvider provideLiteralIterableValue
     */
    public function testInLiteralExpr(iterable $value): void
    {
        self::assertEquals("u.type IN('foo', 'bar')", (string) $this->expr->in('u.type', $value));
    }

    /**
     * @dataProvider provideIterableValue
     */
    public function testNotInExpr(iterable $value): void
    {
        self::assertEquals('u.id NOT IN(1, 2, 3)', (string) $this->expr->notIn('u.id', $value));
    }

    /**
     * @dataProvider provideLiteralIterableValue
     */
    public function testNotInLiteralExpr(iterable $value): void
    {
        self::assertEquals("u.type NOT IN('foo', 'bar')", (string) $this->expr->notIn('u.type', $value));
    }

    public function testAndxOrxExpr(): void
    {
        $andExpr = $this->expr->andX();
        $andExpr->add($this->expr->eq(1, 1));
        $andExpr->add($this->expr->lt(1, 5));

        $orExpr = $this->expr->orX();
        $orExpr->add($andExpr);
        $orExpr->add($this->expr->eq(1, 1));

        $this->assertEquals('(1 = 1 AND 1 < 5) OR 1 = 1', (string) $orExpr);
    }

    public function testOrxExpr(): void
    {
        $orExpr = $this->expr->orX();
        $orExpr->add($this->expr->eq(1, 1));
        $orExpr->add($this->expr->lt(1, 5));

        $this->assertEquals('1 = 1 OR 1 < 5', (string) $orExpr);
    }

    public function testOrderByCountExpr(): void
    {
        $orderExpr = $this->expr->desc('u.username');

        $this->assertEquals($orderExpr->count(), 1);
        $this->assertEquals('u.username DESC', (string) $orderExpr);
    }

    public function testOrderByOrder(): void
    {
        $orderExpr = $this->expr->desc('u.username');
        $this->assertEquals('u.username DESC', (string) $orderExpr);
    }

    public function testOrderByAsc(): void
    {
        $orderExpr = $this->expr->asc('u.username');
        $this->assertEquals('u.username ASC', (string) $orderExpr);
    }

    public function testAddThrowsException(): void
    {
        $this->expectException('InvalidArgumentException');
        $orExpr = $this->expr->orX();
        $orExpr->add($this->expr->quot(5, 2));
    }

    /**
     * @group DDC-1683
     */
    public function testBooleanLiteral(): void
    {
        $this->assertEquals('true', $this->expr->literal(true));
        $this->assertEquals('false', $this->expr->literal(false));
    }

    /**
     * @group DDC-1686
     */
    public function testExpressionGetter(): void
    {
        // Andx
        $andx = new Expr\Andx(['1 = 1', '2 = 2']);
        $this->assertEquals(['1 = 1', '2 = 2'], $andx->getParts());

        // Comparison
        $comparison = new Expr\Comparison('foo', Expr\Comparison::EQ, 'bar');
        $this->assertEquals('foo', $comparison->getLeftExpr());
        $this->assertEquals('bar', $comparison->getRightExpr());
        $this->assertEquals(Expr\Comparison::EQ, $comparison->getOperator());

        // From
        $from = new Expr\From('Foo', 'f', 'f.id');
        $this->assertEquals('f', $from->getAlias());
        $this->assertEquals('Foo', $from->getFrom());
        $this->assertEquals('f.id', $from->getIndexBy());

        // Func
        $func = new Expr\Func('MAX', ['f.id']);
        $this->assertEquals('MAX', $func->getName());
        $this->assertEquals(['f.id'], $func->getArguments());

        // GroupBy
        $group = new Expr\GroupBy(['foo DESC', 'bar ASC']);
        $this->assertEquals(['foo DESC', 'bar ASC'], $group->getParts());

        // Join
        $join = new Expr\Join(Expr\Join::INNER_JOIN, 'f.bar', 'b', Expr\Join::ON, 'b.bar_id = 1', 'b.bar_id');
        $this->assertEquals(Expr\Join::INNER_JOIN, $join->getJoinType());
        $this->assertEquals(Expr\Join::ON, $join->getConditionType());
        $this->assertEquals('b.bar_id = 1', $join->getCondition());
        $this->assertEquals('b.bar_id', $join->getIndexBy());
        $this->assertEquals('f.bar', $join->getJoin());
        $this->assertEquals('b', $join->getAlias());

        // Literal
        $literal = new Expr\Literal(['foo']);
        $this->assertEquals(['foo'], $literal->getParts());

        // Math
        $math = new Expr\Math(10, '+', 20);
        $this->assertEquals(10, $math->getLeftExpr());
        $this->assertEquals(20, $math->getRightExpr());
        $this->assertEquals('+', $math->getOperator());

        // OrderBy
        $order = new Expr\OrderBy('foo', 'DESC');
        $this->assertEquals(['foo DESC'], $order->getParts());

        // Andx
        $orx = new Expr\Orx(['foo = 1', 'bar = 2']);
        $this->assertEquals(['foo = 1', 'bar = 2'], $orx->getParts());

        // Select
        $select = new Expr\Select(['foo', 'bar']);
        $this->assertEquals(['foo', 'bar'], $select->getParts());
    }

    public function testAddEmpty(): void
    {
        $andExpr = $this->expr->andX();
        $andExpr->add($this->expr->andX());

        $this->assertEquals(0, $andExpr->count());
    }

    public function testAddNull(): void
    {
        $andExpr = $this->expr->andX();
        $andExpr->add(null);

        $this->assertEquals(0, $andExpr->count());
    }
}

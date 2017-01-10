<?php

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query\Expr;
use Doctrine\Tests\Models\Company\CompanyEmployee;
use Doctrine\Tests\OrmTestCase;

/**
 * Test case for the DQL Expr class used for generating DQL snippets through
 * a programmatic interface
 *
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class ExprTest extends OrmTestCase
{
    private $em;

    /**
     * @var Expr
     */
    private $expr;

    protected function setUp()
    {
        $this->em = $this->getTestEntityManager();
        $this->expr = new Expr;
    }

    public function testAvgExpr()
    {
        self::assertEquals('AVG(u.id)', (string) $this->expr->avg('u.id'));
    }

    public function testMaxExpr()
    {
        self::assertEquals('MAX(u.id)', (string) $this->expr->max('u.id'));
    }

    public function testMinExpr()
    {
        self::assertEquals('MIN(u.id)', (string) $this->expr->min('u.id'));
    }

    public function testCountExpr()
    {
        self::assertEquals('MAX(u.id)', (string) $this->expr->max('u.id'));
    }

    public function testCountDistinctExpr()
    {
        self::assertEquals('COUNT(DISTINCT u.id)', (string) $this->expr->countDistinct('u.id'));
    }

    public function testCountDistinctExprMulti()
    {
        self::assertEquals('COUNT(DISTINCT u.id, u.name)', (string) $this->expr->countDistinct('u.id', 'u.name'));
    }

    public function testExistsExpr()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('u')->from('User', 'u')->where('u.name = ?1');

        self::assertEquals('EXISTS(SELECT u FROM User u WHERE u.name = ?1)', (string) $this->expr->exists($qb));
    }

    public function testAllExpr()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('u')->from('User', 'u')->where('u.name = ?1');

        self::assertEquals('ALL(SELECT u FROM User u WHERE u.name = ?1)', (string) $this->expr->all($qb));
    }

    public function testSomeExpr()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('u')->from('User', 'u')->where('u.name = ?1');

        self::assertEquals('SOME(SELECT u FROM User u WHERE u.name = ?1)', (string) $this->expr->some($qb));
    }

    public function testAnyExpr()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('u')->from('User', 'u')->where('u.name = ?1');

        self::assertEquals('ANY(SELECT u FROM User u WHERE u.name = ?1)', (string) $this->expr->any($qb));
    }

    public function testNotExpr()
    {
        $qb = $this->em->createQueryBuilder();
        $qb->select('u')->from('User', 'u')->where('u.name = ?1');

        self::assertEquals('NOT(SELECT u FROM User u WHERE u.name = ?1)', (string) $this->expr->not($qb));
    }

    public function testAndExpr()
    {
        self::assertEquals('1 = 1 AND 2 = 2', (string) $this->expr->andX((string) $this->expr->eq(1, 1), (string) $this->expr->eq(2, 2)));
    }

    public function testIntelligentParenthesisPreventionAndExpr()
    {
        self::assertEquals(
            '1 = 1 AND 2 = 2',
            (string) $this->expr->andX($this->expr->orX($this->expr->andX($this->expr->eq(1, 1))), (string) $this->expr->eq(2, 2))
        );
    }

    public function testOrExpr()
    {
        self::assertEquals('1 = 1 OR 2 = 2', (string) $this->expr->orX((string) $this->expr->eq(1, 1), (string) $this->expr->eq(2, 2)));
    }

    public function testAbsExpr()
    {
        self::assertEquals('ABS(1)', (string) $this->expr->abs(1));
    }

    public function testProdExpr()
    {
        self::assertEquals('1 * 2', (string) $this->expr->prod(1, 2));
    }

    public function testDiffExpr()
    {
        self::assertEquals('1 - 2', (string) $this->expr->diff(1, 2));
    }

    public function testSumExpr()
    {
        self::assertEquals('1 + 2', (string) $this->expr->sum(1, 2));
    }

    public function testQuotientExpr()
    {
        self::assertEquals('10 / 2', (string) $this->expr->quot(10, 2));
    }

    public function testScopeInArithmeticExpr()
    {
        self::assertEquals('(100 - 20) / 2', (string) $this->expr->quot($this->expr->diff(100, 20), 2));
        self::assertEquals('100 - (20 / 2)', (string) $this->expr->diff(100, $this->expr->quot(20, 2)));
    }

    public function testSquareRootExpr()
    {
        self::assertEquals('SQRT(1)', (string) $this->expr->sqrt(1));
    }

    public function testEqualExpr()
    {
        self::assertEquals('1 = 1', (string) $this->expr->eq(1, 1));
    }

    public function testLikeExpr()
    {
        self::assertEquals('a.description LIKE :description', (string) $this->expr->like('a.description', ':description'));
    }

    public function testNotLikeExpr()
    {
        self::assertEquals('a.description NOT LIKE :description', (string) $this->expr->notLike('a.description', ':description'));
    }

    public function testConcatExpr()
    {
        self::assertEquals('CONCAT(u.first_name, u.last_name)', (string) $this->expr->concat('u.first_name', 'u.last_name'));
        self::assertEquals('CONCAT(u.first_name, u.middle_name, u.last_name)', (string) $this->expr->concat('u.first_name', 'u.middle_name', 'u.last_name'));
    }

    public function testSubstringExpr()
    {
        self::assertEquals('SUBSTRING(a.title, 0, 25)', (string) $this->expr->substring('a.title', 0, 25));
    }

    /**
     * @group regression
     * @group DDC-612
     */
    public function testSubstringExprAcceptsTwoArguments()
    {
        self::assertEquals('SUBSTRING(a.title, 5)', (string) $this->expr->substring('a.title', 5));
    }

    public function testLowerExpr()
    {
        self::assertEquals('LOWER(u.first_name)', (string) $this->expr->lower('u.first_name'));
    }

    public function testUpperExpr()
    {
        self::assertEquals('UPPER(u.first_name)', (string) $this->expr->upper('u.first_name'));
    }

    public function testLengthExpr()
    {
        self::assertEquals('LENGTH(u.first_name)', (string) $this->expr->length('u.first_name'));
    }

    public function testGreaterThanExpr()
    {
        self::assertEquals('5 > 2', (string) $this->expr->gt(5, 2));
    }

    public function testLessThanExpr()
    {
        self::assertEquals('2 < 5', (string) $this->expr->lt(2, 5));
    }

    public function testStringLiteralExpr()
    {
        self::assertEquals("'word'", (string) $this->expr->literal('word'));
    }

    public function testNumericLiteralExpr()
    {
        self::assertEquals(5, (string) $this->expr->literal(5));
    }

    /**
     * @group regression
     * @group DDC-610
     */
    public function testLiteralExprProperlyQuotesStrings()
    {
       self::assertEquals("'00010001'", (string) $this->expr->literal('00010001'));
    }

    public function testGreaterThanOrEqualToExpr()
    {
        self::assertEquals('5 >= 2', (string) $this->expr->gte(5, 2));
    }

    public function testLessThanOrEqualTo()
    {
        self::assertEquals('2 <= 5', (string) $this->expr->lte(2, 5));
    }

    public function testBetweenExpr()
    {
        self::assertEquals('u.id BETWEEN 3 AND 6', (string) $this->expr->between('u.id', 3, 6));
    }

    public function testTrimExpr()
    {
        self::assertEquals('TRIM(u.id)', (string) $this->expr->trim('u.id'));
    }

    public function testIsNullExpr()
    {
        self::assertEquals('u.id IS NULL', (string) $this->expr->isNull('u.id'));
    }

    public function testIsNotNullExpr()
    {
        self::assertEquals('u.id IS NOT NULL', (string) $this->expr->isNotNull('u.id'));
    }

    public function testIsInstanceOfExpr() {
        self::assertEquals('u INSTANCE OF Doctrine\Tests\Models\Company\CompanyEmployee', (string) $this->expr->isInstanceOf('u', CompanyEmployee::class));
    }

    public function testIsMemberOfExpr() {
        self::assertEquals(':groupId MEMBER OF u.groups', (string) $this->expr->isMemberOf(':groupId', 'u.groups'));
    }

    public function testInExpr()
    {
        self::assertEquals('u.id IN(1, 2, 3)', (string) $this->expr->in('u.id', [1, 2, 3]));
    }

    public function testInLiteralExpr()
    {
        self::assertEquals("u.type IN('foo', 'bar')", (string) $this->expr->in('u.type', ['foo', 'bar']));
    }

    public function testNotInExpr()
    {
        self::assertEquals('u.id NOT IN(1, 2, 3)', (string) $this->expr->notIn('u.id', [1, 2, 3]));
    }

    public function testNotInLiteralExpr()
    {
        self::assertEquals("u.type NOT IN('foo', 'bar')", (string) $this->expr->notIn('u.type', ['foo', 'bar']));
    }

    public function testAndxOrxExpr()
    {
        $andExpr = $this->expr->andX();
        $andExpr->add($this->expr->eq(1, 1));
        $andExpr->add($this->expr->lt(1, 5));

        $orExpr = $this->expr->orX();
        $orExpr->add($andExpr);
        $orExpr->add($this->expr->eq(1, 1));

        self::assertEquals('(1 = 1 AND 1 < 5) OR 1 = 1', (string) $orExpr);
    }

    public function testOrxExpr()
    {
        $orExpr = $this->expr->orX();
        $orExpr->add($this->expr->eq(1, 1));
        $orExpr->add($this->expr->lt(1, 5));

        self::assertEquals('1 = 1 OR 1 < 5', (string) $orExpr);
    }

    public function testOrderByCountExpr()
    {
        $orderExpr = $this->expr->desc('u.username');

        self::assertEquals($orderExpr->count(), 1);
        self::assertEquals('u.username DESC', (string) $orderExpr);
    }

    public function testOrderByOrder()
    {
        $orderExpr = $this->expr->desc('u.username');
        self::assertEquals('u.username DESC', (string) $orderExpr);
    }

    public function testOrderByAsc()
    {
        $orderExpr = $this->expr->asc('u.username');
        self::assertEquals('u.username ASC', (string) $orderExpr);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddThrowsException()
    {
        $orExpr = $this->expr->orX();
        $orExpr->add($this->expr->quot(5, 2));
    }

    /**
     * @group DDC-1683
     */
    public function testBooleanLiteral()
    {
        self::assertEquals('true', $this->expr->literal(true));
        self::assertEquals('false', $this->expr->literal(false));
    }


    /**
     * @group DDC-1686
     */
    public function testExpressionGetter()
    {

        // Andx
        $andx = new Expr\Andx(['1 = 1', '2 = 2']);
        self::assertEquals(['1 = 1', '2 = 2'], $andx->getParts());

        // Comparison
        $comparison = new Expr\Comparison('foo', Expr\Comparison::EQ, 'bar');
        self::assertEquals('foo', $comparison->getLeftExpr());
        self::assertEquals('bar', $comparison->getRightExpr());
        self::assertEquals(Expr\Comparison::EQ, $comparison->getOperator());

        // From
        $from = new Expr\From('Foo', 'f', 'f.id');
        self::assertEquals('f', $from->getAlias());
        self::assertEquals('Foo', $from->getFrom());
        self::assertEquals('f.id', $from->getIndexBy());

        // Func
        $func = new Expr\Func('MAX', ['f.id']);
        self::assertEquals('MAX', $func->getName());
        self::assertEquals(['f.id'], $func->getArguments());

        // GroupBy
        $group = new Expr\GroupBy(['foo DESC', 'bar ASC']);
        self::assertEquals(['foo DESC', 'bar ASC'], $group->getParts());

        // Join
        $join = new Expr\Join(Expr\Join::INNER_JOIN, 'f.bar', 'b', Expr\Join::ON, 'b.bar_id = 1', 'b.bar_id');
        self::assertEquals(Expr\Join::INNER_JOIN, $join->getJoinType());
        self::assertEquals(Expr\Join::ON, $join->getConditionType());
        self::assertEquals('b.bar_id = 1', $join->getCondition());
        self::assertEquals('b.bar_id', $join->getIndexBy());
        self::assertEquals('f.bar', $join->getJoin());
        self::assertEquals('b', $join->getAlias());

        // Literal
        $literal = new Expr\Literal(['foo']);
        self::assertEquals(['foo'], $literal->getParts());

        // Math
        $math = new Expr\Math(10, '+', 20);
        self::assertEquals(10, $math->getLeftExpr());
        self::assertEquals(20, $math->getRightExpr());
        self::assertEquals('+', $math->getOperator());

        // OrderBy
        $order = new Expr\OrderBy('foo', 'DESC');
        self::assertEquals(['foo DESC'], $order->getParts());

        // Andx
        $orx = new Expr\Orx(['foo = 1', 'bar = 2']);
        self::assertEquals(['foo = 1', 'bar = 2'], $orx->getParts());

        // Select
        $select = new Expr\Select(['foo', 'bar']);
        self::assertEquals(['foo', 'bar'], $select->getParts());
    }

    public function testAddEmpty()
    {
        $andExpr = $this->expr->andX();
        $andExpr->add($this->expr->andX());

        self::assertEquals(0, $andExpr->count());
    }

    public function testAddNull()
    {
        $andExpr = $this->expr->andX();
        $andExpr->add(null);

        self::assertEquals(0, $andExpr->count());
    }
}

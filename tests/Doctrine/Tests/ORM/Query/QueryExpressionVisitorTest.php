<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Query;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Expr\Comparison as CriteriaComparison;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\Common\Collections\ExpressionBuilder as CriteriaBuilder;
use Doctrine\ORM\Query\Expr as QueryBuilder;
use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\QueryExpressionVisitor;
use PHPUnit\Framework\TestCase;

/**
 * Test for QueryExpressionVisitor
 */
class QueryExpressionVisitorTest extends TestCase
{
    /** @var QueryExpressionVisitor */
    private $visitor;

    protected function setUp(): void
    {
        $this->visitor = new QueryExpressionVisitor(['o', 'p']);
    }

    /**
     * @param QueryBuilder\Comparison|QueryBuilder\Func|string $queryExpr
     *
     * @dataProvider comparisonData
     */
    public function testWalkComparison(CriteriaComparison $criteriaExpr, $queryExpr, ?Parameter $parameter = null): void
    {
        self::assertEquals($queryExpr, $this->visitor->walkComparison($criteriaExpr));
        if ($parameter) {
            self::assertEquals(new ArrayCollection([$parameter]), $this->visitor->getParameters());
        }
    }

    /**
     * @psalm-return list<array{
     *                   0: CriteriaComparison,
     *                   1: QueryBuilder\Comparison|QueryBuilder\Func|string,
     *                   2?: Parameter,
     *               }>
     */
    public static function comparisonData(): array
    {
        $cb = new CriteriaBuilder();
        $qb = new QueryBuilder();

        return [
            [$cb->eq('field', 'value'), $qb->eq('o.field', ':field'), new Parameter('field', 'value')],
            [$cb->neq('field', 'value'), $qb->neq('o.field', ':field'), new Parameter('field', 'value')],
            [$cb->eq('field', null), $qb->isNull('o.field')],
            [$cb->neq('field', null), $qb->isNotNull('o.field')],
            [$cb->isNull('field'), $qb->isNull('o.field')],

            [$cb->gt('field', 'value'), $qb->gt('o.field', ':field'), new Parameter('field', 'value')],
            [$cb->gte('field', 'value'), $qb->gte('o.field', ':field'), new Parameter('field', 'value')],
            [$cb->lt('field', 'value'), $qb->lt('o.field', ':field'), new Parameter('field', 'value')],
            [$cb->lte('field', 'value'), $qb->lte('o.field', ':field'), new Parameter('field', 'value')],

            [$cb->in('field', ['value']), $qb->in('o.field', ':field'), new Parameter('field', ['value'])],
            [$cb->notIn('field', ['value']), $qb->notIn('o.field', ':field'), new Parameter('field', ['value'])],

            [$cb->contains('field', 'value'), $qb->like('o.field', ':field'), new Parameter('field', '%value%')],
            [$cb->memberOf(':field', 'o.field'), $qb->isMemberOf(':field', 'o.field')],

            [$cb->startsWith('field', 'value'), $qb->like('o.field', ':field'), new Parameter('field', 'value%')],
            [$cb->endsWith('field', 'value'), $qb->like('o.field', ':field'), new Parameter('field', '%value')],

            // Test parameter conversion
            [$cb->eq('object.field', 'value'), $qb->eq('o.object.field', ':object_field'), new Parameter('object_field', 'value')],

            // Test alternative rootAlias
            [$cb->eq('p.field', 'value'), $qb->eq('p.field', ':p_field'), new Parameter('p_field', 'value')],
            [$cb->eq('p.object.field', 'value'), $qb->eq('p.object.field', ':p_object_field'), new Parameter('p_object_field', 'value')],
        ];
    }

    public function testWalkAndCompositeExpression(): void
    {
        $cb   = new CriteriaBuilder();
        $expr = $this->visitor->walkCompositeExpression(
            $cb->andX(
                $cb->eq('foo', 1),
                $cb->eq('bar', 1)
            )
        );

        self::assertInstanceOf(QueryBuilder\Andx::class, $expr);
        self::assertCount(2, $expr->getParts());
    }

    public function testWalkOrCompositeExpression(): void
    {
        $cb   = new CriteriaBuilder();
        $expr = $this->visitor->walkCompositeExpression(
            $cb->orX(
                $cb->eq('foo', 1),
                $cb->eq('bar', 1)
            )
        );

        self::assertInstanceOf(QueryBuilder\Orx::class, $expr);
        self::assertCount(2, $expr->getParts());
    }

    public function testWalkValue(): void
    {
        self::assertEquals('value', $this->visitor->walkValue(new Value('value')));
    }

    public function testClearParameters(): void
    {
        $this->visitor->getParameters()->add(new Parameter('field', 'value'));

        $this->visitor->clearParameters();

        self::assertCount(0, $this->visitor->getParameters());
    }
}

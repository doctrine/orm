<?php

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
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class QueryExpressionVisitorTest extends TestCase
{
    /**
     * @var QueryExpressionVisitor
     */
    private $visitor;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->visitor = new QueryExpressionVisitor(['o','p']);
    }

    /**
     * @param CriteriaComparison     $criteriaExpr
     * @param QueryBuilder\Comparison|string $queryExpr
     * @param Parameter              $parameter
     *
     * @dataProvider comparisonData
     */
    public function testWalkComparison(CriteriaComparison $criteriaExpr, $queryExpr, Parameter $parameter = null)
    {
        $this->assertEquals($queryExpr, $this->visitor->walkComparison($criteriaExpr));
        if ($parameter) {
            $this->assertEquals(new ArrayCollection([$parameter]), $this->visitor->getParameters());
        }
    }

    public function comparisonData()
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

    public function testWalkAndCompositeExpression()
    {
        $cb = new CriteriaBuilder();
        $expr = $this->visitor->walkCompositeExpression(
            $cb->andX(
                $cb->eq("foo", 1),
                $cb->eq("bar", 1)
            )
        );

        $this->assertInstanceOf(QueryBuilder\Andx::class, $expr);
        $this->assertCount(2, $expr->getParts());
    }

    public function testWalkOrCompositeExpression()
    {
        $cb = new CriteriaBuilder();
        $expr = $this->visitor->walkCompositeExpression(
            $cb->orX(
                $cb->eq("foo", 1),
                $cb->eq("bar", 1)
            )
        );

        $this->assertInstanceOf(QueryBuilder\Orx::class, $expr);
        $this->assertCount(2, $expr->getParts());
    }

    public function testWalkValue()
    {
        $this->assertEquals('value', $this->visitor->walkValue(new Value('value')));
    }

    public function testClearParameters()
    {
        $this->visitor->getParameters()->add(new Parameter('field', 'value'));

        $this->visitor->clearParameters();

        $this->assertCount(0, $this->visitor->getParameters());
    }
}

<?php
/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Tests\ORM\Query;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Value;
use Doctrine\Common\Collections\Expr\Comparison as CriteriaComparison;
use Doctrine\ORM\Query\Expr\Comparison as QueryComparison;
use Doctrine\Common\Collections\ExpressionBuilder as CriteriaBuilder;
use Doctrine\ORM\Query\Expr as QueryBuilder;

use Doctrine\ORM\Query\Parameter;
use Doctrine\ORM\Query\QueryExpressionVisitor;

/**
 * Test for QueryExpressionVisitor
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class QueryExpressionVisitorTest extends \PHPUnit_Framework_TestCase
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
        $this->visitor = new QueryExpressionVisitor('entity');
    }

    /**
     * @param CriteriaComparison     $criteriaExpr
     * @param QueryComparison|string $queryExpr
     * @param Parameter              $parameter
     *
     * @dataProvider comparisonData
     */
    public function testWalkComparison(CriteriaComparison $criteriaExpr, $queryExpr, Parameter $parameter = null)
    {
        $this->assertEquals($queryExpr, $this->visitor->walkComparison($criteriaExpr));
        if ($parameter) {
            $this->assertEquals(new ArrayCollection(array($parameter)), $this->visitor->getParameters());
        }
    }

    public function comparisonData()
    {
        $cb = new CriteriaBuilder();
        $qb = new QueryBuilder();

        return array(
            array($cb->eq('field', 'value'), $qb->eq('entity.field', ':entity_field_0'), new Parameter('entity_field_0', 'value')),
            array($cb->neq('field', 'value'), $qb->neq('entity.field', ':entity_field_0'), new Parameter('entity_field_0', 'value')),
            array($cb->eq('field', null), $qb->isNull('entity.field')),
            array($cb->neq('field', null), $qb->isNotNull('entity.field')),
            array($cb->isNull('field'), $qb->isNull('entity.field')),

            array($cb->gt('field', 'value'), $qb->gt('entity.field', ':entity_field_0'), new Parameter('entity_field_0', 'value')),
            array($cb->gte('field', 'value'), $qb->gte('entity.field', ':entity_field_0'), new Parameter('entity_field_0', 'value')),
            array($cb->lt('field', 'value'), $qb->lt('entity.field', ':entity_field_0'), new Parameter('entity_field_0', 'value')),
            array($cb->lte('field', 'value'), $qb->lte('entity.field', ':entity_field_0'), new Parameter('entity_field_0', 'value')),

            array($cb->in('field', array('value')), $qb->in('entity.field', ':entity_field_0'), new Parameter('entity_field_0', array('value'))),
            array($cb->notIn('field', array('value')), $qb->notIn('entity.field', ':entity_field_0'), new Parameter('entity_field_0', array('value'))),

            // Test second level alias
            array($cb->eq('object.field', 'value'), $qb->eq('object.field', ':object_field_0'), new Parameter('object_field_0', 'value')),
        );
    }

    public function testWalkComparisonSameField()
    {
        $cb = new CriteriaBuilder();
        $this->visitor->walkComparison($cb->eq('field1', 'f1_v1'));
        $this->visitor->walkComparison($cb->eq('field1', 'f1_v2'));
        $this->visitor->walkComparison($cb->eq('field2', 'f2_v1'));
        $this->visitor->walkComparison($cb->eq('field2', 'f2_v2'));

        $this->assertEquals(
            new ArrayCollection(array(
                new Parameter('entity_field1_0', 'f1_v1'),
                new Parameter('entity_field1_1', 'f1_v2'),
                new Parameter('entity_field2_0', 'f2_v1'),
                new Parameter('entity_field2_1', 'f2_v2'),
            )),
            $this->visitor->getParameters()
        );
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

        $this->assertInstanceOf('Doctrine\ORM\Query\Expr\Andx', $expr);
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

        $this->assertInstanceOf('Doctrine\ORM\Query\Expr\Orx', $expr);
        $this->assertCount(2, $expr->getParts());
    }

    public function testWalkValue()
    {
        $this->assertEquals('value', $this->visitor->walkValue(new Value('value')));
    }

    public function testClearParameters()
    {
        $cb = new CriteriaBuilder();
        $this->visitor->dispatch($cb->eq('field', 'value'));
        $this->visitor->clearParameters();

        $this->assertCount(0, $this->visitor->getParameters());
    }

    public function testWalkOrdering()
    {
        $this->assertEquals(
            new QueryBuilder\OrderBy('entity.field', 'DESC'),
            $this->visitor->walkOrdering('field', Criteria::DESC)
        );
    }

    public function testDispatchOrderings()
    {
        $this->assertCount(3, $this->visitor->dispatchOrderings(array(
            'field1' => Criteria::ASC,
            'field2' => Criteria::DESC,
            'field3' => null
        )));
    }
}

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
     * @var CriteriaBuilder
     */
    private $criteriaBuilder;
    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    public function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        $this->criteriaBuilder = new CriteriaBuilder();
        $this->queryBuilder = new QueryBuilder();

        parent::__construct($name, $data, $dataName);
    }


    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->visitor = new QueryExpressionVisitor();
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
        return array(
            array($this->criteriaBuilder->eq('field', 'value'), $this->queryBuilder->eq('field', ':field'), new Parameter('field', 'value')),
            array($this->criteriaBuilder->neq('field', 'value'), $this->queryBuilder->neq('field', ':field'), new Parameter('field', 'value')),
            array($this->criteriaBuilder->eq('field', null), $this->queryBuilder->isNull('field')),
            array($this->criteriaBuilder->neq('field', null), $this->queryBuilder->isNotNull('field')),
            array($this->criteriaBuilder->isNull('field'), $this->queryBuilder->isNull('field')),

            array($this->criteriaBuilder->gt('field', 'value'), $this->queryBuilder->gt('field', ':field'), new Parameter('field', 'value')),
            array($this->criteriaBuilder->gte('field', 'value'), $this->queryBuilder->gte('field', ':field'), new Parameter('field', 'value')),
            array($this->criteriaBuilder->lt('field', 'value'), $this->queryBuilder->lt('field', ':field'), new Parameter('field', 'value')),
            array($this->criteriaBuilder->lte('field', 'value'), $this->queryBuilder->lte('field', ':field'), new Parameter('field', 'value')),

            array($this->criteriaBuilder->in('field', array('value')), $this->queryBuilder->in('field', ':field'), new Parameter('field', array('value'))),
            array($this->criteriaBuilder->notIn('field', array('value')), $this->queryBuilder->notIn('field', ':field'), new Parameter('field', array('value'))),

            // Test parameter conversion
            array($this->criteriaBuilder->eq('object.field', 'value'), $this->queryBuilder->eq('object.field', ':object_field'), new Parameter('object_field', 'value')),
        );
    }

    public function testWalkAndCompositeExpression()
    {
        $expr = $this->visitor->walkCompositeExpression(
            $this->criteriaBuilder->andX(
                $this->criteriaBuilder->eq("foo", 1),
                $this->criteriaBuilder->eq("bar", 1)
            )
        );

        $this->assertInstanceOf('Doctrine\ORM\Query\Expr\Andx', $expr);
        $this->assertCount(2, $expr->getParts());
    }

    public function testWalkOrCompositeExpression()
    {
        $expr = $this->visitor->walkCompositeExpression(
            $this->criteriaBuilder->orX(
                $this->criteriaBuilder->eq("foo", 1),
                $this->criteriaBuilder->eq("bar", 1)
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
        $this->visitor->getParameters()->add(new Parameter('field', 'value'));

        $this->visitor->clearParameters();

        $this->assertCount(0, $this->visitor->getParameters());
    }
}

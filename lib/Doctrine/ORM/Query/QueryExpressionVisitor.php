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

namespace Doctrine\ORM\Query;

use Doctrine\Common\Collections\ArrayCollection;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Parameter;

/**
 * Converts Collection expressions to Query expressions.
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @since 2.4
 */
class QueryExpressionVisitor extends ExpressionVisitor
{
    /**
     * @var array
     */
    private static $operatorMap = array(
        Comparison::GT => Expr\Comparison::GT,
        Comparison::GTE => Expr\Comparison::GTE,
        Comparison::LT  => Expr\Comparison::LT,
        Comparison::LTE => Expr\Comparison::LTE
    );

    /**
     * @var Expr
     */
    private $expr;

    /**
     * @var array
     */
    private $parameters = array();

    /**
     * @var string|null
     */
    private $alias;

    /**
     * Constructor with internal initialization
     */
    public function __construct($alias = null)
    {
        $this->alias = $alias;
        $this->expr = new Expr();
    }

    /**
     * Gets bound parameters.
     * Filled after {@link dispach()}.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getParameters()
    {
        return new ArrayCollection(array_reduce(
            $this->parameters,
            function ($parameters, $fieldParameters) {
                return array_merge($parameters, $fieldParameters);
            },
            array()
        ));
    }

    /**
     * Clears parameters.
     *
     * @return void
     */
    public function clearParameters()
    {
        $this->parameters = array();
    }

    /**
     * Add field parameter
     *
     * @param string $field
     * @param string $value
     * @param string $parameter
     */
    private function addParameter($field, $parameter, $value)
    {
        $this->parameters[$field][] = new Parameter($parameter ?: $field, $value);
    }

    /**
     * Converts Criteria expression to Query one based on static map.
     *
     * @param string $criteriaOperator
     *
     * @return string|null
     */
    private static function convertComparisonOperator($criteriaOperator)
    {
        return isset(self::$operatorMap[$criteriaOperator]) ? self::$operatorMap[$criteriaOperator] : null;
    }

    /**
     * {@inheritDoc}
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $expressionList = array();

        foreach ($expr->getExpressionList() as $child) {
            $expressionList[] = $this->dispatch($child);
        }

        switch($expr->getType()) {
            case CompositeExpression::TYPE_AND:
                return new Expr\Andx($expressionList);

            case CompositeExpression::TYPE_OR:
                return new Expr\Orx($expressionList);

            default:
                throw new \RuntimeException(sprintf('Unknown composite expression "%s"', $expr->getType()));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function walkComparison(Comparison $comparison)
    {
        $alias = $this->aliasField($comparison->getField());
        $parameter = $this->parametrizeField($alias);
        $value = $this->walkValue($comparison->getValue());
        $placeholder = ':' . $parameter;

        switch ($comparison->getOperator()) {
            case Comparison::IN:
                $this->addParameter($alias, $parameter, $value);
                return $this->expr->in($alias, $placeholder);

            case Comparison::NIN:
                $this->addParameter($alias, $parameter, $value);
                return $this->expr->notIn($alias, $placeholder);

            case Comparison::EQ:
            case Comparison::IS:
                if ($value === null) {
                    return $this->expr->isNull($alias);
                }
                $this->addParameter($alias, $parameter, $value);
                return $this->expr->eq($alias, $placeholder);

            case Comparison::NEQ:
                if ($this->walkValue($comparison->getValue()) === null) {
                    return $this->expr->isNotNull($alias);
                }
                $this->addParameter($alias, $parameter, $value);
                return $this->expr->neq($alias, $placeholder);

            default:
                $operator = self::convertComparisonOperator($comparison->getOperator());
                if ($operator) {
                    $this->addParameter($alias, $parameter, $value);
                    return new Expr\Comparison(
                        $alias,
                        $operator,
                        $placeholder
                    );
                }

                throw new \RuntimeException(sprintf('Unknown comparison operator "%s"', $comparison->getOperator()));
        }
    }

    /**
     * {@inheritDoc}
     */
    public function walkValue(Value $value)
    {
        return $value->getValue();
    }

    /**
     * Convert criteria orderings to query expressions
     *
     * @param array $orderings
     *
     * @return Expr\OrderBy[]
     */
    public function dispatchOrderings(array $orderings)
    {
        $expressions = array();
        foreach ($orderings as $field => $order) {
            $expressions[] = $this->walkOrdering($field, $order);
        }

        return $expressions;
    }

    /**
     * Convert field ordering to query expression
     *
     * @param string      $field
     * @param string|null $order Criteria::ASC or Criteria::DESC
     *
     * @return Expr\OrderBy
     *
     * @see Criteria
     */
    public function walkOrdering($field, $order = null)
    {
        if ($order !== null && $order !== Criteria::ASC && $order !== Criteria::DESC) {
            throw new \InvalidArgumentException(sprintf('Unknown order "%s"', $order));
        }

        return new Expr\OrderBy($this->aliasField($field), $order);
    }

    /**
     * Create parameter name for field
     *
     * @param string $field
     *
     * @return string
     */
    private function parametrizeField($field)
    {
        // Parameter for field "foo.bar.baz" is "foo_bar_baz_$i"
        return sprintf(
            '%s_%d',
            str_replace('.', '_', $field),
            isset($this->parameters[$field]) ? count($this->parameters[$field]) : 0
        );
    }

    /**
     * Create alias for field
     *
     * @param string $field
     *
     * @return string
     */
    private function aliasField($field)
    {
        // Simple property - use rootAlias or return as is
        if (strpos($field, '.') === false) {
            return $this->alias ? $this->alias . '.' . $field : $field;
        }

        // Reduce property path to last 2 elements
        $parts = explode('.', $field);
        $partCount = count($parts);

        return $parts[$partCount - 2] . '.' . $parts[$partCount - 1];
    }
}

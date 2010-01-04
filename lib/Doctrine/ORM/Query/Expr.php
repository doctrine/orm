<?php
/*
 *  $Id$
 *
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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Query;

/**
 * This class is used to generate DQL expressions via a set of PHP static functions
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class Expr
{
    /**
     * Creates an instance of Expr\Andx with given arguments.
     * Each argument is separated by an "AND". Example:
     *
     *     [php]
     *     // (u.type = ?1) AND (u.role = ?2)
     *     $q->where($q->expr()->andx('u.type = ?1', 'u.role = ?2'));
     *
     * @param mixed $x Optional clause. Defaults = null, but requires
     *                 at least one defined when converting to string.
     * @return Expr\Andx
     */
    public function andx($x = null)
    {
        return new Expr\Andx(func_get_args());
    }

    /**
     * Creates an instance of Expr\Orx with given arguments.
     * Each argument is separated by an "OR". Example:
     *
     *     [php]
     *     // (u.type = ?1) OR (u.role = ?2)
     *     $q->where($q->expr()->orx('u.type = ?1', 'u.role = ?2'));
     *
     * @param mixed $x Optional clause. Defaults = null, but requires
     *                 at least one defined when converting to string.
     * @return Expr\Orx
     */
    public function orx($x = null)
    {
        return new Expr\Orx(func_get_args());
    }

    /**
     * Creates an instance of Expr\Select with given arguments.
     * Each argument is separated by a ",". Example:
     *
     *     [php]
     *     // u.id, u.name, u.surname
     *     $q->select($q->expr()->select('u.id', 'u.name')->add('u.surname'));
     *
     * @param mixed $select Optional select. Defaults = null, but requires
     *                      at least one defined when converting to string.
     * @return Expr\Select
     */
    public function select($select = null)
    {
        return new Expr\Select(func_get_args());
    }
    
    /**
     * Creates an instance of Expr\From with given arguments.
     *
     *     [php]
     *     // User u
     *     $q->from($q->expr()->from('User', 'u'));
     *
     * @param string $from Entity name.
     * @param string $alias Alias to be used by Entity.
     * @return Expr\From
     */
    public function from($from, $alias)
    {
        return new Expr\From($from, $alias);
    }
    
    /**
     * Creates an instance of Expr\Join with given arguments.
     *
     *     [php]
     *     // LEFT JOIN u.Group g WITH g.name = 'admin'
     *     $q->expr()->leftJoin('u.Group', 'g', 'WITH', "g.name = 'admin'")
     *
     * @param string $join Relation join.
     * @param string $alias Alias to be used by Relation.
     * @param string $conditionType Optional type of condition appender. Accepts either string or constant.
     *                              'ON' and 'WITH' are supported strings. Expr\Join::ON and Expr\Join::WITH are supported constants.
     * @param mixed $condition Optional condition to be appended.
     * @return Expr\Join
     */
    public function leftJoin($join, $alias, $conditionType = null, $condition = null)
    {
        return new Expr\Join(Expr\Join::LEFT_JOIN, $join, $alias, $conditionType, $condition);
    }
    
    /**
     * Creates an instance of Expr\Join with given arguments.
     *
     *     [php]
     *     // INNER JOIN u.Group g WITH g.name = 'admin'
     *     $q->expr()->innerJoin('u.Group', 'g', 'WITH', "g.name = 'admin'")
     *
     * @param string $join Relation join.
     * @param string $alias Alias to be used by Relation.
     * @param string $conditionType Optional type of condition appender. Accepts either string or constant.
     *                              'ON' and 'WITH' are supported strings. Expr\Join::ON and Expr\Join::WITH are supported constants.
     * @param mixed $condition Optional condition to be appended.
     * @return Expr\Join
     */
    public function innerJoin($join, $alias, $conditionType = null, $condition = null)
    {
        return new Expr\Join(Expr\Join::INNER_JOIN, $join, $alias, $conditionType, $condition);
    }

    /**
     * Creates an instance of Expr\OrderBy with given item sort and order.
     * Each argument is separated by a ",". Example:
     *
     *     [php]
     *     $q->orderBy($q->expr()->orderBy('u.surname', 'ASC')->add('u.name', 'ASC'));
     *
     * @param string $sort Optional item sort.
     * @param string $order Optional order to be applied in item.
     * @return Expr\OrderBy
     */
    public function orderBy($sort = null, $order = null)
    {
        return new Expr\OrderBy($sort, $order);
    }

    /**
     * Creates an instance of Expr\GroupBy with given arguments.
     * Each argument is separated by a ",". Example:
     *
     *     [php]
     *     // u.id, u.name
     *     $q->select($q->expr()->groupBy('u.id', 'u.name'));
     *
     * @param mixed $groupBy Optional group by. Defaults = null, but requires
     *                       at least one defined when converting to string.
     * @return Expr\Select
     */
    public function groupBy($groupBy = null)
    {
        return new Expr\GroupBy(func_get_args());
    }

    /**
     * Creates an instance of Expr\Comparison, with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> = <right expr>. Example:
     *
     *     [php]
     *     // u.id = ?1
     *     $q->where($q->expr()->eq('u.id', '?1'));
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Expr\Comparison
     */
    public function eq($x, $y)
    {
        return new Expr\Comparison($x, Expr\Comparison::EQ, $y);
    }

    /**
     * Creates an instance of Expr\Comparison, with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> <> <right expr>. Example:
     *
     *     [php]
     *     // u.id <> ?1
     *     $q->where($q->expr()->neq('u.id', '?1'));
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Expr\Comparison
     */
    public function neq($x, $y)
    {
        return new Expr\Comparison($x, Expr\Comparison::NEQ, $y);
    }

    /**
     * Creates an instance of Expr\Comparison, with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> < <right expr>. Example:
     *
     *     [php]
     *     // u.id < ?1
     *     $q->where($q->expr()->lt('u.id', '?1'));
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Expr\Comparison
     */
    public function lt($x, $y)
    {
        return new Expr\Comparison($x, Expr\Comparison::LT, $y);
    }

    /**
     * Creates an instance of Expr\Comparison, with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> <= <right expr>. Example:
     *
     *     [php]
     *     // u.id <= ?1
     *     $q->where($q->expr()->lte('u.id', '?1'));
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Expr\Comparison
     */
    public function lte($x, $y)
    {
        return new Expr\Comparison($x, Expr\Comparison::LTE, $y);
    }

    /**
     * Creates an instance of Expr\Comparison, with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> > <right expr>. Example:
     *
     *     [php]
     *     // u.id > ?1
     *     $q->where($q->expr()->gt('u.id', '?1'));
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Expr\Comparison
     */
    public function gt($x, $y)
    {
        return new Expr\Comparison($x, Expr\Comparison::GT, $y);
    }

    /**
     * Creates an instance of Expr\Comparison, with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> >= <right expr>. Example:
     *
     *     [php]
     *     // u.id >= ?1
     *     $q->where($q->expr()->gte('u.id', '?1'));
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Expr\Comparison
     */
    public function gte($x, $y)
    {
        return new Expr\Comparison($x, Expr\Comparison::GTE, $y);
    }

    /**
     * Creates an instance of AVG() function, with the given argument.
     *
     * @param mixed $x Argument to be used in AVG() function.
     * @return Expr\Func
     */
    public function avg($x)
    {
        return new Expr\Func('AVG', array($x));
    }

    /**
     * Creates an instance of MAX() function, with the given argument.
     *
     * @param mixed $x Argument to be used in MAX() function.
     * @return Expr\Func
     */
    public function max($x)
    {
        return new Expr\Func('MAX', array($x));
    }

    /**
     * Creates an instance of MIN() function, with the given argument.
     *
     * @param mixed $x Argument to be used in MIN() function.
     * @return Expr\Func
     */
    public function min($x)
    {
        return new Expr\Func('MIN', array($x));
    }

    /**
     * Creates an instance of COUNT() function, with the given argument.
     *
     * @param mixed $x Argument to be used in COUNT() function.
     * @return Expr\Func
     */
    public function count($x)
    {
        return new Expr\Func('COUNT', array($x));
    }

    /**
     * Creates an instance of COUNT(DISTINCT) function, with the given argument.
     *
     * @param mixed $x Argument to be used in COUNT(DISTINCT) function.
     * @return string
     */
    public function countDistinct($x)
    {
        return 'COUNT(DISTINCT ' . implode(', ', func_get_args()) . ')';
    }

    /**
     * Creates an instance of EXISTS() function, with the given DQL Subquery.
     *
     * @param mixed $subquery DQL Subquery to be used in EXISTS() function.
     * @return Expr\Func
     */
    public function exists($subquery)
    {
        return new Expr\Func('EXISTS', array($subquery));
    }

    /**
     * Creates an instance of ALL() function, with the given DQL Subquery.
     *
     * @param mixed $subquery DQL Subquery to be used in ALL() function.
     * @return Expr\Func
     */
    public function all($subquery)
    {
        return new Expr\Func('ALL', array($subquery));
    }

    /**
     * Creates an instance of SOME() function, with the given DQL Subquery.
     *
     * @param mixed $subquery DQL Subquery to be used in SOME() function.
     * @return Expr\Func
     */
    public function some($subquery)
    {
        return new Expr\Func('SOME', array($subquery));
    }

    /**
     * Creates an instance of ANY() function, with the given DQL subquery.
     *
     * @param mixed $subquery DQL Subquery to be used in ANY() function.
     * @return Expr\Func
     */
    public function any($subquery)
    {
        return new Expr\Func('ANY', array($subquery));
    }

    /**
     * Creates an instance of NOT() function, with the given restriction.
     *
     * @param mixed $restriction Restriction to be used in NOT() function.
     * @return Expr\Func
     */
    public function not($restriction)
    {
        return new Expr\Func('NOT', array($restriction));
    }

    /**
     * Creates an instance of ABS() function, with the given argument.
     *
     * @param mixed $x Argument to be used in ABS() function.
     * @return Expr\Func
     */
    public function abs($x)
    {
        return new Expr\Func('ABS', array($x));
    }

    /**
     * Creates a product mathematical expression with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> * <right expr>. Example:
     *
     *     [php]
     *     // u.salary * u.percentAnualSalaryIncrease
     *     $q->expr()->prod('u.salary', 'u.percentAnualSalaryIncrease')
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Expr\Math
     */
    public function prod($x, $y)
    {
        return new Expr\Math($x, '*', $y);
    }

    /**
     * Creates a difference mathematical expression with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> - <right expr>. Example:
     *
     *     [php]
     *     // u.monthlySubscriptionCount - 1
     *     $q->expr()->diff('u.monthlySubscriptionCount', '1')
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Expr\Math
     */
    public function diff($x, $y)
    {
        return new Expr\Math($x, '-', $y);
    }

    /**
     * Creates a sum mathematical expression with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> + <right expr>. Example:
     *
     *     [php]
     *     // u.numChildren + 1
     *     $q->expr()->diff('u.numChildren', '1')
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Expr\Math
     */
    public function sum($x, $y)
    {
        return new Expr\Math($x, '+', $y);
    }

    /**
     * Creates a quotient mathematical expression with the given arguments.
     * First argument is considered the left expression and the second is the right expression.
     * When converted to string, it will generated a <left expr> / <right expr>. Example:
     *
     *     [php]
     *     // u.total - u.period
     *     $q->expr()->diff('u.total', 'u.period')
     *
     * @param mixed $x Left expression
     * @param mixed $y Right expression
     * @return Expr\Math
     */
    public function quot($x, $y)
    {
        return new Expr\Math($x, '/', $y);
    }

    /**
     * Creates an instance of SQRT() function, with the given argument.
     *
     * @param mixed $x Argument to be used in SQRT() function.
     * @return Expr\Func
     */
    public function sqrt($x)
    {
        return new Expr\Func('SQRT', array($x));
    }

    /**
     * Creates an instance of field IN() function, with the given arguments.
     *
     * @param string $x Field in string format to be restricted by IN() function
     * @param mixed $y Argument to be used in IN() function.
     * @return Expr\Func
     */
    public function in($x, $y)
    {
        return new Expr\Func($x . ' IN', (array) $y);
    }

    /**
     * Creates an instance of field NOT IN() function, with the given arguments.
     *
     * @param string $x Field in string format to be restricted by NOT IN() function
     * @param mixed $y Argument to be used in NOT IN() function.
     * @return Expr\Func
     */
    public function notIn($x, $y)
    {
        return new Expr\Func($x . ' NOT IN', (array) $y);
    }

    /**
     * Creates an instance of field LIKE() comparison, with the given arguments.
     *
     * @param string $x Field in string format to be inspected by LIKE() comparison.
     * @param mixed $y Argument to be used in LIKE() comparison.
     * @return Expr\Comparison
     */
    public function like($x, $y)
    {
        return new Expr\Comparison($x, 'LIKE', $y);
    }

    /**
     * Creates an instance of CONCAT() function, with the given argument.
     *
     * @param mixed $x First argument to be used in CONCAT() function.
     * @param mixed $x Second argument to be used in CONCAT() function.
     * @return Expr\Func
     */
    public function concat($x, $y)
    {
        return new Expr\Func('CONCAT', array($x, $y));
    }

    /**
     * Creates an instance of SUBSTR() function, with the given argument.
     *
     * @param mixed $x Argument to be used as string to be cropped by SUBSTR() function.
     * @param integer $from Initial offset to start cropping string. May accept negative values.
     * @param integer $len Length of crop. May accept negative values.
     * @return Expr\Func
     */
    public function substr($x, $from, $len)
    {
        return new Expr\Func('SUBSTR', array($x, $from, $len));
    }

    /**
     * Creates an instance of LOWER() function, with the given argument.
     *
     * @param mixed $x Argument to be used in LOWER() function.
     * @return Expr\Func
     */
    public function lower($x)
    {
        return new Expr\Func('LOWER', array($x));
    }

    /**
     * Creates an instance of LOWER() function, with the given argument.
     *
     * @param mixed $x Argument to be used in LOWER() function.
     * @return Expr\Func
     */
    public function upper($x)
    {
        return new Expr\Func('UPPER', array($x));
    }

    /**
     * Creates an instance of LENGTH() function, with the given argument.
     *
     * @param mixed $x Argument to be used as argument of LENGTH() function.
     * @return Expr\Func
     */
    public function length($x)
    {
        return new Expr\Func('LENGTH', array($x));
    }

    /**
     * Creates a literal representation of the given argument.
     *
     * @param mixed $literal Argument to be converted to literal.
     * @return string
     */
    public function literal($literal)
    {
        if (is_numeric($literal)) {
            return (string) $literal;
        } else {
            return "'" . $literal . "'";
        }
    }

    /**
     * Creates an instance of BETWEEN() function, with the given argument.
     *
     * @param mixed $val Valued to be inspected by range values.
     * @param integer $x Starting range value to be used in BETWEEN() function.
     * @param integer $y End point value to be used in BETWEEN() function.
     * @return Expr\Func
     */
    public function between($val, $x, $y)
    {
        return new Expr\Func('BETWEEN', array($val, $x, $y));
    }

    /**
     * Creates an instance of TRIM() function, with the given argument.
     *
     * @param mixed $x Argument to be used as argument of TRIM() function.
     * @return Expr\Func
     */
    public function trim($x)
    {
        return new Expr\Func('TRIM', $x);
    }
}
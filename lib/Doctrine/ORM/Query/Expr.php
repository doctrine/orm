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
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class Expr
{
    public static function andx($x = null)
    {
        return new Expr\Andx(func_get_args());
    }

    public static function orx($x = null)
    {
        return new Expr\Orx(func_get_args());
    }

    public static function select($select = null)
    {
        return new Expr\Select(func_get_args());
    }
    
    public static function from($from, $alias = null)
    {
        return new Expr\From($from, $alias);
    }
    
    public static function leftJoin($join, $alias = null, $conditionType = null, $condition = null)
    {
        return new Expr\Join(Expr\Join::LEFT_JOIN, $join, $alias, $conditionType, $condition);
    }
    
    public static function innerJoin($join, $alias = null, $conditionType = null, $condition = null)
    {
        return new Expr\Join(Expr\Join::INNER_JOIN, $join, $alias, $conditionType, $condition);
    }

    public static function orderBy($sort = null, $order = null)
    {
        return new Expr\OrderBy($sort, $order);
    }

    public static function groupBy($groupBy = null)
    {
        return new Expr\GroupBy(func_get_args());
    }

    public static function eq($x, $y)
    {
        return new Expr\Comparison($x, Expr\Comparison::EQ, $y);
    }

    public static function neq($x, $y)
    {
        return new Expr\Comparison($x, Expr\Comparison::NEQ, $y);
    }

    public static function lt($x, $y)
    {
        return new Expr\Comparison($x, Expr\Comparison::LT, $y);
    }

    public static function lte($x, $y)
    {
        return new Expr\Comparison($x, Expr\Comparison::LTE, $y);
    }

    public static function gt($x, $y)
    {
        return new Expr\Comparison($x, Expr\Comparison::GT, $y);
    }

    public static function gte($x, $y)
    {
        return new Expr\Comparison($x, Expr\Comparison::GTE, $y);
    }

    public static function avg($x)
    {
        return new Expr\Func('AVG', array($x));
    }

    public static function max($x)
    {
        return new Expr\Func('MAX', array($x));
    }

    public static function min($x)
    {
        return new Expr\Func('MIN', array($x));
    }

    public static function count($x)
    {
        return new Expr\Func('COUNT', array($x));
    }

    public static function countDistinct($x)
    {
        return 'COUNT(DISTINCT ' . implode(', ', func_get_args()) . ')';
    }

    public static function exists($subquery)
    {
        return new Expr\Func('EXISTS', array($subquery));
    }

    public static function all($subquery)
    {
        return new Expr\Func('ALL', array($subquery));
    }

    public static function some($subquery)
    {
        return new Expr\Func('SOME', array($subquery));
    }

    public static function any($subquery)
    {
        return new Expr\Func('ANY', array($subquery));
    }

    public static function not($restriction)
    {
        return new Expr\Func('NOT', array($restriction));
    }

    public static function abs($x)
    {
        return new Expr\Func('ABS', array($x));
    }

    public static function prod($x, $y)
    {
        return new Expr\Math($x, '*', $y);
    }

    public static function diff($x, $y)
    {
        return new Expr\Math($x, '-', $y);
    }

    public static function sum($x, $y)
    {
        return new Expr\Math($x, '+', $y);
    }

    public static function quot($x, $y)
    {
        return new Expr\Math($x, '/', $y);
    }

    public static function sqrt($x)
    {
        return new Expr\Func('SQRT', array($x));
    }

    public static function in($x, $y)
    {
        return new Expr\Func($x . ' IN', (array) $y);
    }

    public static function notIn($x, $y)
    {
        return new Expr\Func($x . ' NOT IN', (array) $y);
    }

    public static function like($x, $y)
    {
        return new Expr\Comparison($x, 'LIKE', $y);
    }

    public static function concat($x, $y)
    {
        return new Expr\Func('CONCAT', array($x, $y));
    }

    public static function substr($x, $from, $len)
    {
        return new Expr\Func('SUBSTR', array($x, $from, $len));
    }

    public static function lower($x)
    {
        return new Expr\Func('LOWER', array($x));
    }

    public static function upper($x)
    {
        return new Expr\Func('UPPER', array($x));
    }

    public static function length($x)
    {
        return new Expr\Func('LENGTH', array($x));
    }

    public static function literal($literal)
    {
        if (is_numeric($literal)) {
            return (string) $literal;
        } else {
            return "'" . $literal . "'";
        }
    }

    public static function between($val, $x, $y)
    {
        return new Expr\Func('BETWEEN', array($val, $x, $y));
    }

    public static function trim($x)
    {
        return new Expr\Func('TRIM', $x);
    }
}
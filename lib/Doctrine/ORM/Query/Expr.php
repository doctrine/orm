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
    private static $_methodMap = array(
        'avg' => '_avgExpr',
        'max' => '_maxExpr',
        'min' => '_minExpr',
        'count' => '_countExpr',
        'countDistinct' => '_countDistinctExpr',
        'exists' => '_existsExpr',
        'all' => '_allExpr',
        'some' => '_someExpr',
        'any' => '_anyExpr',
        'not' => '_notExpr',
        'and' => '_andExpr',
        'or' => '_orExpr',
        'abs' => '_absExpr',
        'prod' => '_prodExpr',
        'diff' => '_diffExpr',
        'sum' => '_sumExpr',
        'quot' => '_quotientExpr',
        'sqrt' => '_squareRootExpr',
        'eq' => '_equalExpr',
        'in' => '_inExpr',
        'notIn' => '_notInExpr',
        'notEqual' => '_notEqualExpr',
        'like' => '_likeExpr',
        'concat' => '_concatExpr',
        'substr' => '_substrExpr',
        'lower' => '_lowerExpr',
        'upper' => '_upperExpr',
        'length' => '_lengthExpr',
        'gt' => '_greaterThanExpr',
        'lt' => '_lessThanExpr',
        'path' => '_pathExpr',
        'literal' => '_literalExpr',
        'gtoet' => '_greaterThanOrEqualToExpr',
        'ltoet' => '_lessThanOrEqualToExpr',
        'between' => '_betweenExpr',
        'trim' => '_trimExpr',
        'on' => '_onExpr',
        'with' => '_withExpr',
        'from' => '_fromExpr',
        'innerJoin' => '_innerJoinExpr',
        'leftJoin' => '_leftJoinExpr'
    );

    private $_type;
    private $_parts;

    protected function __construct($type, array $parts)
    {
        $this->_type = $type;
        $this->_parts = $parts;
    }

    public function getDql()
    {
        return $this->{self::$_methodMap[$this->_type]}();
    }

    public function __toString()
    {
        return $this->getDql();
    }

    private function _avgExpr()
    {
        return 'AVG(' . $this->_parts[0] . ')';
    }

    private function _maxExpr()
    {
        return 'MAX(' . $this->_parts[0] . ')';
    }

    private function _minExpr()
    {
        return 'MIN(' . $this->_parts[0] . ')';
    }

    private function _countExpr()
    {
        return 'COUNT(' . $this->_parts[0] . ')';
    }

    private function _countDistinctExpr()
    {
        return 'COUNT(DISTINCT ' . $this->_parts[0] . ')';
    }

    private function _existsExpr()
    {
        return 'EXISTS(' . $this->_parts[0] . ')';
    }

    private function _allExpr()
    {
        return 'ALL(' . $this->_parts[0] . ')';
    }

    private function _someExpr()
    {
        return 'SOME(' . $this->_parts[0] . ')';
    }

    private function _anyExpr()
    {
        return 'ANY(' . $this->_parts[0] . ')';
    }

    private function _notExpr()
    {
        return 'NOT(' . $this->_parts[0] . ')';
    }

    private function _andExpr()
    {
        return '(' . $this->_parts[0] . ' AND ' . $this->_parts[1] . ')';
    }

    private function _orExpr()
    {
        return '(' . $this->_parts[0] . ' OR ' . $this->_parts[1] . ')';
    }

    private function _absExpr()
    {
        return 'ABS(' . $this->_parts[0] . ')';
    }

    private function _prodExpr()
    {
        return '(' . $this->_parts[0] . ' * ' . $this->_parts[1] . ')';
    }

    private function _diffExpr()
    {
        return '(' . $this->_parts[0] . ' - ' . $this->_parts[1] . ')';
    }

    private function _sumExpr()
    {
        return '(' . $this->_parts[0] . ' + ' . $this->_parts[1] . ')';
    }

    private function _quotientExpr()
    {
        return '(' . $this->_parts[0] . ' / ' . $this->_parts[1] . ')';
    }

    private function _squareRootExpr()
    {
        return 'SQRT(' . $this->_parts[0] . ')';
    }

    private function _equalExpr()
    {
        return $this->_parts[0] . ' = ' . $this->_parts[1];
    }

    private function _inExpr()
    {
        return $this->_parts[0] . ' IN(' . implode(', ', $this->_parts[1]) . ')';
    }

    private function _notInExpr()
    {
        return $this->_parts[0] . ' NOT IN(' . implode(', ', $this->_parts[1]) . ')';
    }

    private function _notEqualExpr()
    {
        return $this->_parts[0] . ' != ' . $this->_parts[1];
    }

    private function _likeExpr()
    {
        // TODO: How should we use $escapeChar which is in $this->_parts[2]
        return '(' . $this->_parts[0] . ' LIKE ' . $this->_parts[1] . ')';
    }

    private function _concatExpr()
    {
        return 'CONCAT(' . $this->_parts[0] . ', ' . $this->_parts[1] . ')';
    }

    private function _substrExpr()
    {
        return 'SUBSTR(' . $this->_parts[0] . ', ' . $this->_parts[1] . ', ' . $this->_parts[2] . ')';
    }

    private function _lowerExpr()
    {
        return 'LOWER(' . $this->_parts[0] . ')';
    }

    private function _upperExpr()
    {
        return 'UPPER(' . $this->_parts[0] . ')';
    }

    private function _lengthExpr()
    {
        return 'LENGTH(' . $this->_parts[0] . ')';
    }

    private function _greaterThanExpr()
    {
        return $this->_parts[0] . ' > ' . $this->_parts[1];
    }

    private function _lessThanExpr()
    {
        return $this->_parts[0] . ' < ' . $this->_parts[1];
    }

    private function _pathExpr()
    {
        // TODO: What is this?
    }

    private function _literalExpr()
    {
        if (is_numeric($this->_parts[0])) {
            return (string) $this->_parts[0];
        } else {
            return "'" . $this->_parts[0] . "'";
        }
    }

    private function _greaterThanOrEqualToExpr()
    {
        return $this->_parts[0] . ' >= ' . $this->_parts[1];
    }

    private function _lessThanOrEqualToExpr()
    {
        return $this->_parts[0] . ' <= ' . $this->_parts[1];
    }

    private function _betweenExpr()
    {
        return 'BETWEEN(' . $this->_parts[0] . ', ' . $this->_parts[1] . ', ' . $this->_parts[2] . ')';
    }

    private function _ltExpr()
    {
        return '(' . $this->_parts[0] . ' < ' . $this->_parts[1] . ')';
    }

    private function _trimExpr()
    {
        return 'TRIM(' . $this->_parts[0] . ')';
    }

    private function _onExpr()
    {
        return 'ON ' . $this->_parts[0];
    }

    private function _withExpr()
    {
        return 'WITH ' . $this->_parts[0];
    }

    private function _fromExpr()
    {
        return $this->_parts[0] . ' ' . $this->_parts[1];
    }

    private function _leftJoinExpr()
    {
        return 'LEFT JOIN ' . $this->_parts[0] . '.' . $this->_parts[1] . ' '
        . $this->_parts[2] . (isset($this->_parts[3]) ? ' ' . $this->_parts[3] : null);
    }

    private function _innerJoinExpr()
    {
        return 'INNER JOIN ' . $this->_parts[0] . '.' . $this->_parts[1] . ' '
        . $this->_parts[2] . (isset($this->_parts[3]) ? ' ' . $this->_parts[3] : null);
    }

    public static function avg($x)
    {
        return new self('avg', array($x));
    }

    public static function max($x)
    {
        return new self('max', array($x));
    }

    public static function min($x)
    {
        return new self('min', array($x));
    }

    public static function count($x)
    {
        return new self('count', array($x));
    }

    public static function countDistinct($x)
    {
        return new self('countDistinct', array($x));
    }

    public static function exists($subquery)
    {
        return new self('exists', array($subquery));
    }

    public static function all($subquery)
    {
        return new self('all', array($subquery));
    }

    public static function some($subquery)
    {
        return new self('some', array($subquery));
    }

    public static function any($subquery)
    {
        return new self('any', array($subquery));
    }

    public static function not($restriction)
    {
        return new self('not', array($restriction));
    }

    public static function andx($x, $y)
    {
        return new self('and', array($x, $y));
    }

    public static function orx($x, $y)
    {
        return new self('or', array($x, $y));
    }

    public static function abs($x)
    {
        return new self('abs', array($x));
    }

    public static function prod($x, $y)
    {
        return new self('prod', array($x, $y));
    }

    public static function diff($x, $y)
    {
        return new self('diff', array($x, $y));
    }

    public static function sum($x, $y)
    {
        return new self('sum', array($x, $y));
    }

    public static function quot($x, $y)
    {
        return new self('quot', array($x, $y));
    }

    public static function sqrt($x)
    {
        return new self('sqrt', array($x));
    }

    public static function eq($x, $y)
    {
        return new self('eq', array($x, $y));
    }

    public static function in($x, $y)
    {
        return new self('in', array($x, $y));
    }

    public static function notIn($x, $y)
    {
        return new self('notIn', array($x, $y));
    }

    public static function notEqual($x, $y)
    {
        return new self('notEqual', array($x, $y));
    }

    public static function like($x, $pattern, $escapeChar = null)
    {
        return new self('like', array($x, $pattern, $escapeChar));
    }

    public static function concat($x, $y)
    {
        return new self('concat', array($x, $y));
    }

    public static function substr($x, $from = null, $len = null)
    {
        return new self('substr', array($x, $from, $len));
    }

    public static function lower($x)
    {
        return new self('lower', array($x));
    }

    public static function upper($x)
    {
        return new self('upper', array($x));
    }

    public static function length($x)
    {
        return new self('length', array($x));
    }

    public static function gt($x, $y)
    {
        return new self('gt', array($x, $y));
    }

    public static function greaterThan($x, $y)
    {
        return new self('gt', array($x, $y));
    }

    public static function lt($x, $y)
    {
        return new self('lt', array($x, $y));
    }

    public static function lessThan($x, $y)
    {
        return new self('lt', array($x, $y));
    }

    public static function path($path)
    {
        return new self('path', array($path));
    }

    public static function literal($literal)
    {
        return new self('literal', array($literal));
    }

    public static function greaterThanOrEqualTo($x, $y)
    {
        return new self('gtoet', array($x, $y));
    }

    public static function gtoet($x, $y)
    {
        return new self('gtoet', array($x, $y));
    }

    public static function lessThanOrEqualTo($x, $y)
    {
        return new self('ltoet', array($x, $y));
    }

    public static function ltoet($x, $y)
    {
        return new self('ltoet', array($x, $y));
    }

    public static function between($val, $x, $y)
    {
        return new self('between', array($val, $x, $y));
    }

    public static function trim($val, $spec = null, $char = null)
    {
        return new self('trim', array($val, $spec, $char));
    }

    public static function on($x)
    {
        return new self('on', array($x));
    }

    public static function with($x)
    {
        return new self('with', array($x));
    }

    public static function from($from, $alias)
    {
        return new self('from', array($from, $alias));
    }

    public static function leftJoin($parentAlias, $join, $alias, $condition = null)
    {
        return new self('leftJoin', array($parentAlias, $join, $alias, $condition));
    }

    public static function innerJoin($parentAlias, $join, $alias, $condition = null)
    {
        return new self('innerJoin', array($parentAlias, $join, $alias, $condition));
    }
}
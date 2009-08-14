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

namespace Doctrine\Tests\ORM\Query;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query;

require_once __DIR__ . '/../../TestInit.php';

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
class ExprTest extends \Doctrine\Tests\OrmTestCase
{
    private $_em;

    protected function setUp()
    {
        $this->_em = $this->_getTestEntityManager();
    }

    public function testAvgExpr()
    {
        $this->assertEquals('AVG(u.id)', (string) Expr::avg('u.id'));
    }

    public function testMaxExpr()
    {
        $this->assertEquals('MAX(u.id)', (string) Expr::max('u.id'));
    }

    public function testMinExpr()
    {
        $this->assertEquals('MIN(u.id)', (string) Expr::min('u.id'));
    }

    public function testCountExpr()
    {
        $this->assertEquals('MAX(u.id)', (string) Expr::max('u.id'));
    }

    public function testCountDistinctExpr()
    {
        $this->assertEquals('COUNT(DISTINCT u.id)', (string) Expr::countDistinct('u.id'));
    }

    public function testExistsExpr()
    {
        $this->assertEquals('EXISTS(SUBQUERY)', (string) Expr::exists('SUBQUERY'));
    }

    public function testAllExpr()
    {
        $this->assertEquals('ALL(SUBQUERY)', (string) Expr::all('SUBQUERY'));
    }

    public function testSomeExpr()
    {
        $this->assertEquals('SOME(SUBQUERY)', (string) Expr::some('SUBQUERY'));
    }

    public function testAnyExpr()
    {
        $this->assertEquals('ANY(SUBQUERY)', (string) Expr::any('SUBQUERY'));
    }

    public function testNotExpr()
    {
        $this->assertEquals('NOT(SUBQUERY)', (string) Expr::not('SUBQUERY'));
    }

    public function testAndExpr()
    {
        $this->assertEquals('(1 = 1) AND (2 = 2)', (string) Expr::andx((string) Expr::eq(1, 1), (string) Expr::eq(2, 2)));
    }

    public function testOrExpr()
    {
        $this->assertEquals('(1 = 1) OR (2 = 2)', (string) Expr::orx((string) Expr::eq(1, 1), (string) Expr::eq(2, 2)));
    }

    public function testAbsExpr()
    {
        $this->assertEquals('ABS(1)', (string) Expr::abs(1));
    }

    public function testProdExpr()
    {
        $this->assertEquals('1 * 2', (string) Expr::prod(1, 2));
    }

    public function testDiffExpr()
    {
        $this->assertEquals('1 - 2', (string) Expr::diff(1, 2));
    }

    public function testSumExpr()
    {
        $this->assertEquals('1 + 2', (string) Expr::sum(1, 2));
    }

    public function testQuotientExpr()
    {
        $this->assertEquals('10 / 2', (string) Expr::quot(10, 2));
    }

    public function testSquareRootExpr()
    {
        $this->assertEquals('SQRT(1)', (string) Expr::sqrt(1));
    }

    public function testEqualExpr()
    {
        $this->assertEquals('1 = 1', (string) Expr::eq(1, 1));
    }

    public function testLikeExpr()
    {
        $this->assertEquals('a.description LIKE :description', (string) Expr::like('a.description', ':description'));
    }

    public function testConcatExpr()
    {
        $this->assertEquals('CONCAT(u.first_name, u.last_name)', (string) Expr::concat('u.first_name', 'u.last_name'));
    }

    public function testSubstrExpr()
    {
        $this->assertEquals('SUBSTR(a.title, 0, 25)', (string) Expr::substr('a.title', 0, 25));
    }

    public function testLowerExpr()
    {
        $this->assertEquals('LOWER(u.first_name)', (string) Expr::lower('u.first_name'));
    }

    public function testUpperExpr()
    {
        $this->assertEquals('UPPER(u.first_name)', (string) Expr::upper('u.first_name'));
    }

    public function testLengthExpr()
    {
        $this->assertEquals('LENGTH(u.first_name)', (string) Expr::length('u.first_name'));
    }

    public function testGreaterThanExpr()
    {
        $this->assertEquals('5 > 2', (string) Expr::gt(5, 2));
    }

    public function testLessThanExpr()
    {
        $this->assertEquals('2 < 5', (string) Expr::lt(2, 5));
    }

    public function testStringLiteralExpr()
    {
        $this->assertEquals("'word'", (string) Expr::literal('word'));
    }

    public function testNumericLiteralExpr()
    {
        $this->assertEquals(5, (string) Expr::literal(5));
    }

    public function testGreaterThanOrEqualToExpr()
    {
        $this->assertEquals('5 >= 2', (string) Expr::gte(5, 2));
    }

    public function testLessThanOrEqualTo()
    {
        $this->assertEquals('2 <= 5', (string) Expr::lte(2, 5));
    }

    public function testBetweenExpr()
    {
        $this->assertEquals('BETWEEN(u.id, 3, 6)', (string) Expr::between('u.id', 3, 6));
    }

    public function testTrimExpr()
    {
        $this->assertEquals('TRIM(u.id)', (string) Expr::trim('u.id'));
    }

    public function testInExpr()
    {
        $this->assertEquals('u.id IN(1, 2, 3)', (string) Expr::in('u.id', array(1, 2, 3)));
    }

    public function testAndxOrxExpr()
    {
        $andExpr = Expr::andx();
        $andExpr->add(Expr::eq(1, 1));
        $andExpr->add(Expr::lt(1, 5));

        $orExpr = Expr::orx();
        $orExpr->add($andExpr);
        $orExpr->add(Expr::eq(1, 1));

        $this->assertEquals('((1 = 1) AND (1 < 5)) OR (1 = 1)', (string) $orExpr);
    }

    public function testOrxExpr()
    {
        $orExpr = Expr::orx();
        $orExpr->add(Expr::eq(1, 1));
        $orExpr->add(Expr::lt(1, 5));

        $this->assertEquals('(1 = 1) OR (1 < 5)', (string) $orExpr);
    }

    public function testSelectExpr()
    {
        $selectExpr = Expr::select();
        $selectExpr->add('u.id');
        $selectExpr->add('u.username');

        $this->assertEquals('u.id, u.username', (string) $selectExpr);
    }

    public function testExprBaseCount()
    {
        $selectExpr = Expr::select();
        $selectExpr->add('u.id');
        $selectExpr->add('u.username');

        $this->assertEquals($selectExpr->count(), 2);
    }

    public function testOrderByCountExpr()
    {
        $orderByExpr = Expr::orderBy();
        $orderByExpr->add('u.username', 'DESC');

        $this->assertEquals($orderByExpr->count(), 1);
        $this->assertEquals('u.username DESC', (string) $orderByExpr);
    }

    public function testOrderByOrder()
    {
        $orderByExpr = Expr::orderBy('u.username', 'DESC');
        $this->assertEquals('u.username DESC', (string) $orderByExpr);
    }

    public function testOrderByDefaultOrderIsAsc()
    {
        $orderByExpr = Expr::orderBy('u.username');
        $this->assertEquals('u.username ASC', (string) $orderByExpr);
    }

    /**
     * @expectedException Doctrine\Common\DoctrineException
     */
    public function testAddThrowsException()
    {
        $orExpr = Expr::orx();
        $orExpr->add(Expr::quot(5, 2));
    }
}
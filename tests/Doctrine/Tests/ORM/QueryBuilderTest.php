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

namespace Doctrine\Tests\ORM;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Expr;

require_once __DIR__ . '/../TestInit.php';

/**
 * Test case for the QueryBuilder class used to build DQL query string in a
 * object oriented way.
 *
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 */
class QueryBuilderTest extends \Doctrine\Tests\OrmTestCase
{
    private $_em;

    protected function setUp()
    {
        $this->_em = $this->_getTestEntityManager();
    }

    protected function assertValidQueryBuilder(QueryBuilder $qb, $expectedDql)
    {
        $dql = $qb->getDql();
        $q = $qb->getQuery();

        try {
            $q->getSql();
        } catch (\Exception $e) {
            echo $dql . "\n";
            echo $e->getTraceAsString();
            $this->fail($e->getMessage());
        }

        $this->assertEquals($expectedDql, $dql);
    }

    public function testSimpleSelect()
    {
        $qb = QueryBuilder::create($this->_em)
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->select('u.id, u.username');

        $this->assertValidQueryBuilder($qb, 'SELECT u.id, u.username FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testSimpleDelete()
    {
        $qb = QueryBuilder::create($this->_em)
            ->delete('Doctrine\Tests\Models\CMS\CmsUser', 'u');

        $this->assertValidQueryBuilder($qb, 'DELETE Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testSimpleUpdate()
    {
        $qb = QueryBuilder::create($this->_em)
            ->update('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->set('u.username', ':username', 'jonwage');

        $this->assertValidQueryBuilder($qb, 'UPDATE Doctrine\Tests\Models\CMS\CmsUser u SET u.username = :username');
    }

    public function testJoin()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u, a')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->join('u.articles', 'a');

        $this->assertValidQueryBuilder($qb, 'SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles a');
    }

    public function testInnerJoin()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u, a')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->innerJoin('u.articles', 'a');

        $this->assertValidQueryBuilder($qb, 'SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles a');
    }

    public function testLeftJoin()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u, a')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->leftJoin('u.articles', 'a');

        $this->assertValidQueryBuilder($qb, 'SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a');
    }

    public function testWhere()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :uid')
            ->where('u.id = :id');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :id');
    }

    public function testAndWhere()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :id')
            ->andWhere('u.username = :username');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :id AND u.username = :username');
    }

    public function testOrWhere()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :id')
            ->orWhere('u.username = :username');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :id OR u.username = :username');
    }

    /*
    public function testWhereIn()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->whereIn('u.id', array(1));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id IN(1)');
    }

    public function testWhereNotIn()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->whereNotIn('u.id', array(1));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id NOT IN(1)');
    }

    public function testAndWhereIn()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :id')
            ->andWhereIn('u.id', array(1, 2, 3));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :id AND u.id IN(1, 2, 3)');
    }

    public function testAndWhereNotIn()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :id')
            ->andWhereNotIn('u.id', array(1, 2, 3));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :id AND u.id NOT IN(1, 2, 3)');
    }

    public function testOrWhereIn()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :id')
            ->orWhereIn('u.id', array(1, 2, 3));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :id OR u.id IN(1, 2, 3)');
    }

    public function testOrWhereNotIn()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :id')
            ->orWhereNotIn('u.id', array(1, 2, 3));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :id OR u.id NOT IN(1, 2, 3)');
    }
    */

    public function testGroupBy()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->groupBy('u.id');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id');
    }

    public function testHaving()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->groupBy('u.id')
            ->having('COUNT(u.id) > 1');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id HAVING COUNT(u.id) > 1');
    }

    public function testAndHaving()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->groupBy('u.id')
            ->having('COUNT(u.id) > 1')
            ->andHaving('COUNT(u.id) < 1');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id HAVING COUNT(u.id) > 1 AND COUNT(u.id) < 1');
    }

    public function testOrHaving()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->groupBy('u.id')
            ->having('COUNT(u.id) > 1')
            ->andHaving('COUNT(u.id) < 1')
            ->orHaving('COUNT(u.id) > 1');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id HAVING COUNT(u.id) > 1 AND COUNT(u.id) < 1 OR COUNT(u.id) > 1');
    }

    public function testOrderBy()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->orderBy('u.username', 'ASC');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username ASC');
    }

    public function testAddOrderBy()
    {
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->orderBy('u.username', 'ASC')
            ->addOrderBy('u.username', 'DESC');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username ASC, u.username DESC');
    }

    public function testLimit()
    {
        /*
        TODO: Limit fails. Is this not implemented in the DQL parser? Will look tomorrow.
        $qb = QueryBuilder::create($this->_em)
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->limit(10)
            ->offset(0);

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u LIMIT 10');
        */
    }
}
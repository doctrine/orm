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

use Doctrine\ORM\QueryBuilder,
    Doctrine\ORM\Query\Expr;

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

        $this->assertEquals($expectedDql, $dql);
    }

    public function testSelectSetsType()
    {
        $qb = $this->_em->createQueryBuilder()
            ->delete('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->select('u.id', 'u.username');

        $this->assertEquals($qb->getType(), QueryBuilder::SELECT);
    }

    public function testEmptySelectSetsType()
    {
        $qb = $this->_em->createQueryBuilder()
            ->delete('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->select();

        $this->assertEquals($qb->getType(), QueryBuilder::SELECT);
    }

    public function testDeleteSetsType()
    {
        $qb = $this->_em->createQueryBuilder()
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->delete();

        $this->assertEquals($qb->getType(), QueryBuilder::DELETE);
    }

    public function testUpdateSetsType()
    {
        $qb = $this->_em->createQueryBuilder()
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->update();

        $this->assertEquals($qb->getType(), QueryBuilder::UPDATE);
    }

    public function testSimpleSelect()
    {
        $qb = $this->_em->createQueryBuilder()
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->select('u.id', 'u.username');

        $this->assertValidQueryBuilder($qb, 'SELECT u.id, u.username FROM Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testSimpleDelete()
    {
        $qb = $this->_em->createQueryBuilder()
            ->delete('Doctrine\Tests\Models\CMS\CmsUser', 'u');

        $this->assertValidQueryBuilder($qb, 'DELETE Doctrine\Tests\Models\CMS\CmsUser u');
    }

    public function testSimpleUpdate()
    {
        $qb = $this->_em->createQueryBuilder()
            ->update('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->set('u.username', ':username');

        $this->assertValidQueryBuilder($qb, 'UPDATE Doctrine\Tests\Models\CMS\CmsUser u SET u.username = :username');
    }

    public function testInnerJoin()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u', 'a')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->innerJoin('u.articles', 'a');

        $this->assertValidQueryBuilder($qb, 'SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles a');
    }
    
    public function testComplexInnerJoin()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u', 'a')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->innerJoin('u.articles', 'a', 'ON', 'u.id = a.author_id');

        $this->assertValidQueryBuilder(
            $qb, 
            'SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u INNER JOIN u.articles a ON u.id = a.author_id'
        );
    }

    public function testLeftJoin()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u', 'a')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->leftJoin('u.articles', 'a');

        $this->assertValidQueryBuilder($qb, 'SELECT u, a FROM Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a');
    }

    public function testWhere()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :uid');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = :uid');
    }

    public function testAndWhere()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :uid')
            ->andWhere('u.id = :uid2');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid) AND (u.id = :uid2)');
    }

    public function testOrWhere()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :uid')
            ->orWhere('u.id = :uid2');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid) OR (u.id = :uid2)');
    }

    public function testAndWhereIn()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
           ->where('u.id = :uid')
           ->andWhere($qb->expr()->in('u.id', array(1, 2, 3)));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid) AND (u.id IN(1, 2, 3))');
    }

    public function testOrWhereIn()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
           ->where('u.id = :uid')
           ->orWhere($qb->expr()->in('u.id', array(1, 2, 3)));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid) OR (u.id IN(1, 2, 3))');
    }

    public function testAndWhereNotIn()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
           ->where('u.id = :uid')
           ->andWhere($qb->expr()->notIn('u.id', array(1, 2, 3)));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid) AND (u.id NOT IN(1, 2, 3))');
    }

    public function testOrWhereNotIn()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
           ->where('u.id = :uid')
           ->orWhere($qb->expr()->notIn('u.id', array(1, 2, 3)));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid) OR (u.id NOT IN(1, 2, 3))');
    }

    public function testGroupBy()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->groupBy('u.id')
            ->addGroupBy('u.username');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id, u.username');
    }

    public function testHaving()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->groupBy('u.id')
            ->having('COUNT(u.id) > 1');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id HAVING COUNT(u.id) > 1');
    }

    public function testAndHaving()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->groupBy('u.id')
            ->having('COUNT(u.id) > 1')
            ->andHaving('COUNT(u.id) < 1');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id HAVING (COUNT(u.id) > 1) AND (COUNT(u.id) < 1)');
    }

    public function testOrHaving()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->groupBy('u.id')
            ->having('COUNT(u.id) > 1')
            ->andHaving('COUNT(u.id) < 1')
            ->orHaving('COUNT(u.id) > 1');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id HAVING ((COUNT(u.id) > 1) AND (COUNT(u.id) < 1)) OR (COUNT(u.id) > 1)');
    }

    public function testOrderBy()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->orderBy('u.username', 'ASC');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username ASC');
    }
    
    public function testOrderByWithExpression()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->orderBy($qb->expr()->asc('u.username'));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username ASC');
    }

    public function testAddOrderBy()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->orderBy('u.username', 'ASC')
            ->addOrderBy('u.username', 'DESC');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u ORDER BY u.username ASC, u.username DESC');
    }

    public function testGetQuery()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $q = $qb->getQuery();

        $this->assertEquals('Doctrine\ORM\Query', get_class($q));
    }

    public function testSetParameter()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :id')
            ->setParameter('id', 1);

        $this->assertEquals(array('id' => 1), $qb->getParameters());
    }

    public function testSetParameters()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
           ->where($qb->expr()->orx('u.username = :username', 'u.username = :username2'));

        $qb->setParameters(array('username' => 'jwage', 'username2' => 'jonwage'));

        $this->assertEquals(array('username' => 'jwage', 'username2' => 'jonwage'), $qb->getQuery()->getParameters());
    }


    public function testGetParameters()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
           ->where('u.id = :id');

        $qb->setParameters(array('id' => 1));
        $this->assertEquals(array('id' => 1), $qb->getParameters());
    }

    public function testGetParameter()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :id');

        $qb->setParameters(array('id' => 1));
        $this->assertEquals(1, $qb->getParameter('id'));
    }

    public function testMultipleWhere()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->where('u.id = :uid', 'u.id = :uid2');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid) AND (u.id = :uid2)');
    }

    public function testMultipleAndWhere()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
            ->andWhere('u.id = :uid', 'u.id = :uid2');

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid) AND (u.id = :uid2)');
    }

    public function testMultipleOrWhere()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
           ->orWhere('u.id = :uid', $qb->expr()->eq('u.id', ':uid2'));

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid) OR (u.id = :uid2)');
    }

    public function testComplexWhere()
    {
        $qb = $this->_em->createQueryBuilder();
        $orExpr = $qb->expr()->orX();
        $orExpr->add($qb->expr()->eq('u.id', ':uid3'));
        $orExpr->add($qb->expr()->in('u.id', array(1)));

        $qb->select('u')
           ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
           ->where($orExpr);

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid3) OR (u.id IN(1))');
    }
    
    public function testWhereInWithStringLiterals()
    {
        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
           ->where($qb->expr()->in('u.name', array('one', 'two', 'three')));
           
        $this->assertValidQueryBuilder($qb, "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name IN('one', 'two', 'three')");
        
        $qb->where($qb->expr()->in('u.name', array("O'Reilly", "O'Neil", 'Smith')));
           
        $this->assertValidQueryBuilder($qb, "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name IN('O''Reilly', 'O''Neil', 'Smith')");
    }
    
    public function testWhereInWithObjectLiterals()
    {
        $qb = $this->_em->createQueryBuilder();
        $expr = $this->_em->getExpressionBuilder();
        $qb->select('u')
           ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
           ->where($expr->in('u.name', array($expr->literal('one'), $expr->literal('two'), $expr->literal('three'))));
           
        $this->assertValidQueryBuilder($qb, "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name IN('one', 'two', 'three')");
        
        $qb->where($expr->in('u.name', array($expr->literal("O'Reilly"), $expr->literal("O'Neil"), $expr->literal('Smith'))));
           
        $this->assertValidQueryBuilder($qb, "SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name IN('O''Reilly', 'O''Neil', 'Smith')");
    }
    
    public function testNegation()
    {
        $expr = $this->_em->getExpressionBuilder();
        $orExpr = $expr->orX();
        $orExpr->add($expr->eq('u.id', ':uid3'));
        $orExpr->add($expr->not($expr->in('u.id', array(1))));

        $qb = $this->_em->createQueryBuilder();
        $qb->select('u')
           ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
           ->where($orExpr);

        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.id = :uid3) OR (NOT(u.id IN(1)))');
    }
    
    public function testSomeAllAny()
    {
        $qb = $this->_em->createQueryBuilder();
        $expr = $this->_em->getExpressionBuilder();

        //$subquery = $qb->subquery('Doctrine\Tests\Models\CMS\CmsArticle', 'a')->select('a.id');

        $qb->select('u')
           ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u')
           ->where($expr->gt('u.id', $expr->all('select a.id from Doctrine\Tests\Models\CMS\CmsArticle a')));
       
        $this->assertValidQueryBuilder($qb, 'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id > ALL(select a.id from Doctrine\Tests\Models\CMS\CmsArticle a)');

    }
    
    public function testMultipleIsolatedQueryConstruction()
    {
        $qb = $this->_em->createQueryBuilder();
        $expr = $this->_em->getExpressionBuilder();
        
        $qb->select('u')->from('Doctrine\Tests\Models\CMS\CmsUser', 'u');
        $qb->where($expr->eq('u.name', ':name'));
        $qb->setParameter('name', 'romanb');
        
        $q1 = $qb->getQuery();
        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name = :name', $q1->getDql());
        $this->assertEquals(1, count($q1->getParameters()));
        
        // add another condition and construct a second query
        $qb->andWhere($expr->eq('u.id', ':id'));
        $qb->setParameter('id', 42);
        
        $q2 = $qb->getQuery();
        
        $this->assertEquals('SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE (u.name = :name) AND (u.id = :id)', $q2->getDql());
        $this->assertTrue($q1 !== $q2); // two different, independent queries
        $this->assertEquals(2, count($q2->getParameters()));
        $this->assertEquals(1, count($q1->getParameters())); // $q1 unaffected
    }

    public function testGetEntityManager()
    {
        $qb = $this->_em->createQueryBuilder();
        $this->assertEquals($this->_em, $qb->getEntityManager());
    }

    public function testInitialStateIsClean()
    {
        $qb = $this->_em->createQueryBuilder();
        $this->assertEquals(QueryBuilder::STATE_CLEAN, $qb->getState());
    }

    public function testAlteringQueryChangesStateToDirty()
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('u')
            ->from('Doctrine\Tests\Models\CMS\CmsUser', 'u');

        $this->assertEquals(QueryBuilder::STATE_DIRTY, $qb->getState());
    }
}
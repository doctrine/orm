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

require_once __DIR__ . '/../../TestInit.php';

/**
 * Test case for testing the saving and referencing of query identifiers.
 *
 * @package     Doctrine
 * @subpackage  Query
 * @author      Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Janne Vanhala <jpvanhal@cc.hut.fi>
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        http://www.phpdoctrine.org
 * @since       2.0
 * @version     $Revision$
 * @todo        1) [romanb] We  might want to split the SQL generation tests into multiple
 *              testcases later since we'll have a lot of them and we might want to have special SQL
 *              generation tests for some dbms specific SQL syntaxes.
 */
class SelectSqlGenerationTest extends \Doctrine\Tests\OrmTestCase
{
    private $_em;

    protected function setUp() {
        $this->_em = $this->_getTestEntityManager();
    }

    public function assertSqlGeneration($dqlToBeTested, $sqlToBeConfirmed)
    {
        try {
            $query = $this->_em->createQuery($dqlToBeTested);
            parent::assertEquals($sqlToBeConfirmed, $query->getSql());
            $query->free();
        } catch (Doctrine_Exception $e) {
            echo $e->getMessage();
            echo $e->getTraceAsString(); die();
            $this->fail($e->getMessage());
        }
    }


    public function testPlainFromClauseWithoutAlias()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT c0.id AS c0__id, c0.status AS c0__status, c0.username AS c0__username, c0.name AS c0__name FROM cms_users c0'
        );

        $this->assertSqlGeneration(
            'SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT c0.id AS c0__id FROM cms_users c0'
        );
    }

    public function testSelectSingleComponentWithMultipleColumns()
    {
        $this->assertSqlGeneration(
            'SELECT u.username, u.name FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT c0.username AS c0__username, c0.name AS c0__name FROM cms_users c0'
        );
    }

    public function testSelectWithCollectionAssociationJoin()
    {
        $this->assertSqlGeneration(
            'SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.phonenumbers p',
            'SELECT c0.id AS c0__id, c0.status AS c0__status, c0.username AS c0__username, c0.name AS c0__name, c1.phonenumber AS c1__phonenumber FROM cms_users c0 INNER JOIN cms_phonenumbers c1 ON c0.id = c1.user_id'
        );
    }

    public function testSelectWithSingleValuedAssociationJoin()
    {
        $this->assertSqlGeneration(
            'SELECT u, a FROM Doctrine\Tests\Models\Forum\ForumUser u JOIN u.avatar a',
            'SELECT f0.id AS f0__id, f0.username AS f0__username, f1.id AS f1__id FROM forum_users f0 INNER JOIN forum_avatars f1 ON f0.avatar_id = f1.id'
        );
    }

    public function testSelectDistinctIsSupported()
    {
        $this->assertSqlGeneration(
            'SELECT DISTINCT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT DISTINCT c0.name AS c0__name FROM cms_users c0'
        );
    }

    public function testAggregateFunctionInSelect()
    {
        $this->assertSqlGeneration(
            'SELECT COUNT(u.id) FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id',
            'SELECT COUNT(c0.id) AS dctrn__0 FROM cms_users c0 GROUP BY c0.id'
        );
    }

    public function testWhereClauseInSelectWithPositionalParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where u.id = ?1',
            'SELECT f0.id AS f0__id, f0.username AS f0__username FROM forum_users f0 WHERE f0.id = ?'
        );
    }

    public function testWhereClauseInSelectWithNamedParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where u.username = :name',
            'SELECT f0.id AS f0__id, f0.username AS f0__username FROM forum_users f0 WHERE f0.username = :name'
        );
    }

    public function testWhereANDClauseInSelectWithNamedParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where u.username = :name and u.username = :name2',
            'SELECT f0.id AS f0__id, f0.username AS f0__username FROM forum_users f0 WHERE f0.username = :name AND f0.username = :name2'
        );
    }

    public function testCombinedWhereClauseInSelectWithNamedParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where (u.username = :name OR u.username = :name2) AND u.id = :id',
            'SELECT f0.id AS f0__id, f0.username AS f0__username FROM forum_users f0 WHERE (f0.username = :name OR f0.username = :name2) AND f0.id = :id'
        );
    }

    public function testAggregateFunctionWithDistinctInSelect()
    {
        $this->assertSqlGeneration(
            'SELECT COUNT(DISTINCT u.name) FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT COUNT(DISTINCT c0.name) AS dctrn__0 FROM cms_users c0'
        );
    }

    // Ticket #668
    public function testKeywordUsageInStringParam()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name LIKE '%foo OR bar%'",
            "SELECT c0.name AS c0__name FROM cms_users c0 WHERE c0.name LIKE '%foo OR bar%'"
        );
    }

    public function testArithmeticExpressionsSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE ((u.id + 5000) * u.id + 3) < 10000000',
            'SELECT c0.id AS c0__id, c0.status AS c0__status, c0.username AS c0__username, c0.name AS c0__name FROM cms_users c0 WHERE ((c0.id + 5000) * c0.id + 3) < 10000000'
        );
    }

    public function testPlainJoinWithoutClause()
    {
        $this->assertSqlGeneration(
            'SELECT u.id, a.id from Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a',
            'SELECT c0.id AS c0__id, c1.id AS c1__id FROM cms_users c0 LEFT JOIN cms_articles c1 ON c0.id = c1.user_id'
        );
        $this->assertSqlGeneration(
            'SELECT u.id, a.id from Doctrine\Tests\Models\CMS\CmsUser u JOIN u.articles a',
            'SELECT c0.id AS c0__id, c1.id AS c1__id FROM cms_users c0 INNER JOIN cms_articles c1 ON c0.id = c1.user_id'
        );
    }

    public function testDeepJoin()
    {
        $this->assertSqlGeneration(
            'SELECT u.id, a.id, p, c.id from Doctrine\Tests\Models\CMS\CmsUser u JOIN u.articles a JOIN u.phonenumbers p JOIN a.comments c',
            'SELECT c0.id AS c0__id, c1.id AS c1__id, c2.phonenumber AS c2__phonenumber, c3.id AS c3__id FROM cms_users c0 INNER JOIN cms_articles c1 ON c0.id = c1.user_id INNER JOIN cms_phonenumbers c2 ON c0.id = c2.user_id INNER JOIN cms_comments c3 ON c1.id = c3.article_id'
        );
    }

    /*public function testFunctionalExpressionsSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM CmsUser u WHERE TRIM(u.name) = 'someone'",
            // String quoting in the SQL usually depends on the database platform.
            // This test works with a mock connection which uses ' for string quoting.
            "SELECT cu.name AS cu__name FROM CmsUser cu WHERE TRIM(cu.name) = 'someone'"
        );
    }*/


/*
    // Ticket #973
    public function testSingleInValueWithoutSpace()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM CmsUser u WHERE u.id IN(46)",
            "SELECT cu.name AS cu__name FROM cms_user cu WHERE cu.id IN (46)"
        );
    }


    // Ticket 894
    public function testBetweenDeclarationWithInputParameter()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM CmsUser u WHERE u.id BETWEEN ? AND ?",
            "SELECT cu.name AS cu__name FROM cms_user cu WHERE cu.id BETWEEN ? AND ?"
        );
    }


    public function testInExpressionSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            'SELECT * FROM CmsUser WHERE CmsUser.id IN (1, 2)',
            'SELECT cu.id AS cu__id, cu.status AS cu__status, cu.username AS cu__username, cu.name AS cu__name FROM cms_user cu WHERE cu.id IN (1, 2)'
        );
    }


    public function testNotInExpressionSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            'SELECT * FROM CmsUser WHERE CmsUser.id NOT IN (1)',
            'SELECT cu.id AS cu__id, cu.status AS cu__status, cu.username AS cu__username, cu.name AS cu__name FROM cms_user cu WHERE cu.id NOT IN (1)'
        );
    }

*/
}
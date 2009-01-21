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
 * <http://www.phpdoctrine.org>.
 */
require_once 'lib/DoctrineTestInit.php';
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
class Orm_Query_SelectSqlGenerationTest extends Doctrine_OrmTestCase
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
            'SELECT u FROM CmsUser u',
            'SELECT cu.id AS cu__id, cu.status AS cu__status, cu.username AS cu__username, cu.name AS cu__name FROM CmsUser cu'
        );

        $this->assertSqlGeneration(
            'SELECT u.id FROM CmsUser u',
            'SELECT cu.id AS cu__id FROM CmsUser cu'
        );
    }

    public function testSelectSingleComponentWithMultipleColumns()
    {
        $this->assertSqlGeneration(
            'SELECT u.username, u.name FROM CmsUser u', 
            'SELECT cu.username AS cu__username, cu.name AS cu__name FROM CmsUser cu'
        );
    }

    public function testSelectWithCollectionAssociationJoin()
    {
        $this->assertSqlGeneration(
            'SELECT u, p FROM CmsUser u JOIN u.phonenumbers p',
            'SELECT cu.id AS cu__id, cu.status AS cu__status, cu.username AS cu__username, cu.name AS cu__name, cp.phonenumber AS cp__phonenumber FROM CmsUser cu INNER JOIN CmsPhonenumber cp ON cu.id = cp.user_id'
        );
    }

    public function testSelectWithSingleValuedAssociationJoin()
    {
        $this->assertSqlGeneration(
            'SELECT u, a FROM ForumUser u JOIN u.avatar a',
            'SELECT fu.id AS fu__id, fu.username AS fu__username, fa.id AS fa__id FROM ForumUser fu INNER JOIN ForumAvatar fa ON fu.avatar_id = fa.id'
        );
    }

    public function testSelectDistinctIsSupported()
    {
        $this->assertSqlGeneration(
            'SELECT DISTINCT u.name FROM CmsUser u',
            'SELECT DISTINCT cu.name AS cu__name FROM CmsUser cu'
        );
    }

    public function testAggregateFunctionInSelect()
    {
        $this->assertSqlGeneration(
            'SELECT COUNT(u.id) FROM CmsUser u GROUP BY u.id',
            'SELECT COUNT(cu.id) AS dctrn__0 FROM CmsUser cu GROUP BY cu.id'
        );
    }

    public function testWhereClauseInSelectWithPositionalParameter()
    {
        $this->assertSqlGeneration(
            'select u from ForumUser u where u.id = ?1',
            'SELECT fu.id AS fu__id, fu.username AS fu__username FROM ForumUser fu WHERE fu.id = ?'
        );
    }

    public function testWhereClauseInSelectWithNamedParameter()
    {
        $this->assertSqlGeneration(
            'select u from ForumUser u where u.username = :name',
            'SELECT fu.id AS fu__id, fu.username AS fu__username FROM ForumUser fu WHERE fu.username = :name'
        );
    }

    public function testWhereANDClauseInSelectWithNamedParameter()
    {
        $this->assertSqlGeneration(
            'select u from ForumUser u where u.username = :name and u.username = :name2',
            'SELECT fu.id AS fu__id, fu.username AS fu__username FROM ForumUser fu WHERE fu.username = :name AND fu.username = :name2'
        );
    }

    public function testCombinedWhereClauseInSelectWithNamedParameter()
    {
        $this->assertSqlGeneration(
            'select u from ForumUser u where (u.username = :name OR u.username = :name2) AND u.id = :id',
            'SELECT fu.id AS fu__id, fu.username AS fu__username FROM ForumUser fu WHERE (fu.username = :name OR fu.username = :name2) AND fu.id = :id'
        );
    }

    public function testAggregateFunctionWithDistinctInSelect()
    {
        $this->assertSqlGeneration(
            'SELECT COUNT(DISTINCT u.name) FROM CmsUser u',
            'SELECT COUNT(DISTINCT cu.name) AS dctrn__0 FROM CmsUser cu'
        );
    }

    // Ticket #668
    public function testKeywordUsageInStringParam()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM CmsUser u WHERE u.name LIKE '%foo OR bar%'",
            "SELECT cu.name AS cu__name FROM CmsUser cu WHERE cu.name LIKE '%foo OR bar%'"
        );
    }

    public function testArithmeticExpressionsSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM CmsUser u WHERE ((u.id + 5000) * u.id + 3) < 10000000',
            'SELECT cu.id AS cu__id, cu.status AS cu__status, cu.username AS cu__username, cu.name AS cu__name FROM CmsUser cu WHERE ((cu.id + 5000) * cu.id + 3) < 10000000'
        );
    }

    public function testPlainJoinWithoutClause()
    {
        $this->assertSqlGeneration(
            'SELECT u.id, a.id from CmsUser u LEFT JOIN u.articles a',
            'SELECT cu.id AS cu__id, ca.id AS ca__id FROM CmsUser cu LEFT JOIN CmsArticle ca ON cu.id = ca.user_id'
        );
        $this->assertSqlGeneration(
            'SELECT u.id, a.id from CmsUser u JOIN u.articles a',
            'SELECT cu.id AS cu__id, ca.id AS ca__id FROM CmsUser cu INNER JOIN CmsArticle ca ON cu.id = ca.user_id'
        );
    }

    public function testDeepJoin()
    {
        $this->assertSqlGeneration(
            'SELECT u.id, a.id, p, c.id from CmsUser u JOIN u.articles a JOIN u.phonenumbers p JOIN a.comments c',
            'SELECT cu.id AS cu__id, ca.id AS ca__id, cp.phonenumber AS cp__phonenumber, cc.id AS cc__id FROM CmsUser cu INNER JOIN CmsArticle ca ON cu.id = ca.user_id INNER JOIN CmsPhonenumber cp ON cu.id = cp.user_id INNER JOIN CmsComment cc ON ca.id = cc.article_id'
        );
    }

/*
    public function testFunctionalExpressionsSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM CmsUser u WHERE TRIM(u.name) = 'someone'",
            // String quoting in the SQL usually depends on the database platform.
            // This test works with a mock connection which uses ' for string quoting.
            "SELECT cu.name AS cu__name FROM cms_user cu WHERE TRIM(cu.name) = 'someone'"
        );
    }
*/

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
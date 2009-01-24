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
            'SELECT cm.id AS cm__id, cm.status AS cm__status, cm.username AS cm__username, cm.name AS cm__name FROM cms_users cm'
        );

        $this->assertSqlGeneration(
            'SELECT u.id FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT cm.id AS cm__id FROM cms_users cm'
        );
    }

    public function testSelectSingleComponentWithMultipleColumns()
    {
        $this->assertSqlGeneration(
            'SELECT u.username, u.name FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT cm.username AS cm__username, cm.name AS cm__name FROM cms_users cm'
        );
    }

    public function testSelectWithCollectionAssociationJoin()
    {
        $this->assertSqlGeneration(
            'SELECT u, p FROM Doctrine\Tests\Models\CMS\CmsUser u JOIN u.phonenumbers p',
            'SELECT cm.id AS cm__id, cm.status AS cm__status, cm.username AS cm__username, cm.name AS cm__name, cm1.phonenumber AS cm1__phonenumber FROM cms_users cm INNER JOIN cms_phonenumbers cm1 ON cm.id = cm1.user_id'
        );
    }

    public function testSelectWithSingleValuedAssociationJoin()
    {
        $this->assertSqlGeneration(
            'SELECT u, a FROM Doctrine\Tests\Models\Forum\ForumUser u JOIN u.avatar a',
            'SELECT fo.id AS fo__id, fo.username AS fo__username, fo1.id AS fo1__id FROM forum_users fo INNER JOIN forum_avatars fo1 ON fo.avatar_id = fo1.id'
        );
    }

    public function testSelectDistinctIsSupported()
    {
        $this->assertSqlGeneration(
            'SELECT DISTINCT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT DISTINCT cm.name AS cm__name FROM cms_users cm'
        );
    }

    public function testAggregateFunctionInSelect()
    {
        $this->assertSqlGeneration(
            'SELECT COUNT(u.id) FROM Doctrine\Tests\Models\CMS\CmsUser u GROUP BY u.id',
            'SELECT COUNT(cm.id) AS dctrn__0 FROM cms_users cm GROUP BY cm.id'
        );
    }

    public function testWhereClauseInSelectWithPositionalParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where u.id = ?1',
            'SELECT fo.id AS fo__id, fo.username AS fo__username FROM forum_users fo WHERE fo.id = ?'
        );
    }

    public function testWhereClauseInSelectWithNamedParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where u.username = :name',
            'SELECT fo.id AS fo__id, fo.username AS fo__username FROM forum_users fo WHERE fo.username = :name'
        );
    }

    public function testWhereANDClauseInSelectWithNamedParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where u.username = :name and u.username = :name2',
            'SELECT fo.id AS fo__id, fo.username AS fo__username FROM forum_users fo WHERE fo.username = :name AND fo.username = :name2'
        );
    }

    public function testCombinedWhereClauseInSelectWithNamedParameter()
    {
        $this->assertSqlGeneration(
            'select u from Doctrine\Tests\Models\Forum\ForumUser u where (u.username = :name OR u.username = :name2) AND u.id = :id',
            'SELECT fo.id AS fo__id, fo.username AS fo__username FROM forum_users fo WHERE (fo.username = :name OR fo.username = :name2) AND fo.id = :id'
        );
    }

    public function testAggregateFunctionWithDistinctInSelect()
    {
        $this->assertSqlGeneration(
            'SELECT COUNT(DISTINCT u.name) FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'SELECT COUNT(DISTINCT cm.name) AS dctrn__0 FROM cms_users cm'
        );
    }

    // Ticket #668
    public function testKeywordUsageInStringParam()
    {
        $this->assertSqlGeneration(
            "SELECT u.name FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name LIKE '%foo OR bar%'",
            "SELECT cm.name AS cm__name FROM cms_users cm WHERE cm.name LIKE '%foo OR bar%'"
        );
    }

    public function testArithmeticExpressionsSupportedInWherePart()
    {
        $this->assertSqlGeneration(
            'SELECT u FROM Doctrine\Tests\Models\CMS\CmsUser u WHERE ((u.id + 5000) * u.id + 3) < 10000000',
            'SELECT cm.id AS cm__id, cm.status AS cm__status, cm.username AS cm__username, cm.name AS cm__name FROM cms_users cm WHERE ((cm.id + 5000) * cm.id + 3) < 10000000'
        );
    }

    public function testPlainJoinWithoutClause()
    {
        $this->assertSqlGeneration(
            'SELECT u.id, a.id from Doctrine\Tests\Models\CMS\CmsUser u LEFT JOIN u.articles a',
            'SELECT cm.id AS cm__id, cm1.id AS cm1__id FROM cms_users cm LEFT JOIN cms_articles cm1 ON cm.id = cm1.user_id'
        );
        $this->assertSqlGeneration(
            'SELECT u.id, a.id from Doctrine\Tests\Models\CMS\CmsUser u JOIN u.articles a',
            'SELECT cm.id AS cm__id, cm1.id AS cm1__id FROM cms_users cm INNER JOIN cms_articles cm1 ON cm.id = cm1.user_id'
        );
    }

    public function testDeepJoin()
    {
        $this->assertSqlGeneration(
            'SELECT u.id, a.id, p, c.id from Doctrine\Tests\Models\CMS\CmsUser u JOIN u.articles a JOIN u.phonenumbers p JOIN a.comments c',
            'SELECT cm.id AS cm__id, cm1.id AS cm1__id, cm2.phonenumber AS cm2__phonenumber, cm3.id AS cm3__id FROM cms_users cm INNER JOIN cms_articles cm1 ON cm.id = cm1.user_id INNER JOIN cms_phonenumbers cm2 ON cm.id = cm2.user_id INNER JOIN cms_comments cm3 ON cm1.id = cm3.article_id'
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
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
class DeleteSqlGenerationTest extends \Doctrine\Tests\OrmTestCase
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
        } catch (\Exception $e) {
            $this->fail($e->getMessage());
        }
    }

    public function testSupportsDeleteWithoutWhereAndFrom()
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u',
            'DELETE FROM cms_users'
        );
    }

    public function testSupportsDeleteWithoutWhere()
    {
        $this->assertSqlGeneration(
            'DELETE FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'DELETE FROM cms_users'
        );
    }

    public function testSupportsWhereClause()
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1',
            'DELETE FROM cms_users WHERE id = ?'
        );
    }

    public function testSupportsWhereOrExpressions()
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username = ?1 OR u.name = ?2',
            'DELETE FROM cms_users WHERE username = ? OR name = ?'
        );
    }

    public function testSupportsWhereNestedConditionalExpressions()
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1 OR ( u.username = ?2 OR u.name = ?3)',
            'DELETE FROM cms_users WHERE id = ? OR (username = ? OR name = ?)'
        );

        //$this->assertSqlGeneration(
        //    'DELETE FROM Doctrine\Tests\Models\CMS\CmsUser WHERE id = ?1',
        //    'DELETE FROM cms_users WHERE id = ?'
        //);
    }

    public function testIsCaseAgnostic()
    {
        $this->assertSqlGeneration(
            "delete from Doctrine\Tests\Models\CMS\CmsUser u where u.username = ?1",
            "DELETE FROM cms_users WHERE username = ?"
        );
    }

    public function testSupportsAndCondition()
    {
        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username = ?1 AND u.name = ?2",
            "DELETE FROM cms_users WHERE username = ? AND name = ?"
        );
    }

    public function testSupportsWhereNot()
    {
        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE NOT u.id != ?1",
            "DELETE FROM cms_users WHERE NOT id <> ?"
        );
    }

    public function testSupportsWhereNotWithParentheses()
    {
        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE NOT ( u.id != ?1 )",
            "DELETE FROM cms_users WHERE NOT (id <> ?)"
        );
    }

    public function testSupportsWhereNotWithAndExpression()
    {
        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE NOT ( u.id != ?1 AND u.username = ?2 )",
            "DELETE FROM cms_users WHERE NOT (id <> ? AND username = ?)"
        );
    }

    // ConditionalPrimary was already tested (see testSupportsWhereClause() and testSupportsWhereNot())

    public function testSupportsGreaterThanComparisonClause()
    {
        // id = ? was already tested (see testDeleteWithWhere())
        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id > ?1",
            "DELETE FROM cms_users WHERE id > ?"
        );
    }

    public function testSupportsGreaterThanOrEqualToComparisonClause()
    {
        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id >= ?1",
            "DELETE FROM cms_users WHERE id >= ?"
        );
    }

    public function testSupportsLessThanComparisonClause()
    {
        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id < ?1",
            "DELETE FROM cms_users WHERE id < ?"
        );
    }

    public function testSupportsLessThanOrEqualToComparisonClause()
    {
        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id <= ?1",
            "DELETE FROM cms_users WHERE id <= ?"
        );
    }

    public function testSupportsNotEqualToComparisonClause()
    {
        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id <> ?1",
            "DELETE FROM cms_users WHERE id <> ?"
        );
    }

    public function testSupportsNotEqualToComparisonClauseExpressedWithExclamationMark()
    {
        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id != ?1",
            "DELETE FROM cms_users WHERE id <> ?"
        );
    }

    public function testSupportsNotBetweenClause()
    {
        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id NOT BETWEEN ?1 AND ?2",
            "DELETE FROM cms_users WHERE id NOT BETWEEN ? AND ?"
        );
    }

    public function testSupportsBetweenClauseUsedWithAndClause()
    {
        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id BETWEEN ?1 AND ?2 AND u.username != ?3",
            "DELETE FROM cms_users WHERE id BETWEEN ? AND ? AND username <> ?"
        );
    }

    public function testSupportsNotLikeClause()
    {
        // "WHERE" Expression LikeExpression
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username NOT LIKE ?1',
            'DELETE FROM cms_users WHERE username NOT LIKE ?'
        );
    }

    public function testSupportsLikeClauseWithEscapeExpression()
    {
        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username LIKE ?1 ESCAPE '\\'",
            "DELETE FROM cms_users WHERE username LIKE ? ESCAPE '\\'"
        );
    }

    public function testSupportsIsNullClause()
    {
        // "WHERE" Expression NullComparisonExpression
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name IS NULL',
            'DELETE FROM cms_users WHERE name IS NULL'
        );
    }

    public function testSupportsIsNotNullClause()
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.name IS NOT NULL',
            'DELETE FROM cms_users WHERE name IS NOT NULL'
        );
    }

    public function testSupportsAtomExpressionAsClause()
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE 1 = 1',
            'DELETE FROM cms_users WHERE 1 = 1'
        );
    }

    public function testSupportsParameterizedAtomExpression()
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE ?1 = 1',
            'DELETE FROM cms_users WHERE ? = 1'
        );
    }

    public function testSupportsInClause()
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id IN ( ?1, ?2, ?3, ?4 )',
            'DELETE FROM cms_users WHERE id IN (?, ?, ?, ?)'
        );
    }

    public function testSupportsNotInClause()
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id NOT IN ( ?1, ?2 )',
            'DELETE FROM cms_users WHERE id NOT IN (?, ?)'
        );
    }

    /**
     * @group DDC-980
     */
    public function testSubselectTableAliasReferencing()
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE SIZE(u.groups) = 10',
            'DELETE FROM cms_users WHERE (SELECT COUNT(*) FROM cms_users_groups c0_ WHERE c0_.user_id = cms_users.id) = 10'
        );
    }
}

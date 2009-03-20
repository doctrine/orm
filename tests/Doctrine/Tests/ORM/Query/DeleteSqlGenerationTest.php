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

    public function testWithoutWhere()
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u',
            'DELETE FROM cms_users c0'
        );
        $this->assertSqlGeneration(
            'DELETE FROM Doctrine\Tests\Models\CMS\CmsUser u',
            'DELETE FROM cms_users c0'
        );
    }

    public function testWithWhere()
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1',
            'DELETE FROM cms_users c0 WHERE c0.id = ?'
        );
    }

    public function testWithConditionalExpressions()
    {
        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username = ?1 OR u.name = ?2',
            'DELETE FROM cms_users c0 WHERE c0.username = ? OR c0.name = ?'
        );

        $this->assertSqlGeneration(
            'DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id = ?1 OR ( u.username = ?2 OR u.name = ?3)',
            'DELETE FROM cms_users c0 WHERE c0.id = ? OR (c0.username = ? OR c0.name = ?)'
        );

        /*$this->assertSqlGeneration(
            'DELETE FROM Doctrine\Tests\Models\CMS\CmsUser WHERE id = ?1',
            'DELETE FROM cms_users WHERE id = ?'
        );*/
    }

    public function testParserIsCaseAgnostic()
    {
        $this->assertSqlGeneration(
            "delete from Doctrine\Tests\Models\CMS\CmsUser u where u.username = ?1",
            "DELETE FROM cms_users c0 WHERE c0.username = ?"
        );
    }

    public function testWithConditionalTerms()
    {
        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.username = ?1 AND u.name = ?2",
            "DELETE FROM cms_users c0 WHERE c0.username = ? AND c0.name = ?"
        );
    }

    public function testWithConditionalFactors()
    {
        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE NOT u.id != ?1",
            "DELETE FROM cms_users c0 WHERE NOT c0.id <> ?"
        );

        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE NOT ( u.id != ?1 )",
            "DELETE FROM cms_users c0 WHERE NOT (c0.id <> ?)"
        );

        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE NOT ( u.id != ?1 AND u.username = ?2 )",
            "DELETE FROM cms_users c0 WHERE NOT (c0.id <> ? AND c0.username = ?)"
        );
    }

    // ConditionalPrimary was already tested (see testDeleteWithWhere() and testDeleteWithConditionalFactors())

    public function testWithExprAndComparison()
    {
        // id = ? was already tested (see testDeleteWithWhere())
        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id > ?1",
            "DELETE FROM cms_users c0 WHERE c0.id > ?"
        );

        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id >= ?1",
            "DELETE FROM cms_users c0 WHERE c0.id >= ?"
        );

        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id < ?1",
            "DELETE FROM cms_users c0 WHERE c0.id < ?"
        );

        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id <= ?1",
            "DELETE FROM cms_users c0 WHERE c0.id <= ?"
        );

        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id <> ?1",
            "DELETE FROM cms_users c0 WHERE c0.id <> ?"
        );

        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id != ?1",
            "DELETE FROM cms_users c0 WHERE c0.id <> ?"
        );
    }
/*
    public function testWithExprAndBetween()
    {
        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id NOT BETWEEN ?1 AND ?2",
            "DELETE FROM cms_users c0 WHERE c0.id NOT BETWEEN ? AND ?"
        );

        $this->assertSqlGeneration(
            "DELETE Doctrine\Tests\Models\CMS\CmsUser u WHERE u.id BETWEEN ?1 AND ?2 AND u.username != ?3",
            "DELETE FROM cms_users c0 WHERE c0.id BETWEEN ? AND ? AND c0.username <> ?"
        );
    }

    public function testWithExprAndLike()
    {
        // "WHERE" Expression LikeExpression
        $this->assertSqlGeneration(
            'DELETE CmsUser u WHERE u.username NOT LIKE ?',
            'DELETE FROM cms_user cu WHERE cu.username NOT LIKE ?'
        );

        $this->assertSqlGeneration(
            "DELETE CmsUser u WHERE u.username LIKE ? ESCAPE '\\'",
            "DELETE FROM cms_user cu WHERE cu.username LIKE ? ESCAPE '\\'"
        );
    }


    public function testWithExprAndIn()
    {
        // "WHERE" Expression InExpression
        $this->assertSqlGeneration(
            'DELETE CmsUser u WHERE u.id IN ( ?, ?, ?, ? )',
            'DELETE FROM cms_user cu WHERE cu.id IN (?, ?, ?, ?)'
        );

        $this->assertSqlGeneration(
            'DELETE CmsUser u WHERE u.id NOT IN ( ?, ? )',
            'DELETE FROM cms_user cu WHERE cu.id NOT IN (?, ?)'
        );
    }


    public function testWithExprAndNull()
    {
        // "WHERE" Expression NullComparisonExpression
        $this->assertSqlGeneration(
            'DELETE CmsUser u WHERE u.name IS NULL',
            'DELETE FROM cms_user cu WHERE cu.name IS NULL'
        );

        $this->assertSqlGeneration(
            'DELETE CmsUser u WHERE u.name IS NOT NULL',
            'DELETE FROM cms_user cu WHERE cu.name IS NOT NULL'
        );
    }


    // All previously defined tests used Primary as PathExpression. No need to check it again.

    public function testWithPrimaryAsAtom()
    {
        // Atom = string | integer | float | boolean | input_parameter
        $this->assertSqlGeneration(
            'DELETE CmsUser u WHERE 1 = 1',
            'DELETE FROM cms_user cu WHERE 1 = 1'
        );

        $this->assertSqlGeneration(
            'DELETE CmsUser u WHERE ? = 1',
            'DELETE FROM cms_user cu WHERE ? = 1'
        );
    }
 */
}

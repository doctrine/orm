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
class Orm_Query_DeleteSqlGenerationTest extends Doctrine_OrmTestCase
{
    public function assertSqlGeneration($dqlToBeTested, $sqlToBeConfirmed)
    {
        try {
            $entityManager = $this->_em;
            $query = $entityManager->createQuery($dqlToBeTested);

            parent::assertEquals($sqlToBeConfirmed, $query->getSql());

            $query->free();
        } catch (Doctrine_Exception $e) {
            $this->fail($e->getMessage());
        }
    }


    public function testWithoutWhere()
    {
        // NO WhereClause
        $this->assertSqlGeneration(
            'DELETE CmsUser u', 
            'DELETE FROM cms_user cu WHERE 1 = 1'
        );

        $this->assertSqlGeneration(
            'DELETE FROM CmsUser u',
            'DELETE FROM cms_user cu WHERE 1 = 1'
        );
    }


    public function testWithWhere()
    {
        // "WHERE" ConditionalExpression
        // ConditionalExpression = ConditionalTerm {"OR" ConditionalTerm}
        // ConditionalTerm       = ConditionalFactor {"AND" ConditionalFactor}
        // ConditionalFactor     = ["NOT"] ConditionalPrimary
        // ConditionalPrimary    = SimpleConditionalExpression | "(" ConditionalExpression ")"
        // SimpleConditionalExpression
        //                       = Expression (ComparisonExpression | BetweenExpression | LikeExpression
        //                       | InExpression | NullComparisonExpression) | ExistsExpression

        // If this one test fail, all others will fail too. That's the simplest case possible
        $this->assertSqlGeneration(
            'DELETE CmsUser u WHERE id = ?',
            'DELETE FROM cms_user cu WHERE cu.id = ?'
        );
    }


    public function testWithConditionalExpressions()
    {
        $this->assertSqlGeneration(
            'DELETE CmsUser u WHERE u.username = ? OR u.name = ?',
            'DELETE FROM cms_user cu WHERE cu.username = ? OR cu.name = ?'
        );

        $this->assertSqlGeneration(
            'DELETE CmsUser u WHERE u.id = ? OR ( u.username = ? OR u.name = ? )',
            'DELETE FROM cms_user cu WHERE cu.id = ? OR (cu.username = ? OR cu.name = ?)'
        );

        $this->assertSqlGeneration(
            'DELETE FROM CmsUser WHERE id = ?',
            'DELETE FROM cms_user cu WHERE cu.id = ?'
        );
    }


    public function testParserIsCaseAgnostic()
    {
        $this->assertSqlGeneration(
            "delete from CmsUser u where u.username = ?",
            "DELETE FROM cms_user cu WHERE cu.username = ?"
        );
    }


    public function testWithConditionalTerms()
    {
        $this->assertSqlGeneration(
            "DELETE CmsUser u WHERE u.username = ? AND u.name = ?",
            "DELETE FROM cms_user cu WHERE cu.username = ? AND cu.name = ?"
        );
    }


    public function testWithConditionalFactors()
    {
        $this->assertSqlGeneration(
            "DELETE CmsUser u WHERE NOT id != ?",
            "DELETE FROM cms_user cu WHERE NOT cu.id <> ?"
        );

        $this->assertSqlGeneration(
            "DELETE CmsUser u WHERE NOT ( id != ? )",
            "DELETE FROM cms_user cu WHERE NOT (cu.id <> ?)"
        );

        $this->assertSqlGeneration(
            "DELETE CmsUser u WHERE NOT ( id != ? AND username = ? )",
            "DELETE FROM cms_user cu WHERE NOT (cu.id <> ? AND cu.username = ?)"
        );
    }


    // ConditionalPrimary was already tested (see testDeleteWithWhere() and testDeleteWithConditionalFactors())


    public function testWithExprAndComparison()
    {
        // id = ? was already tested (see testDeleteWithWhere())
        $this->assertSqlGeneration(
            "DELETE CmsUser u WHERE id > ?",
            "DELETE FROM cms_user cu WHERE cu.id > ?"
        );

        $this->assertSqlGeneration(
            "DELETE CmsUser u WHERE id >= ?",
            "DELETE FROM cms_user cu WHERE cu.id >= ?"
        );

        $this->assertSqlGeneration(
            "DELETE CmsUser u WHERE id < ?",
            "DELETE FROM cms_user cu WHERE cu.id < ?"
        );

        $this->assertSqlGeneration(
            "DELETE CmsUser u WHERE id <= ?",
            "DELETE FROM cms_user cu WHERE cu.id <= ?"
        );

        $this->assertSqlGeneration(
            "DELETE CmsUser u WHERE id <> ?",
            "DELETE FROM cms_user cu WHERE cu.id <> ?"
        );

        $this->assertSqlGeneration(
            "DELETE CmsUser u WHERE id != ?",
            "DELETE FROM cms_user cu WHERE cu.id <> ?"
        );
    }


    public function testWithExprAndBetween()
    {
        // "WHERE" Expression BetweenExpression
        $this->assertSqlGeneration(
            "DELETE CmsUser u WHERE u.id NOT BETWEEN ? AND ?",
            "DELETE FROM cms_user cu WHERE cu.id NOT BETWEEN ? AND ?"
        );

        $this->assertSqlGeneration(
            "DELETE CmsUser u WHERE u.id BETWEEN ? AND ? AND u.username != ?",
            "DELETE FROM cms_user cu WHERE cu.id BETWEEN ? AND ? AND cu.username <> ?"
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
}

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
 * @since       1.0
 * @version     $Revision$
 * @todo        1) [romanb] We  might want to split the SQL generation tests into multiple
 *              testcases later since we'll have a lot of them and we might want to have special SQL
 *              generation tests for some dbms specific SQL syntaxes.
 */
class Orm_Query_DeleteSqlGenerationTest extends Doctrine_OrmTestCase
{
    public function testWithoutWhere()
    {
        $q = new Doctrine_Query();

        // NO WhereClause
        $q->setDql('DELETE CmsUser u');
        $this->assertEquals('DELETE FROM cms_user cu WHERE 1 = 1', $q->getSql());
        $q->free();

        $q->setDql('DELETE FROM CmsUser u');
        $this->assertEquals('DELETE FROM cms_user cu WHERE 1 = 1', $q->getSql());
        $q->free();
    }


    public function testWithWhere()
    {
        $q = new Doctrine_Query();

        // "WHERE" ConditionalExpression
        // ConditionalExpression = ConditionalTerm {"OR" ConditionalTerm}
        // ConditionalTerm       = ConditionalFactor {"AND" ConditionalFactor}
        // ConditionalFactor     = ["NOT"] ConditionalPrimary
        // ConditionalPrimary    = SimpleConditionalExpression | "(" ConditionalExpression ")"
        // SimpleConditionalExpression
        //                       = Expression (ComparisonExpression | BetweenExpression | LikeExpression
        //                       | InExpression | NullComparisonExpression) | ExistsExpression

        // If this one test fail, all others will fail too. That's the simplest case possible
        $q->setDql('DELETE CmsUser u WHERE id = ?');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.id = ?', $q->getSql());
        $q->free();
    }


    public function testWithConditionalExpressions()
    {
        $q = new Doctrine_Query();

        $q->setDql('DELETE CmsUser u WHERE u.username = ? OR u.name = ?');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.username = ? OR cu.name = ?', $q->getSql());
        $q->free();

        $q->setDql('DELETE CmsUser u WHERE u.id = ? OR ( u.username = ? OR u.name = ? )');
        $this->assertEquals(
            'DELETE FROM cms_user cu WHERE cu.id = ? OR (cu.username = ? OR cu.name = ?)',
            $q->getSql()
        );
        $q->free();

        $q->setDql('DELETE FROM CmsUser WHERE id = ?');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.id = ?', $q->getSql());
        $q->free();
    }


    public function testInvalidSyntaxIsRejected()
    {
        $q = new Doctrine_Query();

        $invalidDql = 'FOOBAR CmsUser';
        $q->setDql($invalidDql);
        try {
            $q->getSql();
            $this->fail("Invalid DQL '$invalidDql' was not rejected.");
        } catch (Doctrine_Exception $parseEx) {}
        $q->free();

        $invalidDql = 'DELETE FROM CmsUser.articles';
        $q->setDql($invalidDql);
        try {
            $q->getSql();
            $this->fail("Invalid DQL '$invalidDql' was not rejected.");
        } catch (Doctrine_Exception $parseEx) {}
        $q->free();

        $invalidDql = 'DELETE FROM CmsUser cu WHERE cu.articles.id > ?';
        $q->setDql($invalidDql);
        try {
            $q->getSql();
            $this->fail("Invalid DQL '$invalidDql' was not rejected.");
        } catch (Doctrine_Exception $parseEx) {}
        $q->free();
    }


    public function testParserIsCaseAgnostic()
    {
        $q = new Doctrine_Query();
        $q->setDql('delete from CmsUser u where u.username = ?');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.username = ?', $q->getSql());
    }


    public function testWithConditionalTerms()
    {
        $q = new Doctrine_Query();

        $q->setDql('DELETE CmsUser u WHERE u.username = ? AND u.name = ?');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.username = ? AND cu.name = ?', $q->getSql());
        $q->free();
    }


    public function testWithConditionalFactors()
    {
        $q = new Doctrine_Query();

        $q->setDql('DELETE CmsUser u WHERE NOT id != ?');
        $this->assertEquals('DELETE FROM cms_user cu WHERE NOT cu.id <> ?', $q->getSql());
        $q->free();

        $q->setDql('DELETE CmsUser u WHERE NOT ( id != ? )');
        $this->assertEquals('DELETE FROM cms_user cu WHERE NOT (cu.id <> ?)', $q->getSql());
        $q->free();

        $q->setDql('DELETE CmsUser u WHERE NOT ( id != ? AND username = ? )');
        $this->assertEquals('DELETE FROM cms_user cu WHERE NOT (cu.id <> ? AND cu.username = ?)', $q->getSql());
        $q->free();
    }


    // ConditionalPrimary was already tested (see testDeleteWithWhere() and testDeleteWithConditionalFactors())


    public function testWithExprAndComparison()
    {
        $q = new Doctrine_Query();

        // id = ? was already tested (see testDeleteWithWhere())

        $q->setDql('DELETE CmsUser u WHERE id > ?');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.id > ?', $q->getSql());
        $q->free();

        $q->setDql('DELETE CmsUser u WHERE id >= ?');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.id >= ?', $q->getSql());
        $q->free();

        $q->setDql('DELETE CmsUser u WHERE id < ?');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.id < ?', $q->getSql());
        $q->free();

        $q->setDql('DELETE CmsUser u WHERE id <= ?');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.id <= ?', $q->getSql());
        $q->free();

        $q->setDql('DELETE CmsUser u WHERE id <> ?');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.id <> ?', $q->getSql());
        $q->free();

        $q->setDql('DELETE CmsUser u WHERE id != ?');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.id <> ?', $q->getSql());
        $q->free();
    }


    public function testWithExprAndBetween()
    {
        $q = new Doctrine_Query();

        // "WHERE" Expression BetweenExpression
        $q->setDql('DELETE CmsUser u WHERE u.id NOT BETWEEN ? AND ?');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.id NOT BETWEEN ? AND ?', $q->getSql());
        $q->free();

        $q->setDql('DELETE CmsUser u WHERE u.id BETWEEN ? AND ? AND u.username != ?');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.id BETWEEN ? AND ? AND cu.username <> ?', $q->getSql());
        $q->free();
    }


    public function testWithExprAndLike()
    {
        $q = new Doctrine_Query();

        // "WHERE" Expression LikeExpression
        $q->setDql('DELETE CmsUser u WHERE u.username NOT LIKE ?');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.username NOT LIKE ?', $q->getSql());
        $q->free();

        $q->setDql("DELETE CmsUser u WHERE u.username LIKE ? ESCAPE '\\'");
        $this->assertEquals("DELETE FROM cms_user cu WHERE cu.username LIKE ? ESCAPE '\\'", $q->getSql());
        $q->free();
    }


    public function testWithExprAndIn()
    {
        $q = new Doctrine_Query();

        // "WHERE" Expression InExpression
        $q->setDql('DELETE CmsUser u WHERE u.id IN ( ?, ?, ?, ? )');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.id IN (?, ?, ?, ?)', $q->getSql());
        $q->free();

        $q->setDql('DELETE CmsUser u WHERE u.id NOT IN ( ?, ? )');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.id NOT IN (?, ?)', $q->getSql());
        $q->free();
    }


    public function testWithExprAndNull()
    {
        $q = new Doctrine_Query();

        // "WHERE" Expression NullComparisonExpression
        $q->setDql('DELETE CmsUser u WHERE u.name IS NULL');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.name IS NULL', $q->getSql());
        $q->free();

        $q->setDql('DELETE CmsUser u WHERE u.name IS NOT NULL');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.name IS NOT NULL', $q->getSql());
        $q->free();
    }


    // All previously defined tests used Primary as PathExpression. No need to check it again.

    public function testWithPrimaryAsAtom()
    {
        $q = new Doctrine_Query();

        // Atom = string | integer | float | boolean | input_parameter
        $q->setDql('DELETE CmsUser u WHERE 1 = 1');
        $this->assertEquals('DELETE FROM cms_user cu WHERE 1 = 1', $q->getSql());
        $q->free();

        $q->setDql('DELETE CmsUser u WHERE ? = 1');
        $this->assertEquals('DELETE FROM cms_user cu WHERE ? = 1', $q->getSql());
        $q->free();
    }
}

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
class Orm_Query_SelectSqlGenerationTest extends Doctrine_OrmTestCase
{
    public function testWithoutWhere()
    {
        $q = new Doctrine_Query();

        // NO WhereClause
        $q->setDql('SELECT u.id FROM CmsUser u');
        $this->assertEquals('SELECT cu.id AS cu__id FROM cms_user cu WHERE 1 = 1', $q->getSql());
        $q->free();

        //$q->setDql('SELECT u.* FROM CmsUser u');
        //$this->assertEquals('DELETE FROM cms_user cu WHERE 1 = 1', $q->getSql());
        //$q->free();
    }

/*
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
        $q->setDql('SELECT u.* FROM CmsUser u WHERE id = ?');
        $this->assertEquals('DELETE FROM cms_user cu WHERE cu.id = ?', $q->getSql());
        $q->free();
    }
*/
}
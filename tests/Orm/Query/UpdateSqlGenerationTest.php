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
class Orm_Query_UpdateSqlGenerationTest extends Doctrine_OrmTestCase
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
            'UPDATE CmsUser u SET name = ?', 
            'UPDATE cms_user cu SET cu.name = ? WHERE 1 = 1'
        );

        $this->assertSqlGeneration(
            'UPDATE CmsUser u SET name = ?, username = ?', 
            'UPDATE cms_user cu SET cu.name = ?, cu.username = ? WHERE 1 = 1'
        );
    }
}

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
 * Doctrine_Record_State_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Record_ZeroValues_TestCase extends Doctrine_UnitTestCase
{
    public function prepareTables()
    {
        $this->tables[] = 'ZeroValueTest';

        parent::prepareTables();
    }

    public function prepareData()
    {
        $user = new ZeroValueTest();
        $user['is_super_admin'] = 0; // set it to 0 and it should be 0 when we pull it back from the database
        $user['username'] = 'jwage';
        $user['salt'] = 'test';
        $user['password'] = 'test';
        $user->save();
    }

    public function testZeroValuesMaintained()
    {
        $users = $this->dbh->query('SELECT * FROM zero_value_test')->fetchAll(PDO::FETCH_ASSOC);

        $this->assertIdentical($users[0]['is_super_admin'], '0');
    }

    public function testZeroValuesMaintained2()
    {
        $q = new Doctrine_Query();
        $q->from('ZeroValueTest');
        $users = $q->execute(array(), Doctrine::FETCH_ARRAY);

        $this->assertIdentical($users[0]['is_super_admin'], false);
        // check for aggregate bug
        $this->assertTrue( ! isset($users[0][0]));
    }

    public function testZeroValuesMaintained3()
    {
        $q = new Doctrine_Query();
        $q->from('ZeroValueTest');
        $users = $q->execute();

        $this->assertIdentical($users[0]['is_super_admin'], false);
    }

}

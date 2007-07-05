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
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine_Query_ComponentAlias_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_ComponentAlias_TestCase extends Doctrine_UnitTestCase
{

    public function testQueryWithSingleAlias()
    {
        $this->connection->clear();
        $q = new Doctrine_Query();

        $q->from('User u, u.Phonenumber');

        $users = $q->execute();

        $count = count($this->conn);

        $this->assertEqual($users->count(), 8);
        $this->assertTrue($users[0]->Phonenumber instanceof Doctrine_Collection);
        $this->assertEqual($q->getQuery(),
        "SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)");
        $this->assertEqual($count, count($this->conn));
    }

    public function testQueryWithNestedAliases()
    {
        $this->connection->clear();
        $q = new Doctrine_Query();

        $q->from('User u, u.Group g, g.Phonenumber');

        $users = $q->execute();

        $count = count($this->conn);

        $this->assertEqual($users->count(), 8);
        $this->assertTrue($users[0]->Phonenumber instanceof Doctrine_Collection);
        $this->assertEqual($q->getQuery(),
        "SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, e2.id AS e2__id, e2.name AS e2__name, e2.loginname AS e2__loginname, e2.password AS e2__password, e2.type AS e2__type, e2.created AS e2__created, e2.updated AS e2__updated, e2.email_id AS e2__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id FROM entity e LEFT JOIN groupuser g ON e.id = g.user_id LEFT JOIN entity e2 ON e2.id = g.group_id LEFT JOIN phonenumber p ON e2.id = p.entity_id WHERE (e.type = 0 AND (e2.type = 1 OR e2.type IS NULL))");
        $this->assertEqual(($count + 1), count($this->conn));
    }
    public function testQueryWithNestedAliasesAndArrayFetching()
    {
        $this->connection->clear();
        $q = new Doctrine_Query();

        $q->from('User u, u.Group g, g.Phonenumber');

        $users = $q->execute(array(), Doctrine::FETCH_ARRAY);

        $count = count($this->conn);

        $this->assertEqual(count($users), 8);
        $this->assertEqual(count($users[7]['Group']), 0);
        $this->assertEqual(count($users[1]['Group']), 1);
    }

    public function testQueryWithMultipleNestedAliases()
    {
        $this->connection->clear();
        $q = new Doctrine_Query();

        $q->from('User u, u.Phonenumber, u.Group g, g.Phonenumber')->where('u.id IN (5,6)');

        $users = $q->execute();

        $count = count($this->conn);


        $this->assertTrue($users[0]->Phonenumber instanceof Doctrine_Collection); 
        $this->assertEqual($q->getQuery(),
        "SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id, e2.id AS e2__id, e2.name AS e2__name, e2.loginname AS e2__loginname, e2.password AS e2__password, e2.type AS e2__type, e2.created AS e2__created, e2.updated AS e2__updated, e2.email_id AS e2__email_id, p2.id AS p2__id, p2.phonenumber AS p2__phonenumber, p2.entity_id AS p2__entity_id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id LEFT JOIN groupuser g ON e.id = g.user_id LEFT JOIN entity e2 ON e2.id = g.group_id LEFT JOIN phonenumber p2 ON e2.id = p2.entity_id WHERE e.id IN (5, 6) AND (e.type = 0 AND (e2.type = 1 OR e2.type IS NULL))");
        $this->assertEqual(count($users), 2);
        $this->assertEqual(count($users[0]['Group']), 1);
        $this->assertEqual(count($users[0]['Group'][0]['Phonenumber']), 1);
        $this->assertEqual(count($users[1]['Group']), 0);
        
        $this->assertEqual($count, count($this->conn));
    }

    public function testQueryWithMultipleNestedAliasesAndArrayFetching()
    {
        $q = new Doctrine_Query();
        $q->from('User u, u.Phonenumber, u.Group g, g.Phonenumber')->where('u.id IN (5,6)');

        $users = $q->execute(array(), Doctrine::FETCH_ARRAY);

        $this->assertEqual(count($users), 2);
        $this->assertEqual(count($users[0]['Group']), 1);
        $this->assertEqual(count($users[0]['Group'][0]['Phonenumber']), 1);
        $this->assertEqual(count($users[1]['Group']), 0);
    }

}
?>

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
 * Doctrine_Query_From_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_From_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData()
    { }

    public function testCount()
    {
        $count = Doctrine_Query::create()->from('User')->count();
    
        $this->assertEqual($count, 0);
    }
    public function testLeftJoin()
    {
        $q = new Doctrine_Query();

        $q->from('User u LEFT JOIN u.Group');

        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, e2.id AS e2__id, e2.name AS e2__name, e2.loginname AS e2__loginname, e2.password AS e2__password, e2.type AS e2__type, e2.created AS e2__created, e2.updated AS e2__updated, e2.email_id AS e2__email_id FROM entity e LEFT JOIN groupuser g ON e.id = g.user_id LEFT JOIN entity e2 ON e2.id = g.group_id WHERE (e.type = 0 AND (e2.type = 1 OR e2.type IS NULL))");
    }

    public function testDefaultJoinIsLeftJoin()
    {
        $q = new Doctrine_Query();

        $q->from('User u JOIN u.Group');

        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, e2.id AS e2__id, e2.name AS e2__name, e2.loginname AS e2__loginname, e2.password AS e2__password, e2.type AS e2__type, e2.created AS e2__created, e2.updated AS e2__updated, e2.email_id AS e2__email_id FROM entity e LEFT JOIN groupuser g ON e.id = g.user_id LEFT JOIN entity e2 ON e2.id = g.group_id WHERE (e.type = 0 AND (e2.type = 1 OR e2.type IS NULL))");
    }

    public function testInnerJoin()
    {
        $q = new Doctrine_Query();

        $q->from('User u INNER JOIN u.Group');

        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, e2.id AS e2__id, e2.name AS e2__name, e2.loginname AS e2__loginname, e2.password AS e2__password, e2.type AS e2__type, e2.created AS e2__created, e2.updated AS e2__updated, e2.email_id AS e2__email_id FROM entity e INNER JOIN groupuser g ON e.id = g.user_id INNER JOIN entity e2 ON e2.id = g.group_id WHERE (e.type = 0 AND (e2.type = 1 OR e2.type IS NULL))");
    }

    public function testMultipleLeftJoin() 
    {
        $q = new Doctrine_Query();

        $q->from('User u LEFT JOIN u.Group LEFT JOIN u.Phonenumber');

        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, e2.id AS e2__id, e2.name AS e2__name, e2.loginname AS e2__loginname, e2.password AS e2__password, e2.type AS e2__type, e2.created AS e2__created, e2.updated AS e2__updated, e2.email_id AS e2__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id FROM entity e LEFT JOIN groupuser g ON e.id = g.user_id LEFT JOIN entity e2 ON e2.id = g.group_id LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0 AND (e2.type = 1 OR e2.type IS NULL))");
    }
    public function testMultipleLeftJoin2() 
    {
        $q = new Doctrine_Query();

        $q->from('User u LEFT JOIN u.Group LEFT JOIN u.Phonenumber');

        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, e2.id AS e2__id, e2.name AS e2__name, e2.loginname AS e2__loginname, e2.password AS e2__password, e2.type AS e2__type, e2.created AS e2__created, e2.updated AS e2__updated, e2.email_id AS e2__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id FROM entity e LEFT JOIN groupuser g ON e.id = g.user_id LEFT JOIN entity e2 ON e2.id = g.group_id LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0 AND (e2.type = 1 OR e2.type IS NULL))");
    }
    public function testMultipleInnerJoin() 
    {
        $q = new Doctrine_Query();

        $q->select('u.name')->from('User u INNER JOIN u.Group INNER JOIN u.Phonenumber');

        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id, e.name AS e__name FROM entity e INNER JOIN groupuser g ON e.id = g.user_id INNER JOIN entity e2 ON e2.id = g.group_id INNER JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0 AND (e2.type = 1 OR e2.type IS NULL))");
    }
    public function testMultipleInnerJoin2() 
    {
        $q = new Doctrine_Query();

        $q->select('u.name')->from('User u INNER JOIN u.Group, u.Phonenumber');

        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id, e.name AS e__name FROM entity e INNER JOIN groupuser g ON e.id = g.user_id INNER JOIN entity e2 ON e2.id = g.group_id INNER JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0 AND (e2.type = 1 OR e2.type IS NULL))");
    }
    public function testMixingOfJoins() 
    {
        $q = new Doctrine_Query();

        $q->select('u.name, g.name, p.phonenumber')->from('User u INNER JOIN u.Group g LEFT JOIN u.Phonenumber p');

        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id, e.name AS e__name, e2.id AS e2__id, e2.name AS e2__name, p.id AS p__id, p.phonenumber AS p__phonenumber FROM entity e INNER JOIN groupuser g ON e.id = g.user_id INNER JOIN entity e2 ON e2.id = g.group_id LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0 AND (e2.type = 1 OR e2.type IS NULL))");
    } 
    public function testMixingOfJoins2()
    {
        $q = new Doctrine_Query();

        $q->select('u.name, g.name, p.phonenumber')->from('User u INNER JOIN u.Group.Phonenumber p');

        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id, e.name AS e__name, p.id AS p__id, p.phonenumber AS p__phonenumber FROM entity e INNER JOIN groupuser g ON e.id = g.user_id INNER JOIN entity e2 ON e2.id = g.group_id LEFT JOIN phonenumber p ON e2.id = p.entity_id WHERE (e.type = 0 AND (e2.type = 1 OR e2.type IS NULL))");
    }
}
?>

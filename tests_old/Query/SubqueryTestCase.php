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
 * Doctrine_Query_Subquery_TestCase
 * This test case is used for testing DQL subquery functionality
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_Subquery_TestCase extends Doctrine_UnitTestCase 
{

    public function testSubqueryWithWherePartAndInExpression()
    {
        $q = new Doctrine_Query();
        $q->from('User u')->where("u.id NOT IN (SELECT u2.id FROM User u2 WHERE u2.name = 'zYne')");

        $this->assertEqual($q->getQuery(),
        "SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE e.id NOT IN (SELECT e2.id AS e2__id FROM entity e2 WHERE e2.name = 'zYne' AND (e.type = 0 AND (e2.type = 0 OR e2.type IS NULL))) AND (e.type = 0)");

        $users = $q->execute();

        $this->assertEqual($users->count(), 7);
        $this->assertEqual($users[0]->name, 'Arnold Schwarzenegger');
    }

    public function testSubqueryAllowsSelectingOfAnyField()
    {
        $q = new Doctrine_Query();
        $q->from('User u')->where('u.id NOT IN (SELECT g.user_id FROM Groupuser g)');

        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE e.id NOT IN (SELECT g.user_id AS g__user_id FROM groupuser g WHERE (e.type = 0)) AND (e.type = 0)");
    }

    public function testSubqueryInSelectPart()
    {
        // ticket #307
        $q = new Doctrine_Query();
        
        $q->parseQuery("SELECT u.name, (SELECT COUNT(p.id) FROM Phonenumber p WHERE p.entity_id = u.id) pcount FROM User u WHERE u.name = 'zYne' LIMIT 1");

        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id, e.name AS e__name, (SELECT COUNT(p.id) AS p__0 FROM phonenumber p WHERE p.entity_id = e.id AND (e.type = 0)) AS e__0 FROM entity e WHERE e.name = 'zYne' AND (e.type = 0) LIMIT 1");
        // test consequent call
        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id, e.name AS e__name, (SELECT COUNT(p.id) AS p__0 FROM phonenumber p WHERE p.entity_id = e.id AND (e.type = 0)) AS e__0 FROM entity e WHERE e.name = 'zYne' AND (e.type = 0) LIMIT 1");

        $users = $q->execute();

        $this->assertEqual($users->count(), 1);

        $this->assertEqual($users[0]->name, 'zYne');
        $this->assertEqual($users[0]->pcount, 1);
    }

    public function testSubqueryInSelectPart2()
    {
        // ticket #307
        $q = new Doctrine_Query();
        
        $q->parseQuery("SELECT u.name, (SELECT COUNT(w.id) FROM User w WHERE w.id = u.id) pcount FROM User u WHERE u.name = 'zYne' LIMIT 1");
        
        $this->assertNotEqual($q->getQuery(), "SELECT e.id AS e__id, e.name AS e__name, (SELECT COUNT(e.id) AS e__0 FROM entity e WHERE e.id = e.id AND (e.type = 0)) AS e__0 FROM entity e WHERE e.name = 'zYne' AND (e.type = 0) LIMIT 1");

    }

    public function testGetLimitSubqueryOrderBy2()
    {
        $q = new Doctrine_Query();
        $q->select('u.name, COUNT(DISTINCT a.id) num_albums');
        $q->from('User u, u.Album a');
        $q->orderby('num_albums');
        $q->groupby('u.id');

        try {
            // this causes getLimitSubquery() to be used, and it fails
            $q->limit(5);

            $users = $q->execute();

            $count = $users->count();
        } catch (Doctrine_Exception $e) {
            $this->fail();
        }

        $this->assertEqual($q->getSql(), 'SELECT e.id AS e__id, e.name AS e__name, COUNT(DISTINCT a.id) AS a__0 FROM entity e LEFT JOIN album a ON e.id = a.user_id WHERE e.id IN (SELECT DISTINCT e2.id FROM entity e2 LEFT JOIN album a2 ON e2.id = a2.user_id WHERE (e2.type = 0) GROUP BY e2.id ORDER BY a__0 LIMIT 5) AND (e.type = 0) GROUP BY e.id ORDER BY a__0');
    }

    public function testAggregateFunctionsInOrderByAndHavingWithCount()
    {
        $q = new Doctrine_Query();
        
        $q->select('u.*, COUNT(a.id) num_albums')
          ->from('User u')
          ->leftJoin('u.Album a')
          ->orderby('num_albums desc')
          ->groupby('u.id')
          ->having('num_albums > 0')
          ->limit(5);
        
        try {
            $q->count();
            $this->pass();
        } catch (Doctrine_Exception $e) {
            $this->fail();
        }
    }
}

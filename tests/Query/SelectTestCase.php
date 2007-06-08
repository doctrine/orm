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
 * Doctrine_Query_Select_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_Select_TestCase extends Doctrine_UnitTestCase 
{
    public function testAggregateFunctionParsingSupportsMultipleComponentReferences()
    {
        $q = new Doctrine_Query();
        $q->select("CONCAT(u.name, ' ', e.address) value")
          ->from('User u')->innerJoin('u.Email e');

        $this->assertEqual($q->getQuery(), "SELECT CONCAT(e.name, ' ', e2.address) AS e__0 FROM entity e INNER JOIN email e2 ON e.email_id = e2.id WHERE (e.type = 0)");

        $users = $q->execute();
        $this->assertEqual($users[0]->value, 'zYne zYne@example.com');
    }
    public function testSelectDistinctIsSupported()
    {
        $q = new Doctrine_Query();
        
        $q->distinct()->select('u.name')->from('User u');

        $this->assertEqual($q->getSql(), "SELECT DISTINCT e.id AS e__id, e.name AS e__name FROM entity e WHERE (e.type = 0)");
    }
    public function testSelectDistinctIsSupported2()
    {
        $q = new Doctrine_Query();
        
        $q->select('DISTINCT u.name')->from('User u');

        $this->assertEqual($q->getSql(), "SELECT DISTINCT e.id AS e__id, e.name AS e__name FROM entity e WHERE (e.type = 0)");
    }
    public function testAggregateFunctionWithDistinctKeyword() 
    {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT COUNT(DISTINCT u.name) FROM User u');

        $this->assertEqual($q->getQuery(), 'SELECT COUNT(DISTINCT e.name) AS e__0 FROM entity e WHERE (e.type = 0)');
    }
    public function testAggregateFunction() 
    {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT COUNT(u.id) FROM User u');

        $this->assertEqual($q->getQuery(), 'SELECT COUNT(e.id) AS e__0 FROM entity e WHERE (e.type = 0)');
    }

    public function testSelectPartSupportsMultipleAggregateFunctions() 
    {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT MAX(u.id), MIN(u.name) FROM User u');

        $this->assertEqual($q->getQuery(), 'SELECT MAX(e.id) AS e__0, MIN(e.name) AS e__1 FROM entity e WHERE (e.type = 0)');
    }
    public function testMultipleAggregateFunctionsWithMultipleComponents() 
    {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT MAX(u.id), MIN(u.name), COUNT(p.id) FROM User u, u.Phonenumber p');

        $this->assertEqual($q->getQuery(), 'SELECT MAX(e.id) AS e__0, MIN(e.name) AS e__1, COUNT(p.id) AS p__2 FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)');
    }
    public function testEmptySelectPart() 
    {
        $q = new Doctrine_Query();
        
        try {
            $q->select(null);
            
            $this->fail();
        } catch(Doctrine_Query_Exception $e) {
            $this->pass();
        }
    }
    public function testUnknownAggregateFunction() 
    {
        $q = new Doctrine_Query();
        
        try {
            $q->parseQuery('SELECT UNKNOWN(u.id) FROM User');
            $this->fail();
        } catch(Doctrine_Query_Exception $e) {
            $this->pass();
        }

    }
    public function testAggregateFunctionValueHydration()
    {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.id, COUNT(p.id) FROM User u LEFT JOIN u.Phonenumber p GROUP BY u.id');

        $users = $q->execute();

        $this->assertEqual($users[0]->Phonenumber[0]->COUNT, 1);

        $this->assertEqual($users[1]->Phonenumber[0]->COUNT, 3);
        $this->assertEqual($users[2]->Phonenumber[0]->COUNT, 1);
        $this->assertEqual($users[3]->Phonenumber[0]->COUNT, 1);
        $this->assertEqual($users[4]->Phonenumber[0]->COUNT, 3);
    }

    public function testSingleComponentWithAsterisk()
    {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.* FROM User u');

        $this->assertEqual($q->getQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE (e.type = 0)');
    }
    public function testSingleComponentWithMultipleColumns()
    {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.name, u.type FROM User u'); 
        
        $this->assertEqual($q->getQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.type AS e__type FROM entity e WHERE (e.type = 0)');
    }
    public function testMultipleComponentsWithAsterisk()
    {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.*, p.* FROM User u, u.Phonenumber p');

        $this->assertEqual($q->getQuery(),'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id, p.id AS p__id, p.phonenumber AS p__phonenumber, p.entity_id AS p__entity_id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)');
    }
    public function testMultipleComponentsWithMultipleColumns()
    {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.id, u.name, p.id FROM User u, u.Phonenumber p');

        $this->assertEqual($q->getQuery(),'SELECT e.id AS e__id, e.name AS e__name, p.id AS p__id FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0)');
    }
    public function testAggregateFunctionValueHydrationWithAliases()
    {

        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.id, COUNT(p.id) count FROM User u, u.Phonenumber p GROUP BY u.id');

        $users = $q->execute();

        $this->assertEqual($users[0]->Phonenumber[0]->count, 1);
        $this->assertEqual($users[1]->Phonenumber[0]->count, 3);
        $this->assertEqual($users[2]->Phonenumber[0]->count, 1);
        $this->assertEqual($users[3]->Phonenumber[0]->count, 1);
        $this->assertEqual($users[4]->Phonenumber[0]->count, 3);
    }
    public function testMultipleAggregateFunctionValueHydrationWithAliases()
    {
        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.id, COUNT(p.id) count, MAX(p.phonenumber) max FROM User u, u.Phonenumber p GROUP BY u.id');

        $users = $q->execute();
        $this->assertEqual($users[0]->Phonenumber[0]->count, 1);
        $this->assertEqual($users[1]->Phonenumber[0]->count, 3);
        $this->assertEqual($users[2]->Phonenumber[0]->count, 1);
        $this->assertEqual($users[3]->Phonenumber[0]->count, 1);
        $this->assertEqual($users[4]->Phonenumber[0]->count, 3);

        $this->assertEqual($users[0]->Phonenumber[0]->max, '123 123');
        $this->assertEqual($users[1]->Phonenumber[0]->max, '789 789');
        $this->assertEqual($users[2]->Phonenumber[0]->max, '123 123');
        $this->assertEqual($users[3]->Phonenumber[0]->max, '111 222 333');
        $this->assertEqual($users[4]->Phonenumber[0]->max, '444 555');
    }
    public function testMultipleAggregateFunctionValueHydrationWithAliasesAndCleanRecords()
    {
        $this->connection->clear();

        $q = new Doctrine_Query();

        $q->parseQuery('SELECT u.id, COUNT(p.id) count, MAX(p.phonenumber) max FROM User u, u.Phonenumber p GROUP BY u.id');
        
        $this->assertEqual($q->getQuery(), 'SELECT e.id AS e__id, COUNT(p.id) AS p__0, MAX(p.phonenumber) AS p__1 FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0) GROUP BY e.id');

        $users = $q->execute();

        $this->assertEqual($users[0]->Phonenumber[0]->state(), Doctrine_Record::STATE_TDIRTY);

        $this->assertEqual($users[0]->Phonenumber[0]->count, 1);
        $this->assertEqual($users[1]->Phonenumber[0]->count, 3);
        $this->assertEqual($users[2]->Phonenumber[0]->count, 1);
        $this->assertEqual($users[3]->Phonenumber[0]->count, 1);
        $this->assertEqual($users[4]->Phonenumber[0]->count, 3);

        $this->assertEqual($users[0]->Phonenumber[0]->max, '123 123');
        $this->assertEqual($users[1]->Phonenumber[0]->max, '789 789');
        $this->assertEqual($users[2]->Phonenumber[0]->max, '123 123');
        $this->assertEqual($users[3]->Phonenumber[0]->max, '111 222 333');
        $this->assertEqual($users[4]->Phonenumber[0]->max, '444 555');
    }
}
?>

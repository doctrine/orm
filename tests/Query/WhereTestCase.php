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
 * Doctrine_Query_Subquery_TestCase
 * This test case is used for testing DQL WHERE part functionality
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_Where_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData() { }
    public function prepareTables() {
        $this->tables = array('Entity', 'EnumTest', 'GroupUser');
        parent::prepareTables();
    }

    public function testDirectParameterSetting() 
    {
        $this->connection->clear();

        $user = new User();
        $user->name = 'someone';
        $user->save();

        $q = new Doctrine_Query();

        $q->from('User')->addWhere('User.id = ?',1);

        $users = $q->execute();

        $this->assertEqual($users->count(), 1);
        $this->assertEqual($users[0]->name, 'someone');
    }

    public function testDirectMultipleParameterSetting() 
    {
        $user = new User();
        $user->name = 'someone.2';
        $user->save();

        $q = new Doctrine_Query();

        $q->from('User')->addWhere('User.id IN (?, ?)', array(1, 2));

        $users = $q->execute();

        $this->assertEqual($users->count(), 2);
        $this->assertEqual($users[0]->name, 'someone');
        $this->assertEqual($users[1]->name, 'someone.2');
    }
    public function testDirectMultipleParameterSetting2() 
    {
        $q = new Doctrine_Query();

        $q->from('User')->where('User.id IN (?, ?)', array(1, 2));
        
        $this->assertEqual($q->getQuery(), 'SELECT e.id AS e__id, e.name AS e__name, e.loginname AS e__loginname, e.password AS e__password, e.type AS e__type, e.created AS e__created, e.updated AS e__updated, e.email_id AS e__email_id FROM entity e WHERE e.id IN (?, ?) AND (e.type = 0)');

        $users = $q->execute();

        $this->assertEqual($users->count(), 2);
        $this->assertEqual($users[0]->name, 'someone');
        $this->assertEqual($users[1]->name, 'someone.2');

        // the parameters and where part should be reseted
        $q->where('User.id IN (?, ?)', array(1, 2));

        $users = $q->execute();

        $this->assertEqual($users->count(), 2);
        $this->assertEqual($users[0]->name, 'someone');
        $this->assertEqual($users[1]->name, 'someone.2');
    }
    public function testNotInExpression() 
    {
        $q = new Doctrine_Query();

        $q->from('User u')->addWhere('u.id NOT IN (?)', array(1));
        $users = $q->execute();

        $this->assertEqual($users->count(), 1);
        $this->assertEqual($users[0]->name, 'someone.2');
    }
    public function testExistsExpression() 
    {
        $q = new Doctrine_Query();
        
        $user = new User();
        $user->name = 'someone with a group';
        $user->Group[0]->name = 'some group';
        $user->save();
        
        // find all users which have groups
        try {
            $q->from('User u')->where('EXISTS (SELECT Groupuser.id FROM Groupuser WHERE Groupuser.user_id = u.id)');
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
        $users = $q->execute();
        $this->assertEqual($users->count(), 1);
        $this->assertEqual($users[0]->name, 'someone with a group');
    }

    public function testNotExistsExpression() 
    {
        $q = new Doctrine_Query();

        // find all users which don't have groups
        try {
            $q->from('User u')->where('NOT EXISTS (SELECT Groupuser.id FROM Groupuser WHERE Groupuser.user_id = u.id)');
            $this->pass();
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
        $users = $q->execute();
        $this->assertEqual($users->count(), 2);
        $this->assertEqual($users[0]->name, 'someone');
        $this->assertEqual($users[1]->name, 'someone.2');
    }
    public function testComponentAliases() 
    {
        $q = new Doctrine_Query();

        $q->from('User u')->addWhere('u.id IN (?, ?)', array(1,2));

        $users = $q->execute();

        $this->assertEqual($users->count(), 2);
        $this->assertEqual($users[0]->name, 'someone');
        $this->assertEqual($users[1]->name, 'someone.2');             

    }
    public function testComponentAliases2() 
    {
        $q = new Doctrine_Query();

        $q->from('User u')->addWhere('u.name = ?', array('someone'));

        $users = $q->execute();

        $this->assertEqual($users->count(), 1);
        $this->assertEqual($users[0]->name, 'someone');
    }
    public function testOperatorWithNoTrailingSpaces()
    {
        $q = new Doctrine_Query();
        
        $q->select('User.id')->from('User')->where("User.name='someone'");

        $users = $q->execute();
        $this->assertEqual($users->count(), 1);
        
        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id FROM entity e WHERE e.name = 'someone' AND (e.type = 0)");
    }
    public function testOperatorWithNoTrailingSpaces2() 
    {
        $q = new Doctrine_Query();
        
        $q->select('User.id')->from('User')->where("User.name='foo.bar'");

        $users = $q->execute();
        $this->assertEqual($users->count(), 0);
        
        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id FROM entity e WHERE e.name = 'foo.bar' AND (e.type = 0)");
    }
    public function testOperatorWithSingleTrailingSpace() 
    {
        $q = new Doctrine_Query();
        
        $q->select('User.id')->from('User')->where("User.name= 'foo.bar'");

        $users = $q->execute();
        $this->assertEqual($users->count(), 0);
        
        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id FROM entity e WHERE e.name = 'foo.bar' AND (e.type = 0)");
    }
    public function testOperatorWithSingleTrailingSpace2() 
    {
        $q = new Doctrine_Query();

        $q->select('User.id')->from('User')->where("User.name ='foo.bar'");

        $users = $q->execute();
        $this->assertEqual($users->count(), 0);
        
        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id FROM entity e WHERE e.name = 'foo.bar' AND (e.type = 0)");
    }
    public function testDeepComponentReferencingIsSupported()
    {
        $q = new Doctrine_Query();

        $q->select('u.id')->from('User u')->where("u.Group.name ='some group'");

        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id FROM entity e LEFT JOIN groupuser g ON e.id = g.user_id LEFT JOIN entity e2 ON e2.id = g.group_id WHERE e2.name = 'some group' AND (e.type = 0 AND (e2.type = 1 OR e2.type IS NULL))");
    }
    public function testDeepComponentReferencingIsSupported2()
    {
        $q = new Doctrine_Query();

        $q->select('u.id')->from('User u')->addWhere("u.Group.name ='some group'");

        $this->assertEqual($q->getQuery(), "SELECT e.id AS e__id FROM entity e LEFT JOIN groupuser g ON e.id = g.user_id LEFT JOIN entity e2 ON e2.id = g.group_id WHERE e2.name = 'some group' AND (e.type = 0 AND (e2.type = 1 OR e2.type IS NULL))");
    }
    public function testEnumValuesWorkInPlaceholders()
    {
    	$e = new EnumTest;
    	$e->status = 'verified';
    	$e->save();

        $q = new Doctrine_Query();
        
        $q->select('e.*')->from('EnumTest e')->where('e.status = ?');

        $q->getQuery();

        $this->assertEqual(count($q->getEnumParams()), 1);

        $q->execute(array('verified'));
    }

    public function testEnumValuesWorkWithMultiplePlaceholders()
    {
        $q = new Doctrine_Query();

        $q->select('e.*')->from('EnumTest e')->where('e.id = ? AND e.status = ?');
        
        $q->getQuery();

        $p = $q->getEnumParams();
        $this->assertEqual(array_keys($p), array(0, 1));
        $this->assertTrue(empty($p[0]));
        $q->execute(array(1, 'verified'));
    }

    public function testEnumValuesWorkWithMultipleNamedPlaceholders()
    {
        $q = new Doctrine_Query();

        $q->select('e.*')->from('EnumTest e')->where('e.id = :id AND e.status = :status');
        
        $q->getQuery();

        $p = $q->getEnumParams();
        $this->assertEqual(array_keys($p), array(':id', ':status'));
        $this->assertTrue(empty($p[':id']));
        $q->execute(array(1, 'verified'));
    }
}
?>

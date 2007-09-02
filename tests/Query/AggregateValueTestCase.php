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
 * Doctrine_Query_AggregateValue_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_AggregateValue_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData() 
    { 
    }
    public function testInitData() 
    {
        $users = new Doctrine_Collection('User');
        
        $users[0]->name = 'John';
        $users[0]->Phonenumber[0]->phonenumber = '123 123';
        $users[0]->Phonenumber[1]->phonenumber = '222 222';
        $users[0]->Phonenumber[2]->phonenumber = '333 333';

        $users[1]->name = 'John';
        $users[2]->name = 'James';
        $users[2]->Phonenumber[0]->phonenumber = '222 344';
        $users[2]->Phonenumber[1]->phonenumber = '222 344';
        $users[3]->name = 'James';
        $users[3]->Phonenumber[0]->phonenumber = '123 123';

        $users->save();
    }

    public function testRecordSupportsValueMapping()
    {
        $record = new User();

        try {
            $record->get('count');
            $this->fail();
        } catch(Doctrine_Exception $e) {
            $this->pass();
        }

        $record->mapValue('count', 3);

        try {
            $i = $record->get('count');
        } catch(Doctrine_Exception $e) {
            $this->fail();
        }
        $this->assertEqual($i, 3);
    }

    public function testAggregateValueIsMappedToNewRecordOnEmptyResultSet()
    {
        $this->connection->clear();

        $q = new Doctrine_Query();

        $q->select('COUNT(u.id) count')->from('User u');
        $this->assertEqual($q->getSql(), "SELECT COUNT(e.id) AS e__0 FROM entity e WHERE (e.type = 0)");

        $users = $q->execute();
        
        $this->assertEqual($users->count(), 1);

        $this->assertEqual($users[0]->state(), Doctrine_Record::STATE_TCLEAN);
    }

    public function testAggregateValueIsMappedToRecord()
    {
        $q = new Doctrine_Query();

        $q->select('u.name, COUNT(u.id) count')->from('User u')->groupby('u.name');

        $users = $q->execute();

        $this->assertEqual($users->count(), 2);
        
        $this->assertEqual($users[0]->state(), Doctrine_Record::STATE_PROXY);
        $this->assertEqual($users[1]->state(), Doctrine_Record::STATE_PROXY);
        
        $this->assertEqual($users[0]->count, 2);
        $this->assertEqual($users[1]->count, 2);
    }

    public function testAggregateOrder()
    {
        $q = new Doctrine_Query();

        $q->select('u.name, COUNT(u.id) count')->from('User u')->groupby('u.name')->orderby('count');

        $users = $q->execute();

        $this->assertEqual($users->count(), 2);
        
        $this->assertEqual($users[0]->state(), Doctrine_Record::STATE_PROXY);
        $this->assertEqual($users[1]->state(), Doctrine_Record::STATE_PROXY);
        
        $this->assertEqual($users[0]->count, 2);
        $this->assertEqual($users[1]->count, 2);
    }

    public function testAggregateValueMappingSupportsLeftJoins() 
    {
        $q = new Doctrine_Query();

        $q->select('u.name, COUNT(p.id) count')->from('User u')->leftJoin('u.Phonenumber p')->groupby('u.id');

        $users = $q->execute();   

        $this->assertEqual(count($users), 4);

        $this->assertEqual($users[0]['Phonenumber'][0]['count'], 3);
        $this->assertEqual($users[1]['Phonenumber'][0]['count'], 0);
        $this->assertEqual($users[2]['Phonenumber'][0]['count'], 2);
        $this->assertEqual($users[3]['Phonenumber'][0]['count'], 1);
    }

    public function testAggregateValueMappingSupportsLeftJoins2()
    {
        $q = new Doctrine_Query();

        $q->select('MAX(u.name)')->from('User u')->leftJoin('u.Phonenumber p')->groupby('u.id');

        $this->assertEqual($q->getQuery(), 'SELECT MAX(e.name) AS e__0 FROM entity e LEFT JOIN phonenumber p ON e.id = p.entity_id WHERE (e.type = 0) GROUP BY e.id');
        $users = $q->execute();

        $this->assertEqual($users->count(), 4);
    }

    public function testAggregateValueMappingSupportsMultipleValues()
    {
        $q = new Doctrine_Query();

        $q->select('u.name, COUNT(p.id) count, MAX(p.id) max')->from('User u')->innerJoin('u.Phonenumber p')->groupby('u.id');

        $users = $q->execute();
        $this->assertEqual($users[0]->Phonenumber[0]->max, 3);
        $this->assertEqual($users[0]->Phonenumber[0]->count, 3);
    }
    public function testAggregateValueMappingSupportsInnerJoins()
    {
        $q = new Doctrine_Query();

        $q->select('u.name, COUNT(p.id) count')->from('User u')->innerJoin('u.Phonenumber p')->groupby('u.id');

        $users = $q->execute();

        $this->assertEqual($users->count(), 3);

        $this->assertEqual($users[0]->Phonenumber[0]->count, 3);
        $this->assertEqual($users[1]->Phonenumber[0]->count, 2);
        $this->assertEqual($users[2]->Phonenumber[0]->count, 1);
    }
    public function testAggregateFunctionParser()
    {
        $q = new Doctrine_Query();
        $func = $q->parseAggregateFunction('SUM(i.price)');
    
        $this->assertEqual($func, 'SUM(i.price)');
    }
    public function testAggregateFunctionParser2()
    {
        $q = new Doctrine_Query();
        $func = $q->parseAggregateFunction('SUM(i.price * i.quantity)');
    
        $this->assertEqual($func, 'SUM(i.price * i.quantity)');
    }
    public function testAggregateFunctionParser3()
    {
        $q = new Doctrine_Query();
        $func = $q->parseAggregateFunction('MOD(i.price, i.quantity)');

        $this->assertEqual($func, 'MOD(i.price, i.quantity)');
    }
    public function testAggregateFunctionParser4()
    {
        $q = new Doctrine_Query();
        $func = $q->parseAggregateFunction('CONCAT(i.price, i.quantity)');

        $this->assertEqual($func, 'CONCAT(i.price, i.quantity)');
    }
    public function testAggregateFunctionParsingSupportsMultipleComponentReferences()
    {
        $q = new Doctrine_Query();
        $q->select('SUM(i.price * i.quantity)')
          ->from('QueryTest_Item i');
          
        $this->assertEqual($q->getQuery(), "SELECT SUM(q.price * q.quantity) AS q__0 FROM query_test__item q");
    }


}

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
 * Doctrine_ClassTableInheritance_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_ClassTableInheritance_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareTables()
    { }
    public function prepareData()
    { }

    public function testClassTableInheritanceIsTheDefaultInheritanceType()
    {
        $class = new CTITest();

        $table = $class->getTable();

        $this->assertEqual($table->getOption('joinedParents'),
                array('CTITestParent4', 'CTITestParent3', 'CTITestParent2', 'CTITestParent1'));
    }

    public function testExportGeneratesAllInheritedTables()
    {
        $sql = $this->conn->export->exportClassesSql(array('CTITest', 'CTITestOneToManyRelated'));
    
        $this->assertEqual($sql[0], 'CREATE TABLE c_t_i_test_parent4 (id INTEGER, age INTEGER, PRIMARY KEY(id))');
        $this->assertEqual($sql[1], 'CREATE TABLE c_t_i_test_parent3 (id INTEGER, added INTEGER, PRIMARY KEY(id))');
        $this->assertEqual($sql[2], 'CREATE TABLE c_t_i_test_parent2 (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(200), verified INTEGER)');
    
        foreach ($sql as $query) {
            $this->conn->exec($query);
        }
    }

    public function testInheritedPropertiesGetOwnerFlags()
    {
        $class = new CTITest();

        $table = $class->getTable();
        
        $columns = $table->getColumns();

        $this->assertEqual($columns['verified']['owner'], 'CTITestParent2');
        $this->assertEqual($columns['name']['owner'], 'CTITestParent2');
        $this->assertEqual($columns['added']['owner'], 'CTITestParent3');
    }

    public function testNewlyCreatedRecordsHaveInheritedPropertiesInitialized()
    {
    	$profiler = new Doctrine_Connection_Profiler();

    	$this->conn->addListener($profiler);

        $record = new CTITest();

        $this->assertEqual($record->toArray(), array('id' => null,
                                                    'age' => null,
                                                    'name' => null,
                                                    'verified' => null,
                                                    'added' => null));

        $record->age = 13;
        $record->name = 'Jack Daniels';
        $record->verified = true;
        $record->added = time();
        $record->save();
        
        // pop the commit event
        $p = $profiler->pop();
        var_dump($p->getQuery());
        $this->assertEqual($profiler->pop()->getQuery(), 'INSERT INTO c_t_i_test_parent4 (age, id) VALUES (?, ?)');
        // pop the prepare event
        $profiler->pop();
        $this->assertEqual($profiler->pop()->getQuery(), 'INSERT INTO c_t_i_test_parent3 (added, id) VALUES (?, ?)');
        // pop the prepare event
        $profiler->pop();
        $this->assertEqual($profiler->pop()->getQuery(), 'INSERT INTO c_t_i_test_parent2 (name, verified) VALUES (?, ?)');
        $this->conn->addListener(new Doctrine_EventListener());
    }
    
    public function testParentalJoinsAreAddedAutomaticallyWithDql()
    {
        $q = new Doctrine_Query();
        $q->from('CTITest c')->where('c.id = 1');

        $this->assertEqual($q->getSql(), 'SELECT c.id AS c__id, c3.added AS c__added, c2.name AS c__name, c2.verified AS c__verified, c.age AS c__age FROM c_t_i_test_parent4 c LEFT JOIN c_t_i_test_parent2 c2 ON c.id = c2.id LEFT JOIN c_t_i_test_parent3 c3 ON c.id = c3.id WHERE c.id = 1');

        $record = $q->fetchOne();
        
        $this->assertEqual($record->id, 1);
        $this->assertEqual($record->name, 'Jack Daniels');
        $this->assertEqual($record->verified, true);
        $this->assertTrue(isset($record->added));
        $this->assertEqual($record->age, 13);
    }

    public function testReferenfingParentColumnsUsesProperAliases()
    {
        $q = new Doctrine_Query();
        $q->from('CTITest c')->where("c.name = 'Jack'");

        $this->assertEqual($q->getSql(), "SELECT c.id AS c__id, c3.added AS c__added, c2.name AS c__name, c2.verified AS c__verified, c.age AS c__age FROM c_t_i_test_parent4 c LEFT JOIN c_t_i_test_parent2 c2 ON c.id = c2.id LEFT JOIN c_t_i_test_parent3 c3 ON c.id = c3.id WHERE c2.name = 'Jack'");

        $q = new Doctrine_Query();
        $q->from('CTITest c')->where("name = 'Jack'");

        $this->assertEqual($q->getSql(), "SELECT c.id AS c__id, c3.added AS c__added, c2.name AS c__name, c2.verified AS c__verified, c.age AS c__age FROM c_t_i_test_parent4 c LEFT JOIN c_t_i_test_parent2 c2 ON c.id = c2.id LEFT JOIN c_t_i_test_parent3 c3 ON c.id = c3.id WHERE c2.name = 'Jack'");
    }

    public function testFetchingCtiRecordsSupportsLimitSubqueryAlgorithm()
    {
    	$record = new CTITestOneToManyRelated;
    	$record->name = 'Someone';
    	$record->cti_id = 1;
    	$record->save();

        $this->conn->clear();

        $q = new Doctrine_Query();
        $q->from('CTITestOneToManyRelated c')->leftJoin('c.CTITest c2')->where('c.id = 1')->limit(1);

        $record = $q->fetchOne();
        
        $this->assertEqual($record->name, 'Someone');
        $this->assertEqual($record->cti_id, 1);

        $cti = $record->CTITest[0];

        $this->assertEqual($cti->id, 1);
        $this->assertEqual($cti->name, 'Jack Daniels');
        $this->assertEqual($cti->verified, true);
        $this->assertTrue(isset($cti->added));
        $this->assertEqual($cti->age, 13);
    }

    public function testUpdatingCtiRecordsUpdatesAllParentTables()
    {
        $this->conn->clear();

        $profiler = new Doctrine_Connection_Profiler();
    	$this->conn->addListener($profiler);

        $record = $this->conn->getRepository('CTITest')->find(1);
        
        $record->age = 11;
        $record->name = 'Jack';
        $record->verified = false;
        $record->added = 0;
        
        $record->save();
        
        // pop the commit event
        $profiler->pop();
        $this->assertEqual($profiler->pop()->getQuery(), 'UPDATE c_t_i_test_parent4 SET age = ? WHERE id = ?');
        // pop the prepare event
        $profiler->pop();
        $this->assertEqual($profiler->pop()->getQuery(), 'UPDATE c_t_i_test_parent3 SET added = ? WHERE id = ?');
        // pop the prepare event
        $profiler->pop();
        $this->assertEqual($profiler->pop()->getQuery(), 'UPDATE c_t_i_test_parent2 SET name = ?, verified = ? WHERE id = ?');
        $this->conn->addListener(new Doctrine_EventListener());
    }
    
    public function testUpdateOperationIsPersistent()
    {
        $this->conn->clear();
        
        $record = $this->conn->getRepository('CTITest')->find(1);
        
        $this->assertEqual($record->id, 1);
        $this->assertEqual($record->name, 'Jack');
        $this->assertEqual($record->verified, false);
        $this->assertEqual($record->added, 0);
        $this->assertEqual($record->age, 11);
    }
    
    public function testDeleteIssuesQueriesOnAllJoinedTables()
    {
        $this->conn->clear();

        $profiler = new Doctrine_Connection_Profiler();
    	$this->conn->addListener($profiler);

        $record = $this->conn->getRepository('CTITest')->find(1);
        
        $record->delete();

        // pop the commit event
        $profiler->pop();
        $this->assertEqual($profiler->pop()->getQuery(), 'DELETE FROM c_t_i_test_parent4 WHERE id = ?');
        // pop the prepare event
        $profiler->pop();
        $this->assertEqual($profiler->pop()->getQuery(), 'DELETE FROM c_t_i_test_parent3 WHERE id = ?');
        // pop the prepare event
        $profiler->pop();
        $this->assertEqual($profiler->pop()->getQuery(), 'DELETE FROM c_t_i_test_parent2 WHERE id = ?');
        $this->conn->addListener(new Doctrine_EventListener());
    }
}
class CTITestParent1 extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->setInheritanceType(Doctrine::INHERITANCE_TYPE_JOINED, array(
                'CTITestParent1' => 1, 'CTITestParent2' => 2,
                'CTITestParent3' => 3, 'CTITestParent4' => 4,
                'CTITest' => 5));
        $this->hasColumn('name', 'string', 200);
    }
}
class CTITestParent2 extends CTITestParent1
{
    public function setTableDefinition()
    {
        $this->hasColumn('verified', 'boolean', 1);
    }
}
class CTITestParent3 extends CTITestParent2
{
    public function setTableDefinition()
    {
        $this->hasColumn('added', 'integer');
    }
}
class CTITestParent4 extends CTITestParent3
{
    public function setTableDefinition()
    {
        $this->hasColumn('age', 'integer', 4);
    }
}
class CTITest extends CTITestParent4
{
    public function setTableDefinition()
    {
        $this->hasColumn('age2', 'integer', 4);
    }
}

class CTITestOneToManyRelated extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string');
        $this->hasColumn('cti_id', 'integer');
    }
    
    public function setUp()
    {
        $this->hasMany('CTITest', array('local' => 'cti_id', 'foreign' => 'id'));
    }
}

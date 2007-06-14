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
 * Doctrine_Relation_Nest_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Relation_Nest_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData() 
    { }
    public function prepareTables()
    {
        $this->tables = array('NestTest', 'NestReference', 'Entity', 'EntityReference');
        
        parent::prepareTables();
    }
    public function testInitJoinTableSelfReferencingInsertingData() {
        $e = new Entity();
        $e->name = "Entity test";

        $this->assertTrue($e->Entity[0] instanceof Entity);
        $this->assertTrue($e->Entity[1] instanceof Entity);

        $this->assertEqual($e->Entity[0]->state(), Doctrine_Record::STATE_TCLEAN);
        $this->assertEqual($e->Entity[1]->state(), Doctrine_Record::STATE_TCLEAN);
        
        $e->Entity[0]->name = 'Friend 1';
        $e->Entity[1]->name = 'Friend 2';
        
        $e->Entity[0]->Entity[0]->name = 'Friend 1 1';
        $e->Entity[0]->Entity[1]->name = 'Friend 1 2';

        $e->Entity[1]->Entity[0]->name = 'Friend 2 1';
        $e->Entity[1]->Entity[1]->name = 'Friend 2 2';

        $this->assertEqual($e->Entity[0]->name, 'Friend 1');
        $this->assertEqual($e->Entity[1]->name, 'Friend 2');

        $this->assertEqual($e->Entity[0]->Entity[0]->name, 'Friend 1 1');
        $this->assertEqual($e->Entity[0]->Entity[1]->name, 'Friend 1 2');

        $this->assertEqual($e->Entity[1]->Entity[0]->name, 'Friend 2 1');
        $this->assertEqual($e->Entity[1]->Entity[1]->name, 'Friend 2 2');

        $this->assertEqual($e->Entity[0]->state(), Doctrine_Record::STATE_TDIRTY);
        $this->assertEqual($e->Entity[1]->state(), Doctrine_Record::STATE_TDIRTY);

        $count = count($this->dbh);

        $e->save();

        $this->assertEqual(($count + 13), $this->dbh->count());
    }
    public function testNestRelationsFetchingData()
    {    
        $this->connection->clear();

        $e = $this->conn->queryOne('FROM Entity e LEFT JOIN e.Entity e2 LEFT JOIN e2.Entity e3 WHERE e.id = 1 ORDER BY e.name, e2.name, e3.name');
        $this->assertEqual($e->state(), Doctrine_Record::STATE_CLEAN);

        $this->assertTrue($e->Entity[0] instanceof Entity);
        $this->assertTrue($e->Entity[1] instanceof Entity);

        $this->assertEqual($e->Entity[0]->name, 'Friend 1');
        $this->assertEqual($e->Entity[1]->name, 'Friend 2');

        $this->assertEqual($e->Entity[0]->Entity[0]->name, 'Entity test');
        $this->assertEqual($e->Entity[0]->Entity[1]->name, 'Friend 1 1');
        $this->assertEqual($e->Entity[0]->Entity[2]->name, 'Friend 1 2');

        $this->assertEqual($e->Entity[0]->Entity[0]->name, 'Entity test');
        $this->assertEqual($e->Entity[1]->Entity[1]->name, 'Friend 2 1');
        $this->assertEqual($e->Entity[1]->Entity[2]->name, 'Friend 2 2');

        $this->assertEqual($e->Entity[0]->state(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($e->Entity[1]->state(), Doctrine_Record::STATE_CLEAN);

        $this->assertTrue(is_numeric($e->id));

        $result = $this->dbh->query('SELECT * FROM entity_reference')->fetchAll(PDO::FETCH_ASSOC);

        $this->assertEqual(count($result), 6);

        //$stmt = $this->dbh->prepare($q);

        //$stmt->execute(array(18));
        //$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //print_r($result);

        $this->connection->clear();

        $e = $e->getTable()->find($e->id);

        $count = count($this->dbh);

        $this->assertTrue($e instanceof Entity);

        $this->assertTrue($e->Entity[0] instanceof Entity);
        $this->assertTrue($e->Entity[1] instanceof Entity);



        $this->assertEqual(count($this->dbh), ($count + 1));

        $this->assertEqual($e->Entity[0]->name, "Friend 1");
        $this->assertEqual($e->Entity[1]->name, "Friend 2");

        $this->assertEqual($e->Entity[0]->Entity[0]->name, "Entity test");
        $this->assertEqual($e->Entity[0]->Entity[1]->name, "Friend 1 1");

        $this->assertEqual(count($this->dbh), ($count + 2));

        $this->assertEqual($e->Entity[1]->Entity[0]->name, "Entity test");
        $this->assertEqual($e->Entity[1]->Entity[1]->name, "Friend 2 1");

        $this->assertEqual(count($this->dbh), ($count + 3));

        $this->assertEqual($e->Entity[0]->state(), Doctrine_Record::STATE_CLEAN);
        $this->assertEqual($e->Entity[1]->state(), Doctrine_Record::STATE_CLEAN);
        
        $coll = $this->connection->query("FROM Entity WHERE Entity.name = 'Friend 1'");
        $this->assertEqual($coll->count(), 1);
        $this->assertEqual($coll[0]->state(), Doctrine_Record::STATE_CLEAN);
        
        $this->assertEqual($coll[0]->name, "Friend 1");
        
        $query = new Doctrine_Query($this->connection);

        $query->from('Entity e LEFT JOIN e.Entity e2')->where("e2.name = 'Friend 1 1'");

        $coll = $query->execute();

        $this->assertEqual($coll->count(), 1);
    }
    public function testNestRelationsParsing()
    {
        $nest = new NestTest();
        
        $rel = $nest->getTable()->getRelation('Parents');

        $this->assertTrue($rel instanceof Doctrine_Relation_Nest);
        
        $this->assertEqual($rel->getLocal(), 'child_id');
        $this->assertEqual($rel->getForeign(), 'parent_id');
    }

    public function testNestRelationsSaving()
    {
        $nest = new NestTest();
        $nest->name = 'n 1';
        $nest->Parents[0]->name = 'p 1';
        $nest->Parents[1]->name = 'p 2';
        $nest->Parents[2]->name = 'p 3';
        $nest->Children[0]->name = 'c 1';
        $nest->Children[0]->Children[0]->name = 'c c 1';
        $nest->Children[0]->Children[1]->name = 'c c 2';
        $nest->Children[1]->name = 'c 2';
        $nest->Children[1]->Parents[]->name = 'n 2';
        $nest->save();
        
        $this->connection->clear();
    }

    public function testNestRelationsLoading()
    {
        $nest = $this->conn->queryOne('FROM NestTest n WHERE n.id = 1');
        
        $this->assertEqual($nest->Parents->count(), 3);
        $this->assertEqual($nest->Children->count(), 2);
        $this->assertEqual($nest->Children[0]->Children->count(), 2);
        $this->assertEqual($nest->Children[1]->Parents->count(), 2);
        
        $this->connection->clear();
    }
    public function testEqualNestRelationsLoading()
    {
        $nest = $this->conn->queryOne('FROM NestTest n WHERE n.id = 1');

        $this->assertEqual($nest->Relatives->count(), 5);
    }
    public function testNestRelationsQuerying()
    {
        $this->connection->clear();

        $q = new Doctrine_Query();

        $q->from('NestTest n')->innerJoin('n.Parents p')->where('n.id = 1');

        $n = $q->execute();

        $this->assertEqual($n[0]->Parents->count(), 3);
    }
    public function testNestRelationsQuerying2()
    {
        $this->connection->clear();

        $q = new Doctrine_Query();

        $q->from('NestTest n')->innerJoin('n.Children c')->where('n.id = 1');

        $n = $q->execute();

        $this->assertEqual($n[0]->Children->count(), 2);
    }

    public function testEqualNestRelationsQuerying()
    {
        $this->connection->clear();

        $q = new Doctrine_Query();

        $q->from('NestTest n')->innerJoin('n.Relatives r')->where('n.id = 1');

        $n = $q->execute();

        $this->assertEqual($q->getSql(), 'SELECT n.id AS n__id, n.name AS n__name, n2.id AS n2__id, n2.name AS n2__name FROM nest_test n INNER JOIN nest_reference n3 ON n.id = n3.child_id OR n.id = n3.parent_id INNER JOIN nest_test n2 ON (n2.id = n3.parent_id OR n2.id = n3.child_id) AND n2.id != n.id WHERE n.id = 1');

        $this->assertEqual($n[0]->Relatives->count(), 5);
    }

}

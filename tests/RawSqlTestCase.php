<?php
/*
 *  $Id: RawSqlTestCase.php 1181 2007-03-20 23:22:51Z gnat $
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
 * Doctrine_RawSql_TestCase
 * This class tests the functinality of Doctrine_RawSql component
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1181 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_RawSql_TestCase extends Doctrine_UnitTestCase 
{
    public function testQueryParser()
    {
        $sql = 'SELECT {p.*} FROM photos p';
        $query = new Doctrine_RawSql($this->connection);
        $query->parseQuery($sql);
        
        $this->assertEqual($query->getQueryPart('from'), array('photos p'));


        $sql = 'SELECT {p.*} FROM (SELECT p.* FROM photos p LEFT JOIN photos_tags t ON t.photo_id = p.id WHERE t.tag_id = 65) p LEFT JOIN photos_tags t ON t.photo_id = p.id WHERE p.can_see = -1 AND t.tag_id = 62 LIMIT 200';
        $query->parseQuery($sql);

        $this->assertEqual($query->getQueryPart('from'), array('(SELECT p.* FROM photos p LEFT JOIN photos_tags t ON t.photo_id = p.id WHERE t.tag_id = 65) p LEFT JOIN photos_tags t ON t.photo_id = p.id'));
        $this->assertEqual($query->getQueryPart('where'), array('p.can_see = -1 AND t.tag_id = 62'));
        $this->assertEqual($query->getQueryPart('limit'), array(200));
    }
    public function testAsteriskOperator() 
    {
        // Selecting with *

        $query = new Doctrine_RawSql($this->connection);
        $query->parseQuery('SELECT {entity.*} FROM entity');
        $fields = $query->getFields();

        $this->assertEqual($fields, array('entity.*'));

        $query->addComponent('entity', 'Entity');

        $coll = $query->execute();

        $this->assertEqual($coll->count(), 11);
    }
    public function testLazyPropertyLoading() 
    {
        $query = new Doctrine_RawSql($this->connection);
        $this->connection->clear();

        // selecting proxy objects (lazy property loading)

        $query->parseQuery('SELECT {entity.name}, {entity.id} FROM entity');
        $fields = $query->getFields();

        $this->assertEqual($fields, array('entity.name', 'entity.id'));
        $query->addComponent('entity', 'Entity');

        $coll = $query->execute();

        $this->assertEqual($coll->count(), 11);

        $this->assertEqual($coll[0]->state(), Doctrine_Record::STATE_PROXY);
        $this->assertEqual($coll[3]->state(), Doctrine_Record::STATE_PROXY); 
    }
    public function testSmartMapping() 
    {
        $query = new Doctrine_RawSql($this->connection);
        // smart component mapping (no need for additional addComponent call
        
        $query->parseQuery('SELECT {entity.name}, {entity.id} FROM entity');
        $fields = $query->getFields();

        $this->assertEqual($fields, array('entity.name', 'entity.id'));

        $coll = $query->execute();

        $this->assertEqual($coll->count(), 11);

        $this->assertEqual($coll[0]->state(), Doctrine_Record::STATE_PROXY);
        $this->assertEqual($coll[3]->state(), Doctrine_Record::STATE_PROXY);
    }

    public function testMultipleComponents() 
    {
        $query = new Doctrine_RawSql($this->connection);
        // multi component fetching

        $query->parseQuery('SELECT {entity.name}, {entity.id}, {phonenumber.*} FROM entity LEFT JOIN phonenumber ON phonenumber.entity_id = entity.id');

        $query->addComponent('entity', 'Entity');

        $query->addComponent('phonenumber', 'Entity.Phonenumber');

        $coll = $query->execute();
        $this->assertEqual($coll->count(), 11);

        $count = $this->dbh->count();
        
        $coll[4]->Phonenumber[0]->phonenumber;
        $this->assertEqual($count, $this->dbh->count());

        $coll[5]->Phonenumber[0]->phonenumber;
        $this->assertEqual($count, $this->dbh->count());
    }
    public function testAliasesAreSupportedInAddComponent()
    {
        $query = new Doctrine_RawSql();
        $query->parseQuery('SELECT {entity.name}, {entity.id}, {phonenumber.*} FROM entity LEFT JOIN phonenumber ON phonenumber.entity_id = entity.id');

        $query->addComponent('entity', 'Entity e');
        $query->addComponent('phonenumber', 'e.Phonenumber');

        $this->assertEqual(array_keys($query->getAliasMap()), array('e', 'e.Phonenumber'));

        $coll = $query->execute();
        $this->assertEqual($coll->count(), 11);

        $count = $this->dbh->count();
        
        $coll[4]->Phonenumber[0]->phonenumber;
        $this->assertEqual($count, $this->dbh->count());

        $coll[5]->Phonenumber[0]->phonenumber;
        $this->assertEqual($count, $this->dbh->count());
    }
    public function testPrimaryKeySelectForcing()
    {
        // forcing the select of primary key fields
        
        $query = new Doctrine_RawSql($this->connection);

        $query->parseQuery('SELECT {entity.name} FROM entity');
        
        $coll = $query->execute();
        
        $this->assertEqual($coll->count(), 11);
        $this->assertTrue(is_numeric($coll[0]->id));
        $this->assertTrue(is_numeric($coll[3]->id));
        $this->assertTrue(is_numeric($coll[7]->id));
    }
    public function testConvenienceMethods() 
    {
        $query = new Doctrine_RawSql($this->connection);
        $query->select('{entity.name}')->from('entity');
        $query->addComponent('entity', 'User');

        $coll = $query->execute();

        $this->assertEqual($coll->count(), 8);
        $this->assertTrue(is_numeric($coll[0]->id));
        $this->assertTrue(is_numeric($coll[3]->id));
        $this->assertTrue(is_numeric($coll[7]->id));
    }

    public function testColumnAggregationInheritance() 
    {
        // forcing the select of primary key fields

        $query = new Doctrine_RawSql($this->connection);

        $query->parseQuery('SELECT {entity.name} FROM entity');
        $query->addComponent('entity', 'User');
        $coll = $query->execute();

        $this->assertEqual($coll->count(), 8);
        $this->assertTrue(is_numeric($coll[0]->id));
        $this->assertTrue(is_numeric($coll[3]->id));
        $this->assertTrue(is_numeric($coll[7]->id));
    }

    public function testColumnAggregationInheritanceWithOrderBy() 
    {
        // forcing the select of primary key fields

        $query = new Doctrine_RawSql($this->connection);

        $query->parseQuery('SELECT {entity.name} FROM entity ORDER BY entity.name');
        $query->addComponent('entity', 'User');

        $this->assertEqual($query->getQuery(), "SELECT entity.name AS entity__name, entity.id AS entity__id FROM entity WHERE entity.type = 0 ORDER BY entity.name");


        $coll = $query->execute();

        $this->assertEqual($coll->count(), 8);
        $this->assertTrue(is_numeric($coll[0]->id));
        $this->assertTrue(is_numeric($coll[3]->id));
        $this->assertTrue(is_numeric($coll[7]->id));

    }

    public function testQueryParser2() 
    {
        $query = new Doctrine_RawSql();
        
        $query->parseQuery("SELECT {entity.name} FROM (SELECT entity.name FROM entity WHERE entity.name = 'something') WHERE entity.id = 2 ORDER BY entity.name");

        $this->assertEqual($query->getQuery(),
        "SELECT entity.name AS entity__name, entity.id AS entity__id FROM (SELECT entity.name FROM entity WHERE entity.name = 'something') WHERE entity.id = 2 ORDER BY entity.name");
    }
}
?>

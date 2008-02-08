<?php
/**
 * Doctrine_Ticket_749_TestCase
 *
 * @package     Doctrine
 * @author      David Brewer <dbrewer@secondstory.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 *
 * This test case demonstrates a problem with column aggregation inheritance
 * in Doctrine 0.9. The high level summary is that it is possible to make
 * it work in general -- if class B is a subclass of class A, you can select
 * from class A and get back objects of class B.  However, those objects do
 * not have the related objects of class B, and in fact an exception is
 * thrown when you try to access those related objects.
 *
 * This test case should not probably be applied to trunk and possibly not
 * to 0.10 branch.  I'm not sure it's even possible to fix in 0.9 but it is
 * an issue that keeps arising for me so it seemed worth a test case.
 */

class Doctrine_Ticket_749_TestCase extends Doctrine_UnitTestCase
{
    public function prepareTables()
    {
        $this->tables = array('Parent749', 'Record749', 'RelatedRecord749');
        parent::prepareTables();
    }

    public function prepareData() 
    {
        $record = new Record749();
        $record['title'] = 'Test Record 1';
        $record['Related']['content'] = 'Test Content 1';
        $record->save();

        $record = new Record749();
        $record['title'] = 'Test Record 2';
        $record['Related']['content'] = 'Test Content 2';
        $record->save();
    }

    public function testSelectDataFromSubclassAsCollection()
    {
        $records = Doctrine_Query::create()->query('
            FROM Record749 r ORDER BY r.title                                           
        ', array());
        
        $this->verifyRecords($records);
    }
    
    public function testSelectDataFromParentClassAsCollection()
    {
        $records = Doctrine_Query::create()->query('
            FROM Parent749 p ORDER BY p.title                                           
        ', array());
        
        $this->verifyRecords($records);
    }

    /**
     * This method is used by both tests, as the collection of records should
     * be identical for both of them if things are working properly.
     */
    private function verifyRecords ($records) {
        $expected_values = array(
            array('title'=>'Test Record 1', 'content'=>'Test Content 1'),
            array('title'=>'Test Record 2', 'content'=>'Test Content 2'),
        );

        foreach ($records as $record) {
            $this->assertTrue($record instanceof Record749);
            $expected = array_shift($expected_values);
            $this->assertEqual($record['title'], $expected['title']);
            try {
                $this->assertEqual($record['Related']['content'], $expected['content']);
            } catch (Exception $e) {
                $this->fail('Caught exception when trying to get related content.');
            }
        }        
    }
}

class Parent749 extends Doctrine_Record
{
  public function setTableDefinition()
  {
    $this->setTableName('mytable');
    $this->hasColumn('id', 'integer', 4, array (
      'primary' => true,
      'autoincrement' => true,
      'notnull' => true,
    ));

    $this->hasColumn('title', 'string', 255, array ());
    $this->hasColumn('type', 'integer', 11, array ());

    $this->option('subclasses', array('Record749'));
  }

  public function setUp()
  {
  }
}

class Record749 extends Parent749
{
  public function setTableDefinition()
  {
    parent::setTableDefinition();
    $this->setTableName('mytable');
  }

  public function setUp()
  {
    parent::setUp();
    $this->hasOne('RelatedRecord749 as Related', array('local' => 'id',
                                                    'foreign' => 'record_id'));

    $this->setInheritanceMap(array('type' => '1'));
  }
}

class RelatedRecord749 extends Doctrine_Record
{
  public function setTableDefinition()
  {
    $this->hasColumn('id', 'integer', 4, array (
      'primary' => true,
      'autoincrement' => true,
      'notnull' => true,
    ));

    $this->hasColumn('content', 'string', 255, array ());
    $this->hasColumn('record_id', 'integer', null, array ('unique' => true,));
  }

  public function setUp()
  {
    $this->hasOne('Record749 as Record', array('local' => 'record_id',
                                  'foreign' => 'id',
                                  'onDelete' => 'cascade'));
  }

}


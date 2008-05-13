<?php

/**
 * Doctrine_Ticket_626_TestCase
 *
 * @package     Doctrine
 * @author      Tamcy <7am.online@gmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */

class Doctrine_Ticket_626C_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData() 
    { }

    public function prepareTables()
    {
      $this->tables = array('T626C_Student1', 'T626C_Student2');
      parent::prepareTables();
    }

    protected function newStudent($cls, $id, $name)
    {
      $u = new $cls;
      $u->id = $id;
      $u->name = $name;
      $u->save();
      return $u;
    }

    public function testFieldNames()
    {
      $student1 = $this->newStudent('T626C_Student1', '07090002', 'First Student');

      try {
        $students = Doctrine_Query::create()
          ->from('T626C_Student1 s INDEXBY s.id')
          ->execute(array(), Doctrine::FETCH_ARRAY);
        $this->pass();
      } catch (Exception $e) {
        $this->fail($e->__toString());
      }
    }

    public function testColNames()
    {
      $student1 = $this->newStudent('T626C_Student2', '07090002', 'First Student');

      try {
        $students = Doctrine_Query::create()
          ->from('T626C_Student2 s INDEXBY s.id')
          ->execute(array(), Doctrine::FETCH_ARRAY);
        $this->pass();
      } catch (Exception $e) {
        $this->fail($e->__toString());
      }
    }
}


class T626C_Student1 extends Doctrine_Entity
{
  public static function initMetadata($class)
  {
    $class->setTableName('T626C_Student_record_1');

    $class->setColumn('s_id as id', 'varchar', 30, array (  'primary' => true,));
    $class->setColumn('s_name as name', 'varchar', 50, array ());
  }
}

class T626C_Student2 extends Doctrine_Entity
{
  public static function initMetadata($class)
  {
    $class->setTableName('T626C_Student_record_2');

    $class->setColumn('id', 'varchar', 30, array (  'primary' => true,));
    $class->setColumn('name', 'varchar', 50, array ());
  }
}
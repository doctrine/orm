<?php

/**
 * Doctrine_Ticket_626D_TestCase
 *
 * @package     Doctrine
 * @author      Tamcy <7am.online@gmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */

class Doctrine_Ticket_626D_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData() 
    { }

    public function prepareTables()
    {
      $this->tables = array('T626D_Student1');
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
      $student1 = $this->newStudent('T626D_Student1', '07090002', 'First Student');

      try {
        $student = $this->conn->getMapper('T626D_Student1')->find('07090002');
        $this->pass();
      } catch (Exception $e) {
        $this->fail($e->__toString());
      }
    }
}


class T626D_Student1 extends Doctrine_Record
{
  public static function initMetadata($class)
  {
    $class->setTableName('T626D_Student_record_1');

    $class->setColumn('s_id as id', 'varchar', 30, array (  'primary' => true,));
    $class->setColumn('s_name as name', 'varchar', 50, array ());
  }
}

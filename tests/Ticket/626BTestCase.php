<?php

/**
 * Doctrine_Ticket_626_TestCase
 *
 * @package     Doctrine
 * @author      Tamcy <7am.online@gmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */

class Doctrine_Ticket_626B_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData() 
    { }

    public function prepareTables()
    {
      $this->tables = array('T626_Group', 'T626B_Student', 'T626_Course', 'T626B_StudentCourse');
      parent::prepareTables();
    }

    protected function newCourse($id, $name)
    {
      $course = new T626_Course();
      $course->id = $id;
      $course->name = $name;
      $course->save();
      return $course;
    }

    protected function newGroup($id, $name)
    {
      $group = new T626_Group();
      $group->id = $id;
      $group->name = $name;
      $group->save();
      return $group;
    }

    protected function newStudent($id, $name, $group)
    {
      $u = new T626B_Student();
      $u->id = $id;
      $u->name = $name;
      $u->group_id = $group->id;
      $u->save();
      return $u;
    }

    protected function newStudentCourse($student, $course)
    {
      $sc = new T626B_StudentCourse;
      $sc->student_id = $student->id;
      $sc->course_id = $course->id;
      $sc->save();
      return $sc;
    }

    public function testTicket()
    {
      $group1 = $this->newGroup('1', 'Group 1');
      $student1 = $this->newStudent('07090002', 'First Student', $group1);
      $course1 = $this->newCourse('MATH001', 'Maths');
      $course2 = $this->newCourse('ENG002', 'English Literature');

      $this->newStudentCourse($student1, $course1);
      $this->newStudentCourse($student1, $course2);
      
      try {
        $group = $student1->get('Group');
        $this->pass();
      } catch (Exception $e) {
        $this->fail($e->__toString());
      }

      try {
        $courses = $student1->get('StudyCourses');
        $this->pass();
      } catch (Exception $e) {
        $this->fail($e->__toString());
      }

    }
}


class T626B_Student extends Doctrine_Record
{
  public function setTableDefinition()
  {
    $this->setTableName('T626B_Student_record');

    $this->hasColumn('s_id as id', 'varchar', 30, array (  'primary' => true,));
    $this->hasColumn('s_g_id as group_id', 'varchar', 30, array ('notnull'=>true));
    $this->hasColumn('s_name as name', 'varchar', 50, array ());
  }
  
  public function setUp()
  {
    $this->hasMany('T626_Course as StudyCourses', array('refClass' => 'T626B_StudentCourse', 'local' => 'sc_student_id', 'foreign' => 'sc_course_id'));
    $this->hasOne('T626_Group as Group', array('local' => 's_g_id', 'foreign' => 'g_id'));
  }
}

class T626_Group extends Doctrine_Record
{
  public function setTableDefinition()
  {
    $this->setTableName('T626B_Student_group');

    $this->hasColumn('g_id as id', 'varchar', 30, array (  'primary' => true,));
    $this->hasColumn('g_name as name', 'varchar', 50, array ());
  }
  
  public function setUp()
  {
    $this->hasMany('T626B_Student as Students', 
      array('local' => 'g_id', 'foreign' => 's_id'));
  }
}


class T626_Course extends Doctrine_Record
{
  public function setTableDefinition()
  {
    $this->setTableName('T626_course');

    $this->hasColumn('c_id as id', 'varchar', 20, array (  'primary' => true,));
    $this->hasColumn('c_name as name', 'varchar', 50, array ());
  }
  
  public function setUp()
  {
    $this->hasMany('T626B_Student as Students', array('refClass' => 'T626B_StudentCourse', 'local' => 'sc_course_id', 'foreign' => 'sc_student_id'));
  }
}

class T626B_StudentCourse extends Doctrine_Record
{
  public function setTableDefinition()
  {
    $this->setTableName('T626B_Student_course');

    $this->hasColumn('sc_student_id as student_id', 'varchar', 30, array (  'primary' => true,));
    $this->hasColumn('sc_course_id as course_id', 'varchar', 20, array (  'primary' => true,));
    $this->hasColumn('sc_remark  as remark', 'varchar', 500, array ());
  }
  
  public function setUp()
  {
    $this->hasOne('T626B_Student as Student', array('local' => 'sc_student_id', 'foreign' => 's_id'));
    $this->hasOne('T626_Course as Course', array('local' => 'sc_course_id', 'foreign' => 'c_id'));
  }
}

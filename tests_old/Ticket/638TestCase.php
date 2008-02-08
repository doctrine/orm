<?php

/**
 * Doctrine_Ticket_638_TestCase
 *
 * @package     Doctrine
 * @author      Tamcy <7am.online@gmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */

class Doctrine_Ticket_638_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData() 
    { }

    public function prepareTables()
    {
      $this->tables = array('T638_Student', 'T638_Course', 'T638_StudentCourse');
      parent::prepareTables();
    }

    protected function newCourse($id, $name)
    {
      $course = new T638_Course();
      $course->id = $id;
      $course->name = $name;
      $course->save();
      return $course;
    }

    protected function newStudent($id, $name)
    {
      $u = new T638_Student();
      $u->id = $id;
      $u->name = $name;
      $u->group_id = 1;
      $u->save();
      return $u;
    }

    protected function newStudentCourse($student, $course)
    {
      $sc = new T638_StudentCourse;
      $sc->student_id = $student->id;
      $sc->course_id = $course->id;
      $sc->save();
      return $sc;
    }

    public function testTicket()
    {
      $student1 = $this->newStudent('07090002', 'First Student');
      $course1 = $this->newCourse('MATH001', 'Maths');
      $course2 = $this->newCourse('ENG002', 'English Literature');

      $sc = new T638_StudentCourse;      
      $sc->set('Student', $student1);
      $sc->set('Course', $course1);

      if ($student1->get('id') instanceof T638_StudentCourse)
      {
        $this->fail('Student Id incorrectly replaced!');
      }
      else
      {
        $this->pass();
      }

      if ($student1->get('id') != '07090002')
      {
        $this->fail('Student Id is not correct after assignment!');
      }
      else
      {
        $this->pass();
      }

      if ($course1->get('id') instanceof T638_StudentCourse)
      {
        $this->fail('Course Id incorrectly replaced!');
      }
      else
      {
        $this->pass();
      }

      if ($course1->get('id') != 'MATH001')
      {
        $this->fail('Course Id is not correct after assignment!');
      }
      else
      {
        $this->pass();
      }

      $this->assertEqual($sc->get('student_id'), '07090002');
      $this->assertEqual($sc->get('course_id'), 'MATH001');
      $this->assertIdentical($sc->get('Student'), $student1);
      $this->assertIdentical($sc->get('Course'), $course1);
    }
}


class T638_Student extends Doctrine_Record
{
  public static function initMetadata($class)
  {
    $class->setTableName('T638_student');

    $class->setColumn('s_id as id', 'varchar', 30, array (  'primary' => true,));
    $class->setColumn('s_g_id as group_id', 'varchar', 30, array ('notnull'=>true));
    $class->setColumn('s_name as name', 'varchar', 50, array ('notnull'=>true));
  }
  
}

class T638_Course extends Doctrine_Record
{
  public static function initMetadata($class)
  {
    $class->setTableName('T638_course');

    $class->setColumn('c_id as id', 'varchar', 20, array (  'primary' => true,));
    $class->setColumn('c_name as name', 'varchar', 50, array ('notnull'=>true));
  }

  public function set($fieldName, $value, $load = true)
  {
    parent::set($fieldName, $value, $load);
  }
}

class T638_StudentCourse extends Doctrine_Record
{
  public static function initMetadata($class)
  {
    $class->setTableName('T638_Student_course');

    $class->setColumn('sc_student_id as student_id', 'varchar', 30, array (  'primary' => true,));
    $class->setColumn('sc_course_id as course_id', 'varchar', 20, array (  'primary' => true,));
    $class->setColumn('sc_remark  as remark', 'varchar', 500, array ('notnull'=>true));
    
    $class->hasOne('T638_Student as Student', array('local' => 'sc_student_id', 'foreign' => 's_id'));
    $class->hasOne('T638_Course as Course', array('local' => 'sc_course_id', 'foreign' => 'c_id'));
  }
}


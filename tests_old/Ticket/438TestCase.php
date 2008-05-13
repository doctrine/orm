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
 * Doctrine_Ticket_438_TestCase
 *
 * @package     Doctrine
 * @author      Tamcy <7am.online@gmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Ticket_438_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData() 
    { }

    public function prepareTables()
    {
      $this->tables = array('T438_Student', 'T438_Course', 'T438_StudentCourse');
      parent::prepareTables();
    }

    protected function newCourse($id, $name)
    {
      $course = new T438_Course();
      $course->id = $id;
      $course->name = $name;
      $course->save();
      return $course;
    }

    protected function newStudent($id, $name)
    {
      $u = new T438_Student();
      $u->id = $id;
      $u->name = $name;
      $u->save();
      return $u;
    }

    protected function newStudentCourse($student, $course)
    {
      $sc = new T438_StudentCourse;
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

      $this->newStudentCourse($student1, $course1);
      $this->newStudentCourse($student1, $course2);


      // 1. Fetch relationship on demand (multiple queries)
      $q = new Doctrine_Query();
      $q->from('T438_StudentCourse sc')
        ->where('sc.student_id = ? AND sc.course_id = ?',array('07090002', 'MATH001'));

      $record = $q->execute()->getFirst();
      $this->assertEqual($record->student_id, '07090002');
      $this->assertEqual($record->course_id,  'MATH001');

      $this->assertEqual($record->get('Student')->id, '07090002');
      $this->assertEqual($record->get('Course')->id,  'MATH001');

      // 2. Fetch relationship in single query
      $q = new Doctrine_Query();
      $coll = $q->select('sc.*, s.*, c.*')
        ->from('T438_StudentCourse sc, sc.Student s, sc.Course c')
        ->where('sc.student_id = ? AND sc.course_id = ?',array('07090002', 'MATH001'))
        ->execute();

      $record = $coll->getFirst();
      $this->assertEqual($record->student_id, '07090002');
      $this->assertEqual($record->course_id,  'MATH001');

      $this->assertEqual($record->get('Student')->id, '07090002');
      $this->assertEqual($record->get('Course')->id,  'MATH001');
    }
}


class T438_Student extends Doctrine_Entity
{
  public static function initMetadata($class)
  {
    $class->setTableName('t438_student_record');
    $class->setColumn('s_id as id', 'varchar', 30, array (  'primary' => true,));
    $class->setColumn('s_name as name', 'varchar', 50, array ());
    $class->hasMany('T438_Course as StudyCourses', array('refClass' => 'T438_StudentCourse', 'local' => 'sc_student_id', 'foreign' => 'sc_course_id'));
  }
}


class T438_Course extends Doctrine_Entity
{
  public static function initMetadata($class)
  {
    $class->setTableName('t438_course');
    $class->setColumn('c_id as id', 'varchar', 20, array (  'primary' => true,));
    $class->setColumn('c_name as name', 'varchar', 50, array ());
    $class->hasMany('T438_Student as Students', array('refClass' => 'T438_StudentCourse', 'local' => 'sc_course_id', 'foreign' => 'sc_student_id'));
  }
}

class T438_StudentCourse extends Doctrine_Entity
{
  public static function initMetadata($class)
  {
    $class->setTableName('t438_student_course');
    $class->setColumn('sc_student_id as student_id', 'varchar', 30, array (  'primary' => true,));
    $class->setColumn('sc_course_id as course_id', 'varchar', 20, array (  'primary' => true,));
    $class->setColumn('sc_remark  as remark', 'varchar', 500, array ());
    $class->hasOne('T438_Student as Student', array('local' => 'sc_student_id', 'foreign' => 's_id'));
    $class->hasOne('T438_Course as Course', array('local' => 'sc_course_id', 'foreign' => 'c_id'));
  }
}

<?php
/*
 *  $Id$
 * %s
 *
 * @package     Doctrine
 * @author      Lloyd Leung (lleung at carlton decimal ca)
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */



class Member extends Doctrine_Record
{
  const DATABASE_NAME = 'doctrine';

  public function setTableDefinition()
  {
    $this->setTableName('members');

    $this->hasColumn('pin', 'string', 8, array('primary'=>true, ));
    $this->hasColumn('name', 'string', 254, array('notblank'=>true, ));
  }

  public function setUp()
  {
    $this->hasMany('NewsBlast as news_blasts', 'NewsBlast.pin');
  }

}


class NewsBlast extends Doctrine_Record
{
  const DATABASE_NAME = 'doctrine';

  public function setTableDefinition()
  {
    $this->setTableName('p2m_newsblast');
    $this->hasColumn('subprogram_id', 'integer', 10, array());
    $this->hasColumn('title', 'string', 254, array());
  }

  public function setUp()
  {
    $this->hasOne('SubProgram as subprogram', 'NewsBlast.subprogram_id', 'id');
    $this->hasOne('Member as member', 'NewsBlast.pin', 'pin');
  }

}


class SubProgram extends Doctrine_Record
{
  const DATABASE_NAME = 'doctrine';

  public function setTableDefinition()
  {
    $this->setTableName('p2m_subprogram');
    $this->hasColumn('name', 'string', 50, array());
  }

  public function setUp()
  {
    $this->hasMany('Member as members', 'Member.subprogram_id');
  }
}



class Doctrine_Ticket343_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData() 
    { }
    public function prepareTables()
    { }
    public function testForeignPkNonId()
    {

		die('happy!');

        $member = new Member();
        $subprogram = new SubProgram();
        $newsblast = new NewsBlast();

        $member->set('name','hello world!');
        $member->set('pin', 'demo1100');

        $subprogram->set('name', 'SoemthingNew');

        $newsblast->set('pin', $member);
        $newsblast->set('subprogram', $subprogram);
        $newsblast->set('title', 'Some title here');

        $newsblast->save();

        $test->assertEqual($newsblast['subprogram'], 'SomethingNew');
        $test->assertEqual($newsblast['member']['pin'], 'demo1100');
        $test->assertEqual($newsblast['member']['name'], 'hello world!');
        $test->assertEqual(0,1);

    }
}

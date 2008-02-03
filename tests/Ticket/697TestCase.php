<?php
/**
 * Doctrine_Ticket_697_TestCase
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */

class Doctrine_Ticket_697_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData() 
    { }

    public function prepareTables()
    {
        $this->tables = array('T697_Person', 'T697_User');
        parent::prepareTables();
    }

    public function testIdsAreSetWhenSavingSubclassInstancesInCTI()
    {        
        $p = new T697_Person();
        $p['name']='Rodrigo';
        $p->save();
        $this->assertEqual(1, $p->id);

        $u = new T697_User();
        $u['name']='Fernandes';
        $u['password']='Doctrine RULES';
        $u->save();
        $this->assertEqual(2, $u->id);
    }
}

class T697_Person extends Doctrine_Record
{
    public static function initMetadata($class)
    {
        $class->setInheritanceType(Doctrine::INHERITANCETYPE_JOINED, array(
                'discriminatorColumn' => 'dtype',
                'discriminatorMap' => array(
                        1 => 'T697_Person', 2 => 'T697_User'
                        )
                ));
        $class->setSubclasses(array('T697_User'));
        $class->setTableName('t697_person');
        $class->setColumn('name', 'string', 30);
        $class->setColumn('dtype', 'integer', 4);
    }
}

//Class table inheritance
class T697_User extends T697_Person {
    public static function initMetadata($class)
    {
        $class->setTableName('t697_user');
        $class->setColumn('password', 'string', 30);
    }
}

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
    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 30);
    }
}

//Class table inheritance
class T697_User extends T697_Person {
    public function setTableDefinition()
    {
        $this->hasColumn('password', 'string', 30);
    }
}

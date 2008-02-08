<?php

/**
 * Doctrine_Ticket_587_TestCase
 *
 * @package     Doctrine
 * @author      Joaquin Bravo <jackbravo@gmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */

class Doctrine_Ticket_576_TestCase extends Doctrine_UnitTestCase
{
    public function prepareTables()
    {
        $this->tables = array('Entity');
        parent::prepareTables();
    }

    public function prepareData() { }

    public function testInit()
    {
        $entity = new Entity();
        $entity->name = 'myname';
        $entity->loginname = 'test';
        $entity->save();
    }

    public function testBug()
    {
        // load our user and our collection of pages
        $user = Doctrine_Query::create()->from('Entity')->fetchOne();
        $this->assertEqual($user->name, 'myname');
        $this->assertEqual($user->loginname, 'test');

        $user->name = null;
        $this->assertEqual($user->name, null);

        $data = Doctrine_Query::create()
            ->select('name')
            ->from('Entity')
            ->fetchOne(array(), Doctrine::FETCH_ARRAY);

        $user->hydrate($data);
        $this->assertEqual($user->name, 'myname');
        $this->assertEqual($user->loginname, 'test'); // <<----- this is what the bug is about
    }
}

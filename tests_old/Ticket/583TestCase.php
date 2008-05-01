<?php

/**
 * Doctrine_Ticket_587_TestCase
 *
 * @package     Doctrine
 * @author      Joaquin Bravo <jackbravo@gmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */

class Doctrine_Ticket_583_TestCase extends Doctrine_UnitTestCase
{
    public function prepareTables()
    {
        $this->tables = array('Entity');
        parent::prepareTables();
    }

    public function prepareData() { }

    public function testBug()
    {
        $entity = new Entity();
        $entity->name = 'myname';
        $entity->save();

        // load our user and our collection of pages
        $user = Doctrine_Query::create()->select('id')->from('Entity')->fetchOne();       
        $this->assertEqual($user->name, 'myname');

        // load our user and our collection of pages
        $user = Doctrine_Query::create()->select('*')->from('Entity')->fetchOne();
        $this->assertEqual($user->name, 'myname');
        
    }
}

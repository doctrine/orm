<?php

/**
 * Doctrine_Record_Inheritance_TestCase
 *
 * This test case demonstrates the use of inheritance involving subclasses of
 * Doctrine_Record.  This type of inheritance is heavily used in sfDoctrine,
 * and as new inheritance-related features get added to Doctrine it seems to
 * be an area where subtle breakage can sneak in.
 *
 * @package     Doctrine
 * @author      David Brewer <dbrewer@secondstory.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */

class Doctrine_Record_Inheritance_TestCase extends Doctrine_UnitTestCase
{
    public function prepareTables()
    {
        $this->tables = array_merge($this->tables, array('SymfonyRecord'));
        parent::prepareTables();
    }
    public function prepareData()
    {
        parent::prepareData();
    }

    public function testInit()
    {
        $record = new SymfonyRecord();
        $record['name'] = 'Test me';
        $record->save();
    }

    public function testInstantiatingRecordWithAbstractParents()
    {
        // load our record
        $record = Doctrine_Query::create()->query(
            'SELECT * FROM SymfonyRecord r', array())->getFirst();

        // did we get a record object?
        $this->assertTrue($record instanceof SymfonyRecord);
        $this->assertTrue($record->exists());

        // does it have the appropriate parentage?
        $this->assertTrue($record instanceof PluginSymfonyRecord);
        $this->assertTrue($record instanceof BaseSymfonyRecord);
        $this->assertTrue($record instanceof Doctrine_Record);

        // does it have the expected data?
        $this->assertEqual($record['name'], 'Test me');
        

    }
}

<?php

/**
 * Doctrine_Ticket_428_TestCase
 *
 * @package     Doctrine
 * @author      Tamcy <7am.online@gmail.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Ticket_428_TestCase extends Doctrine_UnitTestCase
{
    private $_albums;
    
    public function prepareTables()
    {
        $this->tables = array('Album', 'Song');
        parent::prepareTables();
    }
    
    public function prepareData()
    {
    }

    public function testInitData() 
    {
        // Since the tests do a $this->objTable()->clear() before each method call
        // using the User model is not recommended for this test
        $albums = new Doctrine_Collection('Album');

        $albums[0]->name = 'Revolution';
        $albums[0]->Song[0]->title = 'Revolution';
        $albums[0]->Song[1]->title = 'Hey Jude';
        $albums[0]->Song[2]->title = 'Across the Universe';
        $albums[0]->Song[3]->title = 'Michelle';
        $albums->save();
        $this->assertEqual(count($albums[0]->Song), 4);
        $this->_albums = $albums;
    }

    public function testAggregateValueMappingSupportsLeftJoins() 
    {
        foreach ($this->_albums as $album) {
            $album->clearRelated();
        }
        
        $q = new Doctrine_Query();

        $q->select('a.name, COUNT(s.id) count')->from('Album a')->leftJoin('a.Song s')->groupby('a.id');
        $albums = $q->execute();
        
        // Should not reuse the existing collection in this case
        $this->assertEqual(count($albums[0]->Song), 1);

        try {
            // Collection[0] should refer to the object with aggregate value
            $this->assertEqual($albums[0]['Song'][0]['count'], 4);
        } catch (Exception $e) {
            $this->fail('count aggregate value should be available');
        }
    }
}

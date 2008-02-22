<?php

/**
 * Doctrine_Ticket_587_TestCase
 *
 * @package     Doctrine
 * @author      David Brewer <dbrewer@secondstory.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */

class Doctrine_Ticket_587_TestCase extends Doctrine_UnitTestCase
{
    public function prepareTables()
    {
        $this->tables = array_merge($this->tables, array('BookmarkUser', 'Bookmark', 'Page'));
        parent::prepareTables();
    }
    public function prepareData()
    {
        parent::prepareData();
    }

    public function testInit()
    {
        $user = new BookmarkUser();
        $user['name'] = 'Anonymous';
        $user->save();

        $pages = new Doctrine_Collection('Page');
        $pages[0]['name'] = 'Yahoo';
        $pages[0]['url'] = 'http://www.yahoo.com';
        $pages->save();

        $this->assertEqual(count($pages), 1);
    }

    /**
     * This test case demonstrates an issue with the identity case in the
     * Doctrine_Table class.  The brief summary is that if you create a
     * record, then delete it, then create another record with the same
     * primary keys, the record can get into a state where it is in the
     * database but may appear to be marked as TCLEAN under certain
     * circumstances (such as when it comes back as part of a collection).
     * This makes the $record->exists() method return false, which prevents
     * the record from being deleted among other things.
     */
    public function testIdentityMapAndRecordStatus()
    {
        // load our user and our collection of pages
        $user = Doctrine_Query::create()->query(
            'SELECT * FROM BookmarkUser u WHERE u.name=?', array('Anonymous')
        )->getFirst();
        $pages = Doctrine_Query::create()->query('SELECT * FROM Page');

        // bookmark the pages (manually)
        foreach ($pages as $page) {
            $bookmark = new Bookmark();
            $bookmark['page_id'] = $page['id'];
            $bookmark['user_id'] = $user['id'];
            $bookmark->save();
        }

        // select all bookmarks
        $bookmarks = Doctrine_Manager::connection()->query(
            'SELECT * FROM Bookmark b'
        );
        $this->assertEqual(count($bookmarks), 1);

        // verify that they all exist
        foreach ($bookmarks as $bookmark) {
            $this->assertTrue($bookmark->exists());
        }

        // now delete them all.
        $user['Bookmarks']->delete();

        // verify count when accessed directly from database
        $bookmarks = Doctrine_Query::create()->query(
            'SELECT * FROM Bookmark'
        );
        $this->assertEqual(count($bookmarks), 0);

        // now recreate bookmarks and verify they exist:
        foreach ($pages as $page) {
            $bookmark = new Bookmark();
            $bookmark['page_id'] = $page['id'];
            $bookmark['user_id'] = $user['id'];
            $bookmark->save();
        }

        // select all bookmarks for the user
        $bookmarks = Doctrine_Manager::connection()->query(
            'SELECT * FROM Bookmark b'
        );
        $this->assertEqual(count($bookmarks), 1);

        // verify that they all exist
        foreach ($bookmarks as $bookmark) {
            $this->assertTrue($bookmark->exists());
        }
    }
}

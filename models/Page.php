<?php
class Page extends Doctrine_Record
{

    public function setUp()
    {
    	$this->hasMany('Bookmark as Bookmarks',
                        array('local' => 'id',
                              'foreign' => 'page_id'));
    }

    public function setTableDefinition()
    {
        $this->hasColumn('name', 'string', 30);
        $this->hasColumn('url', 'string', 100);
    }
}

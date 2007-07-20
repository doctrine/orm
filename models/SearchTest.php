<?php
class SearchTest extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('title', 'string', 100);
        $this->hasColumn('content', 'string');
    }
    public function setUp()
    {
    	$options = array('generateFiles' => false,
                         'fields' => array('title', 'content'));

        $this->loadTemplate('Doctrine_Search_Template', $options);
    }
}

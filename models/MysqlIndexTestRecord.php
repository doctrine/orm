<?php
class MysqlIndexTestRecord extends Doctrine_Record
{
    public function setTableDefinition() 
    {
        $this->hasColumn('name', 'string', null);
        $this->hasColumn('code', 'integer', 4);
        $this->hasColumn('content', 'string', 4000);

        $this->index('content',  array('fields' => 'content', 'type' => 'fulltext'));
        $this->index('namecode', array('fields' => array('name', 'code'),
                                       'type'   => 'unique'));

        $this->option('type', 'MYISAM');

    }
}

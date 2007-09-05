<?php
class Album extends Doctrine_Record
{
    public function setUp()
    {
        $this->hasMany('Song', array('local' => 'id', 'foreign' => 'album_id'));
        $this->hasOne('User', array('local' => 'user_id',
                                    'foreign' => 'id',
                                    'onDelete' => 'CASCADE'));
    }
    public function setTableDefinition()
    {
        $this->hasColumn('user_id', 'integer');
        $this->hasColumn('name', 'string',20);
    }
}


<?php
class MyUser2 extends Doctrine_Record
{  
    public function setTableDefinition()
    {
        $this->setTableName('my_user');

        $this->hasColumn('id', 'integer', 4, array (  'primary' => true,  'autoincrement' => true,));
        $this->hasColumn('username', 'string', 128, array (  'notnull' => true,));
        $this->hasColumn('algorithm', 'string', 128, array (  'default' => 'sha1',  'notnull' => true,));
        $this->hasColumn('salt', 'string', 128, array (  'notnull' => true,));
        $this->hasColumn('password', 'string', 128, array (  'notnull' => true,));
        $this->hasColumn('created_at', 'timestamp', null, array ());
        $this->hasColumn('last_login', 'timestamp', null, array ());
        $this->hasColumn('is_active', 'boolean', null, array (  'default' => 1,  'notnull' => true,));
        $this->hasColumn('is_super_admin', 'boolean', null, array (  'default' => 0,  'notnull' => true,));
    }

    public function setUp()
    {
        $this->hasMany('MyGroup as groups', array('refClass' => 'MyUserGroup', 'local' => 'user_id', 'foreign' => 'group_id'));
    }  
}

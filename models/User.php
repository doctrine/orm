<?php

require_once('Entity.php');

class UserTable extends Doctrine_Table { }

class User extends Entity
{
    public function setUp() 
    {
        parent::setUp();
        $this->hasMany('Address', array('local' => 'user_id', 
                                        'foreign' => 'address_id',
                                        'refClass' => 'EntityAddress'));
        $this->ownsMany('Album', 'Album.user_id');
        $this->ownsMany('Book', 'Book.user_id');
        $this->hasMany('Group', 'Groupuser.group_id');
//        $this->option('inheritanceMap', array('type' => 0));
    }
    /** Custom validation */
    public function validate() 
    {
        // Allow only one name!
        if ($this->name !== 'The Saint') {
            $this->errorStack()->add('name', 'notTheSaint');
        }
    }
    public function validateOnInsert() 
    {
        if ($this->password !== 'Top Secret') {
            $this->errorStack()->add('password', 'pwNotTopSecret');
        }
    }
    public function validateOnUpdate() 
    {
        if ($this->loginname !== 'Nobody') {
            $this->errorStack()->add('loginname', 'notNobody');
        }
    }
}


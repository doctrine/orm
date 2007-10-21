<?php

require_once('Entity.php');

// UserTable doesn't extend Doctrine_Table -> Doctrine_Connection
// won't initialize grouptable when Doctrine_Connection->getTable('User') is called
class UserTable extends Doctrine_Table { }

class User extends Entity
{
    public function setUp() 
    {
        parent::setUp();
        $this->hasMany('Address', array(
            'local' => 'user_id', 
            'foreign' => 'address_id',
            'refClass' => 'EntityAddress',
        ));
        $this->hasMany('Address as Addresses', array(
            'local' => 'user_id', 
            'foreign' => 'address_id',
            'refClass' => 'EntityAddress',
        ));
        $this->hasMany('Album', array('local' => 'id', 'foreign' => 'user_id'));
        $this->hasMany('Book', array('local' => 'id', 'foreign' => 'user_id'));
        $this->hasMany('Group', array(
            'local' => 'user_id', 
            'foreign' => 'group_id',
            'refClass' => 'Groupuser',
        ));
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


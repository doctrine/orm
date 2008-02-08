<?php

require_once('Entity.php');


class User extends Entity
{
    public static function initMetadata($class) 
    {
        $class->hasMany('Address', array(
            'local' => 'user_id', 
            'foreign' => 'address_id',
            'refClass' => 'EntityAddress',
        ));
        $class->hasMany('Address as Addresses', array(
            'local' => 'user_id', 
            'foreign' => 'address_id',
            'refClass' => 'EntityAddress',
        ));
        $class->hasMany('Album', array('local' => 'id', 'foreign' => 'user_id'));
        $class->hasMany('Book', array('local' => 'id', 'foreign' => 'user_id'));
        $class->hasMany('Group', array(
            'local' => 'user_id', 
            'foreign' => 'group_id',
            'refClass' => 'Groupuser'
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


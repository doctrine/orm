<?php
require_once 'lib/DoctrineTestInit.php';
 
class Orm_Entity_AccessorTestCase extends Doctrine_OrmTestCase
{
    public function testGetterSetterOverride()
    {
        $em = new Doctrine_EntityManager(new Doctrine_Connection_Mock());
        
        $entity1 = new CustomAccessorMutatorTestEntity();
        $entity1->username = 'romanb';
        $this->assertEquals('romanb?!', $entity1->username);
        
        $entity2 = new MagicAccessorMutatorTestEntity();
        $entity2->username = 'romanb';
        $this->assertEquals('romanb?!', $entity1->username);
        
    }
}

class CustomAccessorMutatorTestEntity extends Doctrine_Entity
{
    public static function initMetadata($class) 
    {
        $class->mapColumn('id', 'integer', 4, array('primary'));
        $class->mapColumn('username', 'string', 50, array(
                'accessor' => 'getUsernameCustom',
                'mutator' => 'setUsernameCustom'));
    }
    
    public function getUsernameCustom()
    {
        return $this->_rawGetField('username') . "!";
    }
    
    public function setUsernameCustom($username)
    {
        $this->_rawSetField('username', $username . "?");
    }
}

class MagicAccessorMutatorTestEntity extends Doctrine_Entity
{
    public static function initMetadata($class) 
    {
        $class->mapColumn('id', 'integer', 4, array('primary'));
        $class->mapColumn('username', 'string', 50, array());
    }
    
    public function getUsername()
    {
        return $this->_rawGetField('username') . "!";
    }
    
    public function setUsername($username)
    {
        $this->_rawSetField('username', $username . "?");
    } 
}
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
        $class->mapColumn('username', 'string', 50, array(
                'accessor' => 'getUsernameCustom',
                'mutator' => 'setUsernameCustom'));
    }
    
    public function getUsernameCustom()
    {
        return $this->rawGetField('username') . "!";
    }
    
    public function setUsernameCustom($username)
    {
        $this->rawSetField('username', $username . "?");
    }
}

class MagicAccessorMutatorTestEntity extends Doctrine_Entity
{
    public static function initMetadata($class) 
    {
        $class->mapColumn('username', 'string', 50, array());
    }
    
    public function getUsername()
    {
        return $this->rawGetField('username') . "!";
    }
    
    public function setUsername($username)
    {
        $this->rawSetField('username', $username . "?");
    } 
}
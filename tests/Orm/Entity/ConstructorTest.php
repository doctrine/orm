<?php
require_once 'lib/DoctrineTestInit.php';
 
class Orm_Entity_ConstructorTest extends Doctrine_OrmTestCase
{
    public function testFieldInitializationInConstructor()
    {
        $entity = new ConstructorTestEntity1("romanb");
        $this->assertTrue($entity->isTransient());
        $this->assertEquals("romanb", $entity->username);        
    }
}

class ConstructorTestEntity1 extends Doctrine_Entity
{
    public function __construct($username = null)
    {
        parent::__construct();
        if ($this->isTransient()) {
            $this->username = $username;
        }
    }
    
    /* The mapping definition */
    public static function initMetadata($class) 
    {
        $class->mapColumn('username', 'string', 50, array());
    }
}

?>
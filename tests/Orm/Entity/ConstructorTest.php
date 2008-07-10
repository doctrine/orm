<?php
require_once 'lib/DoctrineTestInit.php';
 
class Orm_Entity_ConstructorTest extends Doctrine_OrmTestCase
{
    public function testFieldInitializationInConstructor()
    {
        $entity = new ConstructorTestEntity1("romanb");
        $this->assertTrue($entity->isNew());
        $this->assertEquals("romanb", $entity->username);        
    }
}

class ConstructorTestEntity1 extends Doctrine_Entity
{
    public function __construct($username = null)
    {
        parent::__construct();
        if ($this->isNew()) {
            $this->username = $username;
        }
    }
    
    /* The mapping definition */
    public static function initMetadata($class) 
    {
        $class->mapColumn('id', 'integer', 4, array('primary'));
        $class->mapColumn('username', 'string', 50, array());
    }
}

?>
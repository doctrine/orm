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

class ConstructorTestEntity1 extends Doctrine_ORM_Entity
{
    public function __construct($username = null)
    {
        parent::__construct();
        if ($this->isNew()) {
            $this->username = $username;
        }
    }
    
    /* The mapping definition */
    public static function initMetadata($mapping) 
    {
        $mapping->mapField(array(
            'fieldName' => 'id',
            'type' => 'integer',
            'length' => 4,
            'id' => true
        ));
        $mapping->mapField(array(
            'fieldName' => 'username',
            'type' => 'string',
            'length' => 50
        ));
    }
}

?>
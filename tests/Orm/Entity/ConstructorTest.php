<?php
require_once 'lib/DoctrineTestInit.php';
 
class Orm_Entity_ConstructorTest extends Doctrine_OrmTestCase
{
    public function testFieldInitializationInConstructor()
    {
        $entity = new ConstructorTestEntity1("romanb");
        $this->assertEquals("romanb", $entity->username);        
    }
}

class ConstructorTestEntity1
{
    public $id;
    public $username;

    public function __construct($username = null)
    {
        if ($username !== null) {
            $this->username = $username;
        }
    }
    
    /* The mapping definition */
    public static function initMetadata($mapping) 
    {
        /*
        $mapping->addFieldMetadata('id', array(
            'doctrine.id' => true,
            'doctrine.validator.constraints' => array('notnull', 'unique')
        ));
        */

        $mapping->mapField(array(
            'fieldName' => 'id',
            'type' => 'integer',
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
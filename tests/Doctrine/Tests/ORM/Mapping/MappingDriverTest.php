<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\Driver\XmlDriver,
    Doctrine\ORM\Mapping\Driver\YamlDriver;

require_once __DIR__ . '/../../TestInit.php';
 
class MappingDriverTest extends \Doctrine\Tests\OrmTestCase
{
    public function testXmlMapping()
    {
        $className = 'Doctrine\Tests\ORM\Mapping\User';
        $xmlDriver = new XmlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'xml');
        
        $class = new ClassMetadata($className);
        
        $this->assertFalse($xmlDriver->isTransient($className));
        
        $xmlDriver->loadMetadataForClass($className, $class);
        
        $this->_testUserClassMapping($class);
    }
    
    public function testYamlMapping()
    {
        $className = 'Doctrine\Tests\ORM\Mapping\User';
        $yamlDriver = new YamlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'yaml');
        
        $class = new ClassMetadata($className);
        
        $this->assertFalse($yamlDriver->isTransient($className));
        
        $yamlDriver->loadMetadataForClass($className, $class);
        
        $this->_testUserClassMapping($class);
    }
    
    public function testXmlGetAllClassNames()
    {
        $className = 'Doctrine\Tests\ORM\Mapping\User';
        $xmlDriver = new XmlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'xml');
        
        $class = new ClassMetadata($className);
        
        $classNames = $xmlDriver->getAllClassNames();
        
        $this->assertEquals($className, $classNames[0]);
        $this->assertEquals(1, count($classNames));
    }
    
    private function _testUserClassMapping(ClassMetadata $class)
    {        
        $this->assertEquals('cms_users', $class->getTableName());
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $class->getInheritanceType());
        $this->assertEquals(2, count($class->fieldMappings));
        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['name']));
        $this->assertEquals('string', $class->fieldMappings['name']['type']);
        $this->assertTrue($class->fieldMappings['name']['nullable']);
        $this->assertTrue($class->fieldMappings['name']['unique']);
        $this->assertEquals(array('id'), $class->identifier);
        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_AUTO, $class->getIdGeneratorType());
        
        $this->assertEquals(3, count($class->associationMappings));
        $this->assertEquals(1, count($class->inverseMappings));
        
        $this->assertTrue($class->associationMappings['address'] instanceof \Doctrine\ORM\Mapping\OneToOneMapping);
        $this->assertTrue(isset($class->associationMappings['address']));
        $this->assertTrue($class->associationMappings['address']->isOwningSide);
        // Check cascading
        $this->assertTrue($class->associationMappings['address']->isCascadeRemove);
        $this->assertFalse($class->associationMappings['address']->isCascadePersist);
        $this->assertFalse($class->associationMappings['address']->isCascadeRefresh);
        $this->assertFalse($class->associationMappings['address']->isCascadeDetach);
        $this->assertFalse($class->associationMappings['address']->isCascadeMerge);
        
        $this->assertTrue($class->associationMappings['phonenumbers'] instanceof \Doctrine\ORM\Mapping\OneToManyMapping);
        $this->assertTrue(isset($class->associationMappings['phonenumbers']));
        $this->assertFalse($class->associationMappings['phonenumbers']->isOwningSide);
        $this->assertTrue($class->associationMappings['phonenumbers']->isInverseSide());
        $this->assertTrue($class->associationMappings['phonenumbers']->isCascadePersist);
        $this->assertFalse($class->associationMappings['phonenumbers']->isCascadeRemove);
        $this->assertFalse($class->associationMappings['phonenumbers']->isCascadeRefresh);
        $this->assertFalse($class->associationMappings['phonenumbers']->isCascadeDetach);
        $this->assertFalse($class->associationMappings['phonenumbers']->isCascadeMerge);
        
        $this->assertTrue($class->associationMappings['groups'] instanceof \Doctrine\ORM\Mapping\ManyToManyMapping);
        $this->assertTrue(isset($class->associationMappings['groups']));
        $this->assertTrue($class->associationMappings['groups']->isOwningSide);
        $this->assertEquals(count($class->lifecycleCallbacks), 2);
        $this->assertEquals($class->lifecycleCallbacks['prePersist'][0], 'doStuffOnPrePersist');
        $this->assertEquals($class->lifecycleCallbacks['postPersist'][0], 'doStuffOnPostPersist');
        // Make sure that cascade-all works as expected
        $this->assertTrue($class->associationMappings['groups']->isCascadeRemove);
        $this->assertTrue($class->associationMappings['groups']->isCascadePersist);
        $this->assertTrue($class->associationMappings['groups']->isCascadeRefresh);
        $this->assertTrue($class->associationMappings['groups']->isCascadeDetach);
        $this->assertTrue($class->associationMappings['groups']->isCascadeMerge);

        // Non-Nullability of Join Column
        $this->assertFalse($class->associationMappings['groups']->joinTable['joinColumns'][0]['nullable']);
        $this->assertFalse($class->associationMappings['groups']->joinTable['joinColumns'][0]['unique']);
    }
}

class User {
    private $id;
    private $name;
    private $address;
    private $phonenumbers;
    private $groups;
    
    // ... rest of code omitted, irrelevant for the mapping tests

    public function doStuffOnPrePersist()
    {
    }

    public function doStuffOnPostPersist()
    {
        
    }
}
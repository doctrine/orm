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
        $xmlDriver = new XmlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'xml', XmlDriver::FILE_PER_CLASS);
        
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
    
    public function testXmlPreloadMode()
    {
        $className = 'Doctrine\Tests\ORM\Mapping\User';
        $xmlDriver = new XmlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'xml');
        $class = new ClassMetadata($className);
        
        $classNames = $xmlDriver->preload();
        
        $this->assertEquals($className, $classNames[0]);
        $this->assertEquals(1, count($xmlDriver->getPreloadedElements()));
        
        $xmlDriver->loadMetadataForClass($className, $class);
        
        $this->assertEquals(0, count($xmlDriver->getPreloadedElements()));
    }
    
    public function testYamlPreloadMode()
    {
        $className = 'Doctrine\Tests\ORM\Mapping\User';
        $yamlDriver = new YamlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'yaml');
        $class = new ClassMetadata($className);
        
        $classNames = $yamlDriver->preload();
        
        $this->assertEquals($className, $classNames[0]);
        $this->assertEquals(1, count($yamlDriver->getPreloadedElements()));
        
        $yamlDriver->loadMetadataForClass($className, $class);
        
        $this->assertEquals(0, count($yamlDriver->getPreloadedElements()));
    }
    
    private function _testUserClassMapping($class)
    {        
        $this->assertEquals('cms_users', $class->getTableName());
        $this->assertEquals(ClassMetadata::INHERITANCE_TYPE_NONE, $class->getInheritanceType());
        $this->assertEquals(2, count($class->fieldMappings));
        $this->assertTrue(isset($class->fieldMappings['id']));
        $this->assertTrue(isset($class->fieldMappings['name']));
        $this->assertEquals('string', $class->fieldMappings['name']['type']);
        $this->assertEquals(array('id'), $class->identifier);
        $this->assertEquals(ClassMetadata::GENERATOR_TYPE_AUTO, $class->getIdGeneratorType());
        
        $this->assertEquals(3, count($class->associationMappings));
        $this->assertEquals(1, count($class->inverseMappings));
        
        $this->assertTrue($class->associationMappings['address'] instanceof \Doctrine\ORM\Mapping\OneToOneMapping);
        $this->assertTrue(isset($class->associationMappings['address']));
        $this->assertTrue($class->associationMappings['address']->isOwningSide);
        
        $this->assertTrue($class->associationMappings['phonenumbers'] instanceof \Doctrine\ORM\Mapping\OneToManyMapping);
        $this->assertTrue(isset($class->associationMappings['phonenumbers']));
        $this->assertFalse($class->associationMappings['phonenumbers']->isOwningSide);
        $this->assertTrue($class->associationMappings['phonenumbers']->isInverseSide());
        $this->assertTrue($class->associationMappings['phonenumbers']->isCascadePersist);
        
        $this->assertTrue($class->associationMappings['groups'] instanceof \Doctrine\ORM\Mapping\ManyToManyMapping);
        $this->assertTrue(isset($class->associationMappings['groups']));
        $this->assertTrue($class->associationMappings['groups']->isOwningSide);
        $this->assertEquals(count($class->lifecycleCallbacks), 2);
        $this->assertEquals($class->lifecycleCallbacks['prePersist'][0], 'doStuffOnPrePersist');
        $this->assertEquals($class->lifecycleCallbacks['postPersist'][0], 'doStuffOnPostPersist');
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
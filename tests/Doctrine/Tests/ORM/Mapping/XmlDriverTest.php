<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\XmlDriver;

require_once __DIR__ . '/xml/User.php';
require_once __DIR__ . '/../../TestInit.php';
 
class XmlDriverTest extends \Doctrine\Tests\OrmTestCase
{
    public function testFilePerClassMapping()
    {
        $className = 'XmlMappingTest\User';
        $xmlDriver = new XmlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'xml', XmlDriver::FILE_PER_CLASS);
        
        $class = new ClassMetadata($className);
        
        $this->assertFalse($xmlDriver->isTransient($className));
        
        $xmlDriver->loadMetadataForClass($className, $class);
        
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
        $this->assertTrue($class->associationMappings['phonenumbers']->isCascadeSave);
        
        $this->assertTrue($class->associationMappings['groups'] instanceof \Doctrine\ORM\Mapping\ManyToManyMapping);
        $this->assertTrue(isset($class->associationMappings['groups']));
        $this->assertTrue($class->associationMappings['groups']->isOwningSide);
        
    }
    
    public function testPreloadMode()
    {
        $className = 'XmlMappingTest\User';
        $xmlDriver = new XmlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'xml');
        $class = new ClassMetadata($className);
        
        $classNames = $xmlDriver->preload();
        
        $this->assertEquals($className, $classNames[0]);
        $this->assertEquals(1, count($xmlDriver->getPreloadedXmlElements()));
        
        $xmlDriver->loadMetadataForClass($className, $class);
        
        $this->assertEquals(0, count($xmlDriver->getPreloadedXmlElements()));
    }
}
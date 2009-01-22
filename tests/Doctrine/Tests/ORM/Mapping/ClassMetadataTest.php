<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;

require_once dirname(__FILE__) . '/../../TestInit.php';
 
class ClassMetadataTest extends \Doctrine\Tests\OrmTestCase
{
    public function testClassMetadataInstanceSerialization() {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');

        // Test initial state
        $this->assertTrue(count($cm->getReflectionProperties()) == 0);
        $this->assertTrue($cm->getReflectionClass() instanceof \ReflectionClass);
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $cm->getClassName());
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $cm->getRootClassName());
        $this->assertEquals(array(), $cm->getSubclasses());
        $this->assertEquals(array(), $cm->getParentClasses());

        // Customize state
        $cm->setSubclasses(array("One", "Two", "Three"));
        $cm->setParentClasses(array("UserParent"));
        $cm->setCustomRepositoryClass("UserRepository");
        $cm->setDiscriminatorColumn(array('name' => 'disc', 'type' => 'integer'));
        $cm->mapOneToOne(array('fieldName' => 'phonenumbers', 'targetEntity' => 'Bar', 'mappedBy' => 'foo'));
        $this->assertTrue($cm->getAssociationMapping('phonenumbers') instanceof \Doctrine\ORM\Mapping\OneToOneMapping);
        $this->assertEquals(1, count($cm->getAssociationMappings()));

        $serialized = serialize($cm);
        $cm = unserialize($serialized);

        // Check state
        $this->assertTrue(count($cm->getReflectionProperties()) > 0);
        $this->assertTrue($cm->getReflectionClass() instanceof \ReflectionClass);
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $cm->getClassName());
        $this->assertEquals('UserParent', $cm->getRootClassName());
        $this->assertEquals(array('One', 'Two', 'Three'), $cm->getSubclasses());
        $this->assertEquals(array('UserParent'), $cm->getParentClasses());
        $this->assertEquals('UserRepository', $cm->getCustomRepositoryClass());
        $this->assertEquals(array('name' => 'disc', 'type' => 'integer'), $cm->getDiscriminatorColumn());
        $this->assertTrue($cm->getAssociationMapping('phonenumbers') instanceof \Doctrine\ORM\Mapping\OneToOneMapping);
        $this->assertEquals(1, count($cm->getAssociationMappings()));
        $oneOneMapping = $cm->getAssociationMapping('phonenumbers');
        $this->assertEquals('phonenumbers', $oneOneMapping->getSourceFieldName());
        $this->assertEquals('Bar', $oneOneMapping->getTargetEntityName());
    }
    
}
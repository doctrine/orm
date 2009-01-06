<?php

#namespace Doctrine\Tests\ORM\Mapping;

require_once 'lib/DoctrineTestInit.php';
 
class Orm_Mapping_ClassMetadataTest extends Doctrine_OrmTestCase
{
    public function testClassMetadataInstanceSerialization() {
        $cm = new Doctrine_ORM_Mapping_ClassMetadata('CmsUser');

        // Test initial state
        $this->assertTrue(count($cm->getReflectionProperties()) > 0);
        $this->assertTrue($cm->getReflectionClass() instanceof ReflectionClass);
        $this->assertEquals('CmsUser', $cm->getClassName());
        $this->assertEquals('CmsUser', $cm->getRootClassName());
        $this->assertEquals(array(), $cm->getSubclasses());
        $this->assertEquals(array(), $cm->getParentClasses());

        // Customize state
        $cm->setSubclasses(array("One", "Two", "Three"));
        $cm->setParentClasses(array("UserParent"));
        $cm->setCustomRepositoryClass("UserRepository");
        $cm->setDiscriminatorColumn(array('name' => 'disc', 'type' => 'integer'));
        $cm->mapOneToOne(array('fieldName' => 'foo', 'targetEntity' => 'Bar', 'mappedBy' => 'foo'));
        $this->assertTrue($cm->getAssociationMapping('foo') instanceof Doctrine_ORM_Mapping_OneToOneMapping);
        $this->assertEquals(1, count($cm->getAssociationMappings()));

        $serialized = serialize($cm);
        $cm = unserialize($serialized);

        // Check state
        $this->assertTrue(count($cm->getReflectionProperties()) > 0);
        $this->assertTrue($cm->getReflectionClass() instanceof ReflectionClass);
        $this->assertEquals('CmsUser', $cm->getClassName());
        $this->assertEquals('UserParent', $cm->getRootClassName());
        $this->assertEquals(array('One', 'Two', 'Three'), $cm->getSubclasses());
        $this->assertEquals(array('UserParent'), $cm->getParentClasses());
        $this->assertEquals('UserRepository', $cm->getCustomRepositoryClass());
        $this->assertEquals(array('name' => 'disc', 'type' => 'integer'), $cm->getDiscriminatorColumn());
        $this->assertTrue($cm->getAssociationMapping('foo') instanceof Doctrine_ORM_Mapping_OneToOneMapping);
        $this->assertEquals(1, count($cm->getAssociationMappings()));
        $oneOneMapping = $cm->getAssociationMapping('foo');
        $this->assertEquals('foo', $oneOneMapping->getSourceFieldName());
        $this->assertEquals('Bar', $oneOneMapping->getTargetEntityName());
    }
    
}
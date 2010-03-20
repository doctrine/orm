<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Events;

require_once __DIR__ . '/../../TestInit.php';
 
class ClassMetadataTest extends \Doctrine\Tests\OrmTestCase
{
    public function testClassMetadataInstanceSerialization()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');

        // Test initial state
        $this->assertTrue(count($cm->getReflectionProperties()) == 0);
        $this->assertTrue($cm->reflClass instanceof \ReflectionClass);
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $cm->name);
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $cm->rootEntityName);
        $this->assertEquals(array(), $cm->subClasses);
        $this->assertEquals(array(), $cm->parentClasses);

        // Customize state
        $cm->setSubclasses(array("One", "Two", "Three"));
        $cm->setParentClasses(array("UserParent"));
        $cm->setCustomRepositoryClass("UserRepository");
        $cm->setDiscriminatorColumn(array('name' => 'disc', 'type' => 'integer'));
        $cm->mapOneToOne(array('fieldName' => 'phonenumbers', 'targetEntity' => 'Bar', 'mappedBy' => 'foo'));
        $this->assertTrue($cm->getAssociationMapping('phonenumbers') instanceof \Doctrine\ORM\Mapping\OneToOneMapping);
        $this->assertEquals(1, count($cm->associationMappings));

        $serialized = serialize($cm);
        $cm = unserialize($serialized);

        // Check state
        $this->assertTrue(count($cm->getReflectionProperties()) > 0);
        $this->assertTrue($cm->reflClass instanceof \ReflectionClass);
        $this->assertEquals('Doctrine\Tests\Models\CMS\CmsUser', $cm->name);
        $this->assertEquals('UserParent', $cm->rootEntityName);
        $this->assertEquals(array('Doctrine\Tests\Models\CMS\One', 'Doctrine\Tests\Models\CMS\Two', 'Doctrine\Tests\Models\CMS\Three'), $cm->subClasses);
        $this->assertEquals(array('UserParent'), $cm->parentClasses);
        $this->assertEquals('UserRepository', $cm->customRepositoryClassName);
        $this->assertEquals(array('name' => 'disc', 'type' => 'integer', 'fieldName' => 'disc'), $cm->discriminatorColumn);
        $this->assertTrue($cm->getAssociationMapping('phonenumbers') instanceof \Doctrine\ORM\Mapping\OneToOneMapping);
        $this->assertEquals(1, count($cm->associationMappings));
        $oneOneMapping = $cm->getAssociationMapping('phonenumbers');
        $this->assertEquals('phonenumbers', $oneOneMapping->sourceFieldName);
        $this->assertEquals('Doctrine\Tests\Models\CMS\Bar', $oneOneMapping->targetEntityName);
    }

    public function testFieldIsNullable()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');

        // Explicit Nullable
        $cm->mapField(array('fieldName' => 'status', 'nullable' => true, 'type' => 'string', 'length' => 50));
        $this->assertTrue($cm->isNullable('status'));

        // Explicit Not Nullable
        $cm->mapField(array('fieldName' => 'username', 'nullable' => false, 'type' => 'string', 'length' => 50));
        $this->assertFalse($cm->isNullable('username'));

        // Implicit Not Nullable
        $cm->mapField(array('fieldName' => 'name', 'type' => 'string', 'length' => 50));
        $this->assertFalse($cm->isNullable('name'), "By default a field should not be nullable.");
    }

    /**
     * @group DDC-115
     */
    public function testMapAssocationInGlobalNamespace()
    {
        require_once __DIR__."/../../Models/Global/GlobalNamespaceModel.php";

        $cm = new ClassMetadata('DoctrineGlobal_Article');
        $cm->mapManyToMany(array(
            'fieldName' => 'author',
            'targetEntity' => 'DoctrineGlobal_User',
            'joinTable' => array(
                'name' => 'bar',
                'joinColumns' => array(array('name' => 'bar_id', 'referencedColumnName' => 'id')),
                'inverseJoinColumns' => array(array('name' => 'baz_id', 'referencedColumnName' => 'id')),
            ),
        ));

        $this->assertEquals("DoctrineGlobal_User", $cm->associationMappings['author']->targetEntityName);
    }
    
    public function testMapManyToManyJoinTableDefaults()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->mapManyToMany(
            array(
            'fieldName' => 'groups',
            'targetEntity' => 'CmsGroup'
        ));
        
        $assoc = $cm->associationMappings['groups'];
        $this->assertTrue($assoc instanceof \Doctrine\ORM\Mapping\ManyToManyMapping);
        $this->assertEquals(array(
            'name' => 'CmsUser_CmsGroup',
            'joinColumns' => array(array('name' => 'CmsUser_id', 'referencedColumnName' => 'id')),
            'inverseJoinColumns' => array(array('name' => 'CmsGroup_id', 'referencedColumnName' => 'id'))
        ), $assoc->joinTable);
    }

    /**
     * @group DDC-115
     */
    public function testSetDiscriminatorMapInGlobalNamespace()
    {
        require_once __DIR__."/../../Models/Global/GlobalNamespaceModel.php";

        $cm = new ClassMetadata('DoctrineGlobal_User');
        $cm->setDiscriminatorMap(array('descr' => 'DoctrineGlobal_Article', 'foo' => 'DoctrineGlobal_User'));

        $this->assertEquals("DoctrineGlobal_Article", $cm->discriminatorMap['descr']);
        $this->assertEquals("DoctrineGlobal_User", $cm->discriminatorMap['foo']);
    }

    /**
     * @group DDC-115
     */
    public function testSetSubClassesInGlobalNamespace()
    {
        require_once __DIR__."/../../Models/Global/GlobalNamespaceModel.php";

        $cm = new ClassMetadata('DoctrineGlobal_User');
        $cm->setSubclasses(array('DoctrineGlobal_Article'));

        $this->assertEquals("DoctrineGlobal_Article", $cm->subClasses[0]);
    }

    /**
     * @group DDC-268
     */
    public function testSetInvalidVersionMapping_ThrowsException()
    {
        $field = array();
        $field['fieldName'] = 'foo';
        $field['type'] = 'string';

        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');

        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException');
        $cm->setVersionMapping($field);
    }

    public function testGetSingleIdentifierFieldName_MultipleIdentifierEntity_ThrowsException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->isIdentifierComposite  = true;

        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException');
        $cm->getSingleIdentifierFieldName();
    }

    public function testDuplicateAssociationMappingException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $a1 = new \Doctrine\ORM\Mapping\OneToOneMapping(array('fieldName' => 'foo', 'sourceEntity' => 'stdClass', 'targetEntity' => 'stdClass', 'mappedBy' => 'foo'));
        $a2 = new \Doctrine\ORM\Mapping\OneToOneMapping(array('fieldName' => 'foo', 'sourceEntity' => 'stdClass', 'targetEntity' => 'stdClass', 'mappedBy' => 'foo'));

        $cm->addAssociationMapping($a1);
        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException');
        $cm->addAssociationMapping($a2);
    }

    public function testDuplicateColumnName_ThrowsMappingException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->mapField(array('fieldName' => 'name', 'columnName' => 'name'));

        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException');
        $cm->mapField(array('fieldName' => 'username', 'columnName' => 'name'));
    }

    public function testDuplicateColumnName_DiscriminatorColumn_ThrowsMappingException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->mapField(array('fieldName' => 'name', 'columnName' => 'name'));

        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException');
        $cm->setDiscriminatorColumn(array('name' => 'name'));
    }

    public function testDuplicateColumnName_DiscriminatorColumn2_ThrowsMappingException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->setDiscriminatorColumn(array('name' => 'name'));

        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException');
        $cm->mapField(array('fieldName' => 'name', 'columnName' => 'name'));
    }

    public function testDuplicateFieldAndAssocationMapping1_ThrowsException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->mapField(array('fieldName' => 'name', 'columnName' => 'name'));

        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException');
        $cm->mapOneToOne(array('fieldName' => 'name', 'targetEntity' => 'CmsUser'));
    }

    public function testDuplicateFieldAndAssocationMapping2_ThrowsException()
    {
        $cm = new ClassMetadata('Doctrine\Tests\Models\CMS\CmsUser');
        $cm->mapOneToOne(array('fieldName' => 'name', 'targetEntity' => 'CmsUser'));

        $this->setExpectedException('Doctrine\ORM\Mapping\MappingException');
        $cm->mapField(array('fieldName' => 'name', 'columnName' => 'name'));
    }
}
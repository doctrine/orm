<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Driver\XmlDriver;

class XmlMappingDriverTest extends AbstractMappingDriverTest
{
    protected function _loadDriver()
    {
        return new XmlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'xml');
    }

    public function testClassTableInheritanceDiscriminatorMap()
    {
        $className = 'Doctrine\Tests\ORM\Mapping\CTI';
        $mappingDriver = $this->_loadDriver();

        $class = new ClassMetadata($className);
        $class->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);
        $mappingDriver->loadMetadataForClass($className, $class);

        $expectedMap = array(
            'foo' => 'Doctrine\Tests\ORM\Mapping\CTIFoo',
            'bar' => 'Doctrine\Tests\ORM\Mapping\CTIBar',
            'baz' => 'Doctrine\Tests\ORM\Mapping\CTIBaz',
        );

        $this->assertEquals(3, count($class->discriminatorMap));
        $this->assertEquals($expectedMap, $class->discriminatorMap);
    }

    /**
     * @expectedException Doctrine\ORM\Cache\CacheException
     * @expectedExceptionMessage Entity association field "Doctrine\Tests\ORM\Mapping\XMLSLC#foo" not configured as part of the second-level cache.
     */
    public function testFailingSecondLevelCacheAssociation()
    {
        $className = 'Doctrine\Tests\ORM\Mapping\XMLSLC';
        $mappingDriver = $this->_loadDriver();

        $class = new ClassMetadata($className);
        $mappingDriver->loadMetadataForClass($className, $class);
    }

    public function testIdentifierWithAssociationKey()
    {
        $driver  = $this->_loadDriver();
        $em      = $this->_getTestEntityManager();
        $factory = new ClassMetadataFactory();

        $em->getConfiguration()->setMetadataDriverImpl($driver);
        $factory->setEntityManager($em);

        $class = $factory->getMetadataFor('Doctrine\Tests\Models\DDC117\DDC117Translation');

        $this->assertEquals(array('language', 'article'), $class->identifier);
        $this->assertArrayHasKey('article', $class->associationMappings);

        $this->assertArrayHasKey('id', $class->associationMappings['article']);
        $this->assertTrue($class->associationMappings['article']['id']);
    }

    public function testEmbeddableMapping()
    {
        $class = $this->createClassMetadata('Doctrine\Tests\Models\ValueObjects\Name');

        $this->assertEquals(true, $class->isEmbeddedClass);
    }

    /**
     * @group DDC-3293
     * @group DDC-3477
     * @group 1238
     */
    public function testEmbeddedMappingsWithUseColumnPrefix()
    {
        $factory = new ClassMetadataFactory();
        $em      = $this->_getTestEntityManager();

        $em->getConfiguration()->setMetadataDriverImpl($this->_loadDriver());
        $factory->setEntityManager($em);

        $this->assertEquals(
            '__prefix__',
            $factory
                ->getMetadataFor('Doctrine\Tests\Models\DDC3293\DDC3293UserPrefixed')
                ->embeddedClasses['address']['columnPrefix']
        );
    }

    /**
     * @group DDC-3293
     * @group DDC-3477
     * @group 1238
     */
    public function testEmbeddedMappingsWithFalseUseColumnPrefix()
    {
        $factory = new ClassMetadataFactory();
        $em      = $this->_getTestEntityManager();

        $em->getConfiguration()->setMetadataDriverImpl($this->_loadDriver());
        $factory->setEntityManager($em);

        $this->assertFalse(
            $factory
                ->getMetadataFor('Doctrine\Tests\Models\DDC3293\DDC3293User')
                ->embeddedClasses['address']['columnPrefix']
        );
    }

    public function testEmbeddedMapping()
    {
        $class = $this->createClassMetadata('Doctrine\Tests\Models\ValueObjects\Person');

        $this->assertEquals(
            array(
                'name' => array(
                    'class' => 'Doctrine\Tests\Models\ValueObjects\Name',
                    'columnPrefix' => 'nm_',
                    'declaredField' => null,
                    'originalField' => null,
                )
            ),
            $class->embeddedClasses
        );
    }

    /**
     * @group DDC-1468
     *
     * @expectedException Doctrine\Common\Persistence\Mapping\MappingException
     * @expectedExceptionMessage Invalid mapping file 'Doctrine.Tests.Models.Generic.SerializationModel.dcm.xml' for class 'Doctrine\Tests\Models\Generic\SerializationModel'.
     */
    public function testInvalidMappingFileException()
    {
        $this->createClassMetadata('Doctrine\Tests\Models\Generic\SerializationModel');
    }

    /**
     * @param string $xmlMappingFile
     * @dataProvider dataValidSchema
     * @group DDC-2429
     */
    public function testValidateXmlSchema($xmlMappingFile)
    {
        $xsdSchemaFile  = __DIR__ . '/../../../../../doctrine-mapping.xsd';
        $dom            = new \DOMDocument('UTF-8');

        $dom->load($xmlMappingFile);

        $this->assertTrue($dom->schemaValidate($xsdSchemaFile));
    }

    static public function dataValidSchema()
    {
        $list    = glob(__DIR__ . '/xml/*.xml');
        $invalid = array(
            'Doctrine.Tests.Models.DDC889.DDC889Class.dcm'
        );

        $list = array_filter($list, function($item) use ($invalid){
            return ! in_array(pathinfo($item, PATHINFO_FILENAME), $invalid);
        });

        return array_map(function($item){
            return array($item);
        }, $list);
    }

    /**
     * @group DDC-889
     * @expectedException \Doctrine\Common\Persistence\Mapping\MappingException
     * @expectedExceptionMessage Invalid mapping file 'Doctrine.Tests.Models.DDC889.DDC889Class.dcm.xml' for class 'Doctrine\Tests\Models\DDC889\DDC889Class'.
     */
    public function testinvalidEntityOrMappedSuperClassShouldMentionParentClasses()
    {
        $this->createClassMetadata('Doctrine\Tests\Models\DDC889\DDC889Class');
    }
}

class CTI
{
    public $id;
}

class CTIFoo extends CTI {}
class CTIBar extends CTI {}
class CTIBaz extends CTI {}

class XMLSLC
{
    public $foo;
}
class XMLSLCFoo
{
    public $id;
}

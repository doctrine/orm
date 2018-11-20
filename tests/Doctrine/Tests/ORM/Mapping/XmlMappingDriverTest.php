<?php

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Tests\Models\DDC117\DDC117Translation;
use Doctrine\Tests\Models\DDC3293\DDC3293User;
use Doctrine\Tests\Models\DDC3293\DDC3293UserPrefixed;
use Doctrine\Tests\Models\DDC889\DDC889Class;
use Doctrine\Tests\Models\Generic\SerializationModel;
use Doctrine\Tests\Models\GH7141\GH7141Article;
use Doctrine\Tests\Models\GH7316\GH7316Article;
use Doctrine\Tests\Models\ValueObjects\Name;
use Doctrine\Tests\Models\ValueObjects\Person;

class XmlMappingDriverTest extends AbstractMappingDriverTest
{
    protected function _loadDriver()
    {
        return new XmlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'xml');
    }

    public function testClassTableInheritanceDiscriminatorMap()
    {
        $mappingDriver = $this->_loadDriver();

        $class = new ClassMetadata(CTI::class);
        $class->initializeReflection(new RuntimeReflectionService());
        $mappingDriver->loadMetadataForClass(CTI::class, $class);

        $expectedMap = [
            'foo' => CTIFoo::class,
            'bar' => CTIBar::class,
            'baz' => CTIBaz::class,
        ];

        $this->assertEquals(3, count($class->discriminatorMap));
        $this->assertEquals($expectedMap, $class->discriminatorMap);
    }

    /**
     * @expectedException \Doctrine\ORM\Cache\CacheException
     * @expectedExceptionMessage Entity association field "Doctrine\Tests\ORM\Mapping\XMLSLC#foo" not configured as part of the second-level cache.
     */
    public function testFailingSecondLevelCacheAssociation()
    {
        $mappingDriver = $this->_loadDriver();

        $class = new ClassMetadata(XMLSLC::class);
        $mappingDriver->loadMetadataForClass(XMLSLC::class, $class);
    }

    public function testIdentifierWithAssociationKey()
    {
        $driver  = $this->_loadDriver();
        $em      = $this->_getTestEntityManager();
        $factory = new ClassMetadataFactory();

        $em->getConfiguration()->setMetadataDriverImpl($driver);
        $factory->setEntityManager($em);

        $class = $factory->getMetadataFor(DDC117Translation::class);

        $this->assertEquals(['language', 'article'], $class->identifier);
        $this->assertArrayHasKey('article', $class->associationMappings);

        $this->assertArrayHasKey('id', $class->associationMappings['article']);
        $this->assertTrue($class->associationMappings['article']['id']);
    }

    public function testEmbeddableMapping()
    {
        $class = $this->createClassMetadata(Name::class);

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
            $factory->getMetadataFor(DDC3293UserPrefixed::class)
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
            $factory->getMetadataFor(DDC3293User::class)
                ->embeddedClasses['address']['columnPrefix']
        );
    }

    public function testEmbeddedMapping()
    {
        $class = $this->createClassMetadata(Person::class);

        $this->assertEquals(
            [
                'name' => [
                    'class' => Name::class,
                    'columnPrefix' => 'nm_',
                    'declaredField' => null,
                    'originalField' => null,
                ]
            ],
            $class->embeddedClasses
        );
    }

    /**
     * @group DDC-1468
     *
     * @expectedException \Doctrine\Common\Persistence\Mapping\MappingException
     * @expectedExceptionMessage Invalid mapping file 'Doctrine.Tests.Models.Generic.SerializationModel.dcm.xml' for class 'Doctrine\Tests\Models\Generic\SerializationModel'.
     */
    public function testInvalidMappingFileException()
    {
        $this->createClassMetadata(SerializationModel::class);
    }

    /**
     * @param string $xmlMappingFile
     * @dataProvider dataValidSchema
     * @group DDC-2429
     */
    public function testValidateXmlSchema($xmlMappingFile)
    {
        $xsdSchemaFile = __DIR__ . '/../../../../../doctrine-mapping.xsd';
        $dom           = new \DOMDocument();

        $dom->load($xmlMappingFile);

        $this->assertTrue($dom->schemaValidate($xsdSchemaFile));
    }

    static public function dataValidSchema()
    {
        $list    = glob(__DIR__ . '/xml/*.xml');
        $invalid = [
            'Doctrine.Tests.Models.DDC889.DDC889Class.dcm'
        ];

        $list = array_filter($list, function($item) use ($invalid){
            return ! in_array(pathinfo($item, PATHINFO_FILENAME), $invalid);
        });

        return array_map(function($item){
            return [$item];
        }, $list);
    }

    /**
     * @group GH-7141
     */
    public function testOneToManyDefaultOrderByAsc()
    {
        $driver = $this->_loadDriver();
        $class  = new ClassMetadata(GH7141Article::class);

        $class->initializeReflection(new RuntimeReflectionService());
        $driver->loadMetadataForClass(GH7141Article::class, $class);


        $this->assertEquals(
            Criteria::ASC,
            $class->getMetadataValue('associationMappings')['tags']['orderBy']['position']
        );
    }

    public function testManyToManyDefaultOrderByAsc() : void
    {
        $class  = new ClassMetadata(GH7316Article::class);
        $class->initializeReflection(new RuntimeReflectionService());

        $driver = $this->_loadDriver();
        $driver->loadMetadataForClass(GH7316Article::class, $class);

        self::assertEquals(
            Criteria::ASC,
            $class->getMetadataValue('associationMappings')['tags']['orderBy']['position']
        );
    }

    /**
     * @group DDC-889
     * @expectedException \Doctrine\Common\Persistence\Mapping\MappingException
     * @expectedExceptionMessage Invalid mapping file 'Doctrine.Tests.Models.DDC889.DDC889Class.dcm.xml' for class 'Doctrine\Tests\Models\DDC889\DDC889Class'.
     */
    public function testinvalidEntityOrMappedSuperClassShouldMentionParentClasses()
    {
        $this->createClassMetadata(DDC889Class::class);
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

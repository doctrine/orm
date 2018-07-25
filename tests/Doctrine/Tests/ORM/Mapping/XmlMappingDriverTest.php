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
     * @group 6389
     */
    public function testValidateXmlSchema($xmlMappingFile)
    {
        $this->assertTrue($this->doValidateXmlSchema($xmlMappingFile));
    }

    /**
     * @param string   $xmlMappingFile
     * @param string[] $errorMessageRegexes
     * @dataProvider dataValidSchemaInvalidMappings
     * @group 6389
     */
    public function testValidateXmlSchemaWithInvalidMapping($xmlMappingFile, $errorMessageRegexes)
    {
        $savedUseErrors = libxml_use_internal_errors(true);
        libxml_clear_errors();

        try {
            $validationResult = $this->doValidateXmlSchema($xmlMappingFile);

            $this->assertFalse($validationResult, 'Invalid XML mapping should not pass XSD validation.');

            /** @var \LibXMLError[] $errors */
            $errors = libxml_get_errors();

            $this->assertCount(count($errorMessageRegexes), $errors);
            foreach ($errorMessageRegexes as $i => $errorMessageRegex) {
                $this->assertRegExp($errorMessageRegex, trim($errors[$i]->message));
            }
        } finally {
            // Restore previous setting
            libxml_clear_errors();
            libxml_use_internal_errors($savedUseErrors);
        }
    }

    /**
     * @param string $xmlMappingFile
     * @return bool
     */
    private function doValidateXmlSchema($xmlMappingFile)
    {
        $xsdSchemaFile  = __DIR__ . '/../../../../../doctrine-mapping.xsd';
        $dom            = new \DOMDocument('1.0', 'UTF-8');

        $dom->load($xmlMappingFile);

        return $dom->schemaValidate($xsdSchemaFile);
    }

    static public function dataValidSchema()
    {
        $list    = self::getAllXmlMappingPaths();
        $invalid = self::getInvalidXmlMappingMap();

        $list = array_filter($list, function($item) use ($invalid){
            return ! array_key_exists(pathinfo($item, PATHINFO_FILENAME), $invalid);
        });

        return array_map(function($item){
            return [$item];
        }, $list);
    }

    static public function dataValidSchemaInvalidMappings()
    {
        $list    = self::getAllXmlMappingPaths();
        $invalid = self::getInvalidXmlMappingMap();

        $map = [];
        foreach ($invalid as $filename => $errorMessageRegexes) {
            $foundItem = null;
            foreach ($list as $item) {
                if (pathinfo($item, PATHINFO_FILENAME) === $filename) {
                    $foundItem = $item;
                    break;
                }
            }

            if ($foundItem !== null) {
                $map[$foundItem] = $errorMessageRegexes;
            } else {
                throw new \RuntimeException(sprintf('Found no XML mapping with filename "%s".', $filename));
            }
        }

        return array_map(function($item, $errorMessageRegexes){
            return [$item, $errorMessageRegexes];
        }, array_keys($map), $map);
    }

    /**
     * @return string[]
     */
    static private function getAllXmlMappingPaths()
    {
        return glob(__DIR__ . '/xml/*.xml');
    }

    /**
     * @return array<string, string[]> ($filename => $errorMessageRegexes)
     */
    static private function getInvalidXmlMappingMap()
    {
        $invalid = [
            'Doctrine.Tests.Models.DDC889.DDC889Class.dcm' => [
                "Element '{http://doctrine-project.org/schemas/orm/doctrine-mapping}class': This element is not expected. Expected is %s.",
            ],
        ];

        // Convert basic sprintf-style formats to PCRE patterns
        return array_map(function ($errorMessageFormats) {
            return array_map(function ($errorMessageFormat) {
                return '/^' . strtr(preg_quote($errorMessageFormat, '/'), [
                        '%%' => '%',
                        '%s' => '.*',
                    ]) . '$/s';
            }, $errorMessageFormats);
        }, $invalid);
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

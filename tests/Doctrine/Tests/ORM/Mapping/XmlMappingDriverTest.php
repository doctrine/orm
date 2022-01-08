<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Cache\Exception\CacheException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Tests\Models\DDC117\DDC117Translation;
use Doctrine\Tests\Models\DDC3293\DDC3293User;
use Doctrine\Tests\Models\DDC3293\DDC3293UserPrefixed;
use Doctrine\Tests\Models\DDC889\DDC889Class;
use Doctrine\Tests\Models\Generic\SerializationModel;
use Doctrine\Tests\Models\GH7141\GH7141Article;
use Doctrine\Tests\Models\GH7316\GH7316Article;
use Doctrine\Tests\Models\ValueObjects\Name;
use Doctrine\Tests\Models\ValueObjects\Person;
use DOMDocument;

use function array_filter;
use function array_map;
use function assert;
use function glob;
use function in_array;
use function is_array;
use function pathinfo;

use const DIRECTORY_SEPARATOR;
use const PATHINFO_FILENAME;

class XmlMappingDriverTest extends AbstractMappingDriverTest
{
    protected function loadDriver(): MappingDriver
    {
        return new XmlDriver(__DIR__ . DIRECTORY_SEPARATOR . 'xml');
    }

    public function testClassTableInheritanceDiscriminatorMap(): void
    {
        $mappingDriver = $this->loadDriver();

        $class = new ClassMetadata(CTI::class);
        $class->initializeReflection(new RuntimeReflectionService());
        $mappingDriver->loadMetadataForClass(CTI::class, $class);

        $expectedMap = [
            'foo' => CTIFoo::class,
            'bar' => CTIBar::class,
            'baz' => CTIBaz::class,
        ];

        self::assertCount(3, $class->discriminatorMap);
        self::assertEquals($expectedMap, $class->discriminatorMap);
    }

    public function testFailingSecondLevelCacheAssociation(): void
    {
        $this->expectException(CacheException::class);
        $this->expectExceptionMessage('Entity association field "Doctrine\Tests\ORM\Mapping\XMLSLC#foo" not configured as part of the second-level cache.');
        $mappingDriver = $this->loadDriver();

        $class = new ClassMetadata(XMLSLC::class);
        $mappingDriver->loadMetadataForClass(XMLSLC::class, $class);
    }

    public function testIdentifierWithAssociationKey(): void
    {
        $driver  = $this->loadDriver();
        $em      = $this->getTestEntityManager();
        $factory = new ClassMetadataFactory();

        $em->getConfiguration()->setMetadataDriverImpl($driver);
        $factory->setEntityManager($em);

        $class = $factory->getMetadataFor(DDC117Translation::class);

        self::assertEquals(['language', 'article'], $class->identifier);
        self::assertArrayHasKey('article', $class->associationMappings);

        self::assertArrayHasKey('id', $class->associationMappings['article']);
        self::assertTrue($class->associationMappings['article']['id']);
    }

    public function testEmbeddableMapping(): void
    {
        $class = $this->createClassMetadata(Name::class);

        self::assertTrue($class->isEmbeddedClass);
    }

    /**
     * @group DDC-3293
     * @group DDC-3477
     * @group 1238
     */
    public function testEmbeddedMappingsWithUseColumnPrefix(): void
    {
        $factory = new ClassMetadataFactory();
        $em      = $this->getTestEntityManager();

        $em->getConfiguration()->setMetadataDriverImpl($this->loadDriver());
        $factory->setEntityManager($em);

        self::assertEquals(
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
    public function testEmbeddedMappingsWithFalseUseColumnPrefix(): void
    {
        $factory = new ClassMetadataFactory();
        $em      = $this->getTestEntityManager();

        $em->getConfiguration()->setMetadataDriverImpl($this->loadDriver());
        $factory->setEntityManager($em);

        self::assertFalse(
            $factory->getMetadataFor(DDC3293User::class)
                ->embeddedClasses['address']['columnPrefix']
        );
    }

    public function testEmbeddedMapping(): void
    {
        $class = $this->createClassMetadata(Person::class);

        self::assertEquals(
            [
                'name' => [
                    'class' => Name::class,
                    'columnPrefix' => 'nm_',
                    'declaredField' => null,
                    'originalField' => null,
                ],
            ],
            $class->embeddedClasses
        );
    }

    /**
     * @group DDC-1468
     */
    public function testInvalidMappingFileException(): void
    {
        $this->expectException('Doctrine\Persistence\Mapping\MappingException');
        $this->expectExceptionMessage('Invalid mapping file \'Doctrine.Tests.Models.Generic.SerializationModel.dcm.xml\' for class \'Doctrine\Tests\Models\Generic\SerializationModel\'.');
        $this->createClassMetadata(SerializationModel::class);
    }

    /**
     * @dataProvider dataValidSchema
     * @group DDC-2429
     */
    public function testValidateXmlSchema(string $xmlMappingFile): void
    {
        $xsdSchemaFile = __DIR__ . '/../../../../../doctrine-mapping.xsd';
        $dom           = new DOMDocument();

        $dom->load($xmlMappingFile);

        self::assertTrue($dom->schemaValidate($xsdSchemaFile));
    }

    /**
     * @psalm-return list<array{string}>
     */
    public static function dataValidSchema(): array
    {
        $list    = glob(__DIR__ . '/xml/*.xml');
        $invalid = ['Doctrine.Tests.Models.DDC889.DDC889Class.dcm'];
        assert(is_array($list));

        $list = array_filter($list, static function (string $item) use ($invalid): bool {
            return ! in_array(pathinfo($item, PATHINFO_FILENAME), $invalid, true);
        });

        return array_map(static function (string $item) {
            return [$item];
        }, $list);
    }

    /**
     * @group GH-7141
     */
    public function testOneToManyDefaultOrderByAsc(): void
    {
        $driver = $this->loadDriver();
        $class  = new ClassMetadata(GH7141Article::class);

        $class->initializeReflection(new RuntimeReflectionService());
        $driver->loadMetadataForClass(GH7141Article::class, $class);

        self::assertEquals(
            Criteria::ASC,
            $class->getMetadataValue('associationMappings')['tags']['orderBy']['position']
        );
    }

    public function testManyToManyDefaultOrderByAsc(): void
    {
        $class = new ClassMetadata(GH7316Article::class);
        $class->initializeReflection(new RuntimeReflectionService());

        $driver = $this->loadDriver();
        $driver->loadMetadataForClass(GH7316Article::class, $class);

        self::assertEquals(
            Criteria::ASC,
            $class->getMetadataValue('associationMappings')['tags']['orderBy']['position']
        );
    }

    /**
     * @group DDC-889
     */
    public function testinvalidEntityOrMappedSuperClassShouldMentionParentClasses(): void
    {
        $this->expectException('Doctrine\Persistence\Mapping\MappingException');
        $this->expectExceptionMessage('Invalid mapping file \'Doctrine.Tests.Models.DDC889.DDC889Class.dcm.xml\' for class \'Doctrine\Tests\Models\DDC889\DDC889Class\'.');
        $this->createClassMetadata(DDC889Class::class);
    }
}

class CTI
{
    /** @var int */
    public $id;
}

class CTIFoo extends CTI
{
}
class CTIBar extends CTI
{
}
class CTIBaz extends CTI
{
}

class XMLSLC
{
    /** @var mixed */
    public $foo;
}

class XMLSLCFoo
{
    /** @var int */
    public $id;
}

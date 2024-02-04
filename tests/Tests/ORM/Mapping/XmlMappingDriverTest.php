<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Mapping;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Cache\Exception\CacheException;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Persistence\Mapping\MappingException as PersistenceMappingException;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Tests\Models\DDC117\DDC117Translation;
use Doctrine\Tests\Models\DDC3293\DDC3293User;
use Doctrine\Tests\Models\DDC3293\DDC3293UserPrefixed;
use Doctrine\Tests\Models\DDC889\DDC889Class;
use Doctrine\Tests\Models\DDC889\DDC889Entity;
use Doctrine\Tests\Models\DDC889\DDC889SuperClass;
use Doctrine\Tests\Models\Generic\BooleanModel;
use Doctrine\Tests\Models\GH7141\GH7141Article;
use Doctrine\Tests\Models\GH7316\GH7316Article;
use Doctrine\Tests\Models\Project\Project;
use Doctrine\Tests\Models\Project\ProjectId;
use Doctrine\Tests\Models\Project\ProjectInvalidMapping;
use Doctrine\Tests\Models\Project\ProjectName;
use Doctrine\Tests\Models\ValueObjects\Name;
use Doctrine\Tests\Models\ValueObjects\Person;

use function substr_count;

use const DIRECTORY_SEPARATOR;

class XmlMappingDriverTest extends MappingDriverTestCase
{
    protected function loadDriver(): MappingDriver
    {
        return new XmlDriver(
            __DIR__ . DIRECTORY_SEPARATOR . 'xml',
            XmlDriver::DEFAULT_FILE_EXTENSION,
            true
        );
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

    /** @group DDC-1468 */
    public function testItMentionsFilenameAndEntityNameOnInvalidMapping(): void
    {
        $this->expectException(PersistenceMappingException::class);
        $this->expectExceptionMessage('Invalid mapping file \'Doctrine.Tests.Models.Generic.BooleanModel.dcm.xml\' for class \'Doctrine\Tests\Models\Generic\BooleanModel\'.');
        $this->createClassMetadata(BooleanModel::class);
    }

    /**
     * @dataProvider dataValidSchema
     * @group DDC-2429
     */
    public function testValidateXmlSchema(
        string $class,
        string $tableName,
        array $fieldNames,
        array $associationNames
    ): void {
        $metadata = $this->createClassMetadata($class);

        $this->assertInstanceOf(ClassMetadata::class, $metadata);
        $this->assertEquals($metadata->getTableName(), $tableName);
        $this->assertEquals($metadata->getFieldNames(), $fieldNames);
        $this->assertEquals($metadata->getAssociationNames(), $associationNames);
    }

    /** @psalm-return []array{0: class-string, 1: string, 2: list<string>, 3: list<string>} */
    public static function dataValidSchema(): array
    {
        return [
            [
                User::class,
                'cms_users',
                ['name', 'email', 'version', 'id'],
                ['address', 'phonenumbers', 'groups'],
            ],
            [
                DDC889Entity::class,
                'DDC889Entity',
                [],
                [],
            ],
            [
                DDC889SuperClass::class,
                'DDC889SuperClass',
                ['name'],
                [],
            ],
        ];
    }

    /**
     * @param class-string                 $class
     * @param non-empty-array<string, int> $expectedExceptionOccurrences
     *
     * @dataProvider dataInvalidSchema
     */
    public function testValidateIncorrectXmlSchema(string $class, array $expectedExceptionOccurrences): void
    {
        try {
            $this->createClassMetadata($class);

            $this->fail('XML schema validation should throw a MappingException');
        } catch (MappingException $exception) {
            foreach ($expectedExceptionOccurrences as $exceptionContent => $occurrencesCount) {
                $this->assertEquals($occurrencesCount, substr_count($exception->getMessage(), $exceptionContent));
            }
        }
    }

    /** @return non-empty-list<array{0: class-string, 1: non-empty-array<string, int>}> */
    public static function dataInvalidSchema(): array
    {
        return [
            [
                DDC889Class::class,
                ['This element is not expected' => 1],
            ],
            [
                UserIncorrectAttributes::class,
                [
                    'attribute \'field\': The attribute \'field\' is not allowed' => 2,
                    'The attribute \'name\' is required but missing' => 2,
                    'attribute \'fieldName\': The attribute \'fieldName\' is not allowed' => 1,
                ],
            ],
            [
                UserMissingAttributes::class,
                ['The attribute \'name\' is required but missing' => 1],
            ],
            [
                ProjectInvalidMapping::class,
                ['attribute \'type\': [facet \'pattern\'] The value' => 2],
            ],
        ];
    }

    /** @group GH-7141 */
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

    /** @group DDC-889 */
    public function testInvalidEntityOrMappedSuperClassShouldMentionParentClasses(): void
    {
        $this->expectException(MappingException::class);
        $this->expectExceptionMessage('libxml error: Element \'{http://doctrine-project.org/schemas/orm/doctrine-mapping}class\': This element is not expected.');

        $this->createClassMetadata(DDC889Class::class);
    }

    public function testClassNameInFieldOrId(): void
    {
        $class = new ClassMetadata(Project::class);
        $class->initializeReflection(new RuntimeReflectionService());

        $driver = $this->loadDriver();
        $driver->loadMetadataForClass(Project::class, $class);

        /** @var array{type: string} $id */
        $id = $class->getFieldMapping('id');
        /** @var array{type: string} $name */
        $name = $class->getFieldMapping('name');

        self::assertEquals(ProjectId::class, $id['type']);
        self::assertEquals(ProjectName::class, $name['type']);
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

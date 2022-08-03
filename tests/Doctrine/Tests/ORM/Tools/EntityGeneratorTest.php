<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools;

use DateTime;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\Tests\Models\DDC2372\DDC2372Admin;
use Doctrine\Tests\Models\DDC2372\DDC2372User;
use Doctrine\Tests\OrmTestCase;
use Doctrine\Tests\VerifyDeprecations;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use ReflectionMethod;
use ReflectionObject;
use ReflectionProperty;
use SplFileInfo;
use stdClass;

use function array_keys;
use function assert;
use function basename;
use function class_exists;
use function file_get_contents;
use function file_put_contents;
use function get_class;
use function method_exists;
use function mkdir;
use function preg_match;
use function preg_replace;
use function rmdir;
use function sprintf;
use function str_replace;
use function sys_get_temp_dir;
use function ucfirst;
use function uniqid;
use function unlink;

use const DIRECTORY_SEPARATOR;

class EntityGeneratorTest extends OrmTestCase
{
    use VerifyDeprecations;

    /** @var EntityGenerator */
    private $generator;

    /** @var string */
    private $tmpDir;

    /** @var string */
    private $namespace;

    protected function setUp(): void
    {
        $this->namespace = uniqid('doctrine_');
        $this->tmpDir    = sys_get_temp_dir();
        mkdir($this->tmpDir . DIRECTORY_SEPARATOR . $this->namespace);
        $this->generator = new EntityGenerator();
        $this->generator->setAnnotationPrefix('');
        $this->generator->setGenerateAnnotations(true);
        $this->generator->setGenerateStubMethods(true);
        $this->generator->setRegenerateEntityIfExists(false);
        $this->generator->setUpdateEntityIfExists(true);
        $this->generator->setFieldVisibility(EntityGenerator::FIELD_VISIBLE_PROTECTED);
    }

    public function tearDown(): void
    {
        $ri = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->tmpDir . '/' . $this->namespace));
        foreach ($ri as $file) {
            assert($file instanceof SplFileInfo);
            if ($file->isFile()) {
                unlink($file->getPathname());
            }
        }

        rmdir($this->tmpDir . '/' . $this->namespace);
    }

    /** @after */
    public function ensureTestGeneratedDeprecationMessages(): void
    {
        $this->assertHasDeprecationMessages();
    }

    /**
     * @param ClassMetadataInfo[] $embeddedClasses
     */
    public function generateBookEntityFixture(array $embeddedClasses = []): ClassMetadataInfo
    {
        $metadata                            = new ClassMetadataInfo($this->namespace . '\EntityGeneratorBook');
        $metadata->namespace                 = $this->namespace;
        $metadata->customRepositoryClassName = $this->namespace . '\EntityGeneratorBookRepository';

        $metadata->table['name']                           = 'book';
        $metadata->table['uniqueConstraints']['name_uniq'] = ['columns' => ['name']];
        $metadata->table['indexes']['status_idx']          = ['columns' => ['status']];
        $metadata->mapField(['fieldName' => 'name', 'type' => 'string']);
        $metadata->mapField(['fieldName' => 'status', 'type' => 'string', 'options' => ['default' => 'published']]);
        $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
        $metadata->mapOneToOne(
            ['fieldName' => 'author', 'targetEntity' => EntityGeneratorAuthor::class, 'mappedBy' => 'book']
        );
        $joinColumns = [
            ['name' => 'author_id', 'referencedColumnName' => 'id'],
        ];
        $metadata->mapManyToMany(
            [
                'fieldName' => 'comments',
                'targetEntity' => EntityGeneratorComment::class,
                'fetch' => ClassMetadataInfo::FETCH_EXTRA_LAZY,
                'joinTable' => [
                    'name' => 'book_comment',
                    'joinColumns' => [['name' => 'book_id', 'referencedColumnName' => 'id']],
                    'inverseJoinColumns' => [['name' => 'comment_id', 'referencedColumnName' => 'id']],
                ],
            ]
        );
        $metadata->addLifecycleCallback('loading', 'postLoad');
        $metadata->addLifecycleCallback('willBeRemoved', 'preRemove');
        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);

        foreach ($embeddedClasses as $fieldName => $embeddedClass) {
            $this->mapNestedEmbedded($fieldName, $metadata, $embeddedClass);
            $this->mapEmbedded($fieldName, $metadata, $embeddedClass);
        }

        $this->generator->writeEntityClass($metadata, $this->tmpDir);

        return $metadata;
    }

    private function generateEntityTypeFixture(array $field): ClassMetadataInfo
    {
        $metadata            = new ClassMetadataInfo($this->namespace . '\EntityType');
        $metadata->namespace = $this->namespace;

        $metadata->table['name'] = 'entity_type';
        $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);

        $name = $field['fieldName'];
        $type = $field['dbType'];
        $metadata->mapField(['fieldName' => $name, 'type' => $type]);

        $this->generator->writeEntityClass($metadata, $this->tmpDir);

        return $metadata;
    }

    private function generateIsbnEmbeddableFixture(array $embeddedClasses = [], $columnPrefix = null): ClassMetadataInfo
    {
        $metadata                  = new ClassMetadataInfo($this->namespace . '\EntityGeneratorIsbn');
        $metadata->namespace       = $this->namespace;
        $metadata->isEmbeddedClass = true;
        $metadata->mapField(['fieldName' => 'prefix', 'type' => 'integer']);
        $metadata->mapField(['fieldName' => 'groupNumber', 'type' => 'integer']);
        $metadata->mapField(['fieldName' => 'publisherNumber', 'type' => 'integer']);
        $metadata->mapField(['fieldName' => 'titleNumber', 'type' => 'integer']);
        $metadata->mapField(['fieldName' => 'checkDigit', 'type' => 'integer']);

        foreach ($embeddedClasses as $fieldName => $embeddedClass) {
            $this->mapEmbedded($fieldName, $metadata, $embeddedClass, $columnPrefix);
        }

        $this->generator->writeEntityClass($metadata, $this->tmpDir);

        return $metadata;
    }

    private function generateTestEmbeddableFixture(): ClassMetadataInfo
    {
        $metadata                  = new ClassMetadataInfo($this->namespace . '\EntityGeneratorTestEmbeddable');
        $metadata->namespace       = $this->namespace;
        $metadata->isEmbeddedClass = true;
        $metadata->mapField(['fieldName' => 'field1', 'type' => 'integer']);
        $metadata->mapField(['fieldName' => 'field2', 'type' => 'integer', 'nullable' => true]);
        $metadata->mapField(['fieldName' => 'field3', 'type' => 'datetime']);
        $metadata->mapField(['fieldName' => 'field4', 'type' => 'datetime', 'nullable' => true]);

        $this->generator->writeEntityClass($metadata, $this->tmpDir);

        return $metadata;
    }

    /**
     * @param string|bool $columnPrefix
     */
    private function mapEmbedded(
        string $fieldName,
        ClassMetadataInfo $classMetadata,
        ClassMetadataInfo $embeddableMetadata,
        $columnPrefix = false
    ): void {
        $classMetadata->mapEmbedded(
            ['fieldName' => $fieldName, 'class' => $embeddableMetadata->name, 'columnPrefix' => $columnPrefix]
        );
    }

    private function mapNestedEmbedded(
        string $fieldName,
        ClassMetadataInfo $classMetadata,
        ClassMetadataInfo $embeddableMetadata
    ): void {
        foreach ($embeddableMetadata->embeddedClasses as $property => $embeddableClass) {
            $classMetadata->mapEmbedded(
                [
                    'fieldName' => $fieldName . '.' . $property,
                    'class' => $embeddableClass['class'],
                    'columnPrefix' => $embeddableClass['columnPrefix'],
                    'declaredField' => $embeddableClass['declaredField']
                        ? $fieldName . '.' . $embeddableClass['declaredField']
                        : $fieldName,
                    'originalField' => $embeddableClass['originalField'] ?: $property,
                ]
            );
        }
    }

    private function loadEntityClass(ClassMetadataInfo $metadata): void
    {
        $className = basename(str_replace('\\', '/', $metadata->name));
        $path      = $this->tmpDir . '/' . $this->namespace . '/' . $className . '.php';

        $this->assertFileExists($path);

        require_once $path;
    }

    /**
     * @return mixed An instance of the given metadata's class.
     */
    public function newInstance(ClassMetadataInfo $metadata)
    {
        $this->loadEntityClass($metadata);

        return new $metadata->name();
    }

    /**
     * @group GH-6314
     */
    public function testEmbeddedEntityWithNamedColumnPrefix(): void
    {
        $columnPrefix = 'GH6314Prefix_';
        $testMetadata = $this->generateTestEmbeddableFixture();
        $isbnMetadata = $this->generateIsbnEmbeddableFixture(['testEmbedded' => $testMetadata], $columnPrefix);
        $isbnEntity   = $this->newInstance($isbnMetadata);
        $refClass     = new ReflectionClass($isbnEntity);
        self::assertTrue($refClass->hasProperty('testEmbedded'));

        $docComment = $refClass->getProperty('testEmbedded')->getDocComment();
        $needle     = sprintf('@Embedded(class="%s", columnPrefix="%s")', $testMetadata->name, $columnPrefix);
        self::assertStringContainsString($needle, $docComment);
    }

    /**
     * @group GH-6314
     */
    public function testEmbeddedEntityWithoutColumnPrefix(): void
    {
        $testMetadata = $this->generateTestEmbeddableFixture();
        $isbnMetadata = $this->generateIsbnEmbeddableFixture(['testEmbedded' => $testMetadata], false);
        $isbnEntity   = $this->newInstance($isbnMetadata);
        $refClass     = new ReflectionClass($isbnEntity);
        self::assertTrue($refClass->hasProperty('testEmbedded'));

        $docComment = $refClass->getProperty('testEmbedded')->getDocComment();
        $needle     = sprintf('@Embedded(class="%s", columnPrefix=false)', $testMetadata->name);
        self::assertStringContainsString($needle, $docComment);
    }

    public function testGeneratedEntityClass(): void
    {
        $testMetadata = $this->generateTestEmbeddableFixture();
        $isbnMetadata = $this->generateIsbnEmbeddableFixture(['test' => $testMetadata]);
        $metadata     = $this->generateBookEntityFixture(['isbn' => $isbnMetadata]);

        $book = $this->newInstance($metadata);
        $this->assertTrue(class_exists($metadata->name), 'Class does not exist.');
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', '__construct'), 'EntityGeneratorBook::__construct() missing.');
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'getId'), 'EntityGeneratorBook::getId() missing.');
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'setName'), 'EntityGeneratorBook::setName() missing.');
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'getName'), 'EntityGeneratorBook::getName() missing.');
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'setStatus'), 'EntityGeneratorBook::setStatus() missing.');
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'getStatus'), 'EntityGeneratorBook::getStatus() missing.');
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'setAuthor'), 'EntityGeneratorBook::setAuthor() missing.');
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'getAuthor'), 'EntityGeneratorBook::getAuthor() missing.');
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'getComments'), 'EntityGeneratorBook::getComments() missing.');
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'addComment'), 'EntityGeneratorBook::addComment() missing.');
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'removeComment'), 'EntityGeneratorBook::removeComment() missing.');
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'setIsbn'), 'EntityGeneratorBook::setIsbn() missing.');
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'getIsbn'), 'EntityGeneratorBook::getIsbn() missing.');

        $reflClass = new ReflectionClass($metadata->name);

        $this->assertCount(6, $reflClass->getProperties());
        $this->assertCount(15, $reflClass->getMethods());

        $this->assertEquals('published', $book->getStatus());

        $book->setName('Jonathan H. Wage');
        $this->assertEquals('Jonathan H. Wage', $book->getName());

        $reflMethod           = new ReflectionMethod($metadata->name, 'addComment');
        $addCommentParameters = $reflMethod->getParameters();
        $this->assertEquals('comment', $addCommentParameters[0]->getName());

        $reflMethod              = new ReflectionMethod($metadata->name, 'removeComment');
        $removeCommentParameters = $reflMethod->getParameters();
        $this->assertEquals('comment', $removeCommentParameters[0]->getName());

        $author = new EntityGeneratorAuthor();
        $book->setAuthor($author);
        $this->assertEquals($author, $book->getAuthor());

        $comment = new EntityGeneratorComment();
        $this->assertInstanceOf($metadata->name, $book->addComment($comment));
        $this->assertInstanceOf(ArrayCollection::class, $book->getComments());
        $this->assertEquals(new ArrayCollection([$comment]), $book->getComments());
        $this->assertIsBool($book->removeComment($comment));
        $this->assertEquals(new ArrayCollection([]), $book->getComments());

        $this->newInstance($isbnMetadata);
        $isbn = new $isbnMetadata->name();

        $book->setIsbn($isbn);
        $this->assertSame($isbn, $book->getIsbn());

        $reflMethod     = new ReflectionMethod($metadata->name, 'setIsbn');
        $reflParameters = $reflMethod->getParameters();
        $this->assertEquals($isbnMetadata->name, $reflParameters[0]->getType()->getName());
    }

    public function testBooleanDefaultValue(): void
    {
        $metadata = $this->generateBookEntityFixture(['isbn' => $this->generateIsbnEmbeddableFixture()]);

        $metadata->mapField(['fieldName' => 'foo', 'type' => 'boolean', 'options' => ['default' => '1']]);

        $testEmbeddableMetadata = $this->generateTestEmbeddableFixture();
        $this->mapEmbedded('testEmbedded', $metadata, $testEmbeddableMetadata);

        $this->generator->writeEntityClass($metadata, $this->tmpDir);

        $this->assertFileExists($this->tmpDir . '/' . $this->namespace . '/EntityGeneratorBook.php~');

        $book      = $this->newInstance($metadata);
        $reflClass = new ReflectionClass($metadata->name);

        $this->assertTrue($book->getfoo());
    }

    public function testEntityUpdatingWorks(): void
    {
        $metadata = $this->generateBookEntityFixture(['isbn' => $this->generateIsbnEmbeddableFixture()]);

        $metadata->mapField(['fieldName' => 'test', 'type' => 'string']);

        $testEmbeddableMetadata = $this->generateTestEmbeddableFixture();
        $this->mapEmbedded('testEmbedded', $metadata, $testEmbeddableMetadata);

        $this->generator->writeEntityClass($metadata, $this->tmpDir);

        $this->assertFileExists($this->tmpDir . '/' . $this->namespace . '/EntityGeneratorBook.php~');

        $book      = $this->newInstance($metadata);
        $reflClass = new ReflectionClass($metadata->name);

        $this->assertTrue($reflClass->hasProperty('name'), "Regenerating keeps property 'name'.");
        $this->assertTrue($reflClass->hasProperty('status'), "Regenerating keeps property 'status'.");
        $this->assertTrue($reflClass->hasProperty('id'), "Regenerating keeps property 'id'.");
        $this->assertTrue($reflClass->hasProperty('isbn'), "Regenerating keeps property 'isbn'.");

        $this->assertTrue($reflClass->hasProperty('test'), 'Check for property test failed.');
        $this->assertTrue($reflClass->getProperty('test')->isProtected(), 'Check for protected property test failed.');
        $this->assertTrue($reflClass->hasProperty('testEmbedded'), 'Check for property testEmbedded failed.');
        $this->assertTrue($reflClass->getProperty('testEmbedded')->isProtected(), 'Check for protected property testEmbedded failed.');
        $this->assertTrue($reflClass->hasMethod('getTest'), "Check for method 'getTest' failed.");
        $this->assertTrue($reflClass->getMethod('getTest')->isPublic(), "Check for public visibility of method 'getTest' failed.");
        $this->assertTrue($reflClass->hasMethod('setTest'), "Check for method 'setTest' failed.");
        $this->assertTrue($reflClass->getMethod('setTest')->isPublic(), "Check for public visibility of method 'setTest' failed.");
        $this->assertTrue($reflClass->hasMethod('getTestEmbedded'), "Check for method 'getTestEmbedded' failed.");
        $this->assertTrue(
            $reflClass->getMethod('getTestEmbedded')->isPublic(),
            "Check for public visibility of method 'getTestEmbedded' failed."
        );
        $this->assertTrue($reflClass->hasMethod('setTestEmbedded'), "Check for method 'setTestEmbedded' failed.");
        $this->assertTrue(
            $reflClass->getMethod('setTestEmbedded')->isPublic(),
            "Check for public visibility of method 'setTestEmbedded' failed."
        );
    }

    /**
     * @group DDC-3152
     */
    public function testDoesNotRegenerateExistingMethodsWithDifferentCase(): void
    {
        $metadata = $this->generateBookEntityFixture(['isbn' => $this->generateIsbnEmbeddableFixture()]);

        // Workaround to change existing fields case (just to simulate the use case)
        $metadata->fieldMappings['status']['fieldName'] = 'STATUS';
        $metadata->embeddedClasses['ISBN']              = $metadata->embeddedClasses['isbn'];
        unset($metadata->embeddedClasses['isbn']);

        // Should not throw a PHP fatal error
        $this->generator->writeEntityClass($metadata, $this->tmpDir);

        $this->assertFileExists($this->tmpDir . '/' . $this->namespace . '/EntityGeneratorBook.php~');

        $this->newInstance($metadata);
        $reflClass = new ReflectionClass($metadata->name);

        $this->assertTrue($reflClass->hasProperty('status'));
        $this->assertTrue($reflClass->hasProperty('STATUS'));
        $this->assertTrue($reflClass->hasProperty('isbn'));
        $this->assertTrue($reflClass->hasProperty('ISBN'));
        $this->assertTrue($reflClass->hasMethod('getStatus'));
        $this->assertTrue($reflClass->hasMethod('setStatus'));
        $this->assertTrue($reflClass->hasMethod('getIsbn'));
        $this->assertTrue($reflClass->hasMethod('setIsbn'));
    }

    /**
     * @group DDC-2121
     */
    public function testMethodDocBlockShouldStartWithBackSlash(): void
    {
        $embeddedMetadata = $this->generateIsbnEmbeddableFixture();
        $metadata         = $this->generateBookEntityFixture(['isbn' => $embeddedMetadata]);
        $book             = $this->newInstance($metadata);

        $this->assertPhpDocVarType('\Doctrine\Common\Collections\Collection', new ReflectionProperty($book, 'comments'));
        $this->assertPhpDocReturnType('\Doctrine\Common\Collections\Collection', new ReflectionMethod($book, 'getComments'));
        $this->assertPhpDocParamType('\Doctrine\Tests\ORM\Tools\EntityGeneratorComment', new ReflectionMethod($book, 'addComment'));
        $this->assertPhpDocReturnType('EntityGeneratorBook', new ReflectionMethod($book, 'addComment'));
        $this->assertPhpDocParamType('\Doctrine\Tests\ORM\Tools\EntityGeneratorComment', new ReflectionMethod($book, 'removeComment'));
        $this->assertPhpDocReturnType('boolean', new ReflectionMethod($book, 'removeComment'));

        $this->assertPhpDocVarType('\Doctrine\Tests\ORM\Tools\EntityGeneratorAuthor', new ReflectionProperty($book, 'author'));
        $this->assertPhpDocReturnType('\Doctrine\Tests\ORM\Tools\EntityGeneratorAuthor|null', new ReflectionMethod($book, 'getAuthor'));
        $this->assertPhpDocParamType('\Doctrine\Tests\ORM\Tools\EntityGeneratorAuthor|null', new ReflectionMethod($book, 'setAuthor'));

        $expectedClassName = '\\' . $embeddedMetadata->name;
        $this->assertPhpDocVarType($expectedClassName, new ReflectionProperty($book, 'isbn'));
        $this->assertPhpDocReturnType($expectedClassName, new ReflectionMethod($book, 'getIsbn'));
        $this->assertPhpDocParamType($expectedClassName, new ReflectionMethod($book, 'setIsbn'));
    }

    public function testEntityExtendsStdClass(): void
    {
        $this->generator->setClassToExtend('stdClass');
        $metadata = $this->generateBookEntityFixture();

        $book = $this->newInstance($metadata);
        $this->assertInstanceOf('stdClass', $book);

        $metadata = $this->generateIsbnEmbeddableFixture();
        $isbn     = $this->newInstance($metadata);
        $this->assertInstanceOf('stdClass', $isbn);
    }

    public function testLifecycleCallbacks(): void
    {
        $metadata = $this->generateBookEntityFixture();

        $book      = $this->newInstance($metadata);
        $reflClass = new ReflectionClass($metadata->name);

        $this->assertTrue($reflClass->hasMethod('loading'), 'Check for postLoad lifecycle callback.');
        $this->assertTrue($reflClass->hasMethod('willBeRemoved'), 'Check for preRemove lifecycle callback.');
    }

    public function testLoadMetadata(): void
    {
        $embeddedMetadata = $this->generateIsbnEmbeddableFixture();
        $metadata         = $this->generateBookEntityFixture(['isbn' => $embeddedMetadata]);

        $book = $this->newInstance($metadata);

        $reflectionService = new RuntimeReflectionService();

        $cm = new ClassMetadataInfo($metadata->name);
        $cm->initializeReflection($reflectionService);

        $driver = $this->createAnnotationDriver();
        $driver->loadMetadataForClass($cm->name, $cm);

        $this->assertEquals($cm->columnNames, $metadata->columnNames);
        $this->assertEquals($cm->getTableName(), $metadata->getTableName());
        $this->assertEquals($cm->lifecycleCallbacks, $metadata->lifecycleCallbacks);
        $this->assertEquals($cm->identifier, $metadata->identifier);
        $this->assertEquals($cm->idGenerator, $metadata->idGenerator);
        $this->assertEquals($cm->customRepositoryClassName, $metadata->customRepositoryClassName);
        $this->assertEquals($cm->embeddedClasses, $metadata->embeddedClasses);
        $this->assertEquals($cm->isEmbeddedClass, $metadata->isEmbeddedClass);

        $this->assertEquals(ClassMetadataInfo::FETCH_EXTRA_LAZY, $cm->associationMappings['comments']['fetch']);

        $isbn = $this->newInstance($embeddedMetadata);

        $cm = new ClassMetadataInfo($embeddedMetadata->name);
        $cm->initializeReflection($reflectionService);

        $driver->loadMetadataForClass($cm->name, $cm);

        $this->assertEquals($cm->columnNames, $embeddedMetadata->columnNames);
        $this->assertEquals($cm->embeddedClasses, $embeddedMetadata->embeddedClasses);
        $this->assertEquals($cm->isEmbeddedClass, $embeddedMetadata->isEmbeddedClass);
    }

    public function testLoadPrefixedMetadata(): void
    {
        $this->generator->setAnnotationPrefix('ORM\\');
        $embeddedMetadata = $this->generateIsbnEmbeddableFixture();
        $metadata         = $this->generateBookEntityFixture(['isbn' => $embeddedMetadata]);

        $reader = new AnnotationReader();
        $driver = new AnnotationDriver($reader, []);

        $book = $this->newInstance($metadata);

        $reflectionService = new RuntimeReflectionService();

        $cm = new ClassMetadataInfo($metadata->name);
        $cm->initializeReflection($reflectionService);

        $driver->loadMetadataForClass($cm->name, $cm);

        $this->assertEquals($cm->columnNames, $metadata->columnNames);
        $this->assertEquals($cm->getTableName(), $metadata->getTableName());
        $this->assertEquals($cm->lifecycleCallbacks, $metadata->lifecycleCallbacks);
        $this->assertEquals($cm->identifier, $metadata->identifier);
        $this->assertEquals($cm->idGenerator, $metadata->idGenerator);
        $this->assertEquals($cm->customRepositoryClassName, $metadata->customRepositoryClassName);

        $isbn = $this->newInstance($embeddedMetadata);

        $cm = new ClassMetadataInfo($embeddedMetadata->name);
        $cm->initializeReflection($reflectionService);

        $driver->loadMetadataForClass($cm->name, $cm);

        $this->assertEquals($cm->columnNames, $embeddedMetadata->columnNames);
        $this->assertEquals($cm->embeddedClasses, $embeddedMetadata->embeddedClasses);
        $this->assertEquals($cm->isEmbeddedClass, $embeddedMetadata->isEmbeddedClass);
    }

    /**
     * @group DDC-3272
     */
    public function testMappedSuperclassAnnotationGeneration(): void
    {
        $metadata                     = new ClassMetadataInfo($this->namespace . '\EntityGeneratorBook');
        $metadata->namespace          = $this->namespace;
        $metadata->isMappedSuperclass = true;

        $this->generator->setAnnotationPrefix('ORM\\');
        $this->generator->writeEntityClass($metadata, $this->tmpDir);
        $this->newInstance($metadata); // force instantiation (causes autoloading to kick in)

        $driver = new AnnotationDriver(new AnnotationReader(), []);
        $cm     = new ClassMetadataInfo($metadata->name);

        $cm->initializeReflection(new RuntimeReflectionService());
        $driver->loadMetadataForClass($cm->name, $cm);

        $this->assertTrue($cm->isMappedSuperclass);
    }

    /**
     * @dataProvider getParseTokensInEntityFileData
     */
    public function testParseTokensInEntityFile($php, $classes): void
    {
        $r = new ReflectionObject($this->generator);
        $m = $r->getMethod('parseTokensInEntityFile');
        $m->setAccessible(true);

        $p = $r->getProperty('staticReflection');
        $p->setAccessible(true);

        $ret = $m->invoke($this->generator, $php);
        $this->assertEquals($classes, array_keys($p->getValue($this->generator)));
    }

    /**
     * @group DDC-1784
     */
    public function testGenerateEntityWithSequenceGenerator(): void
    {
        $metadata            = new ClassMetadataInfo($this->namespace . '\DDC1784Entity');
        $metadata->namespace = $this->namespace;
        $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_SEQUENCE);
        $metadata->setSequenceGeneratorDefinition(
            [
                'sequenceName'      => 'DDC1784_ID_SEQ',
                'allocationSize'    => 1,
                'initialValue'      => 2,
            ]
        );
        $this->generator->writeEntityClass($metadata, $this->tmpDir);

        $filename = $this->tmpDir . DIRECTORY_SEPARATOR
                  . $this->namespace . DIRECTORY_SEPARATOR . 'DDC1784Entity.php';

        $this->assertFileExists($filename);
        require_once $filename;

        $reflection = new ReflectionProperty($metadata->name, 'id');
        $docComment = $reflection->getDocComment();

        $this->assertStringContainsString('@Id', $docComment);
        $this->assertStringContainsString('@Column(name="id", type="integer")', $docComment);
        $this->assertStringContainsString('@GeneratedValue(strategy="SEQUENCE")', $docComment);
        $this->assertStringContainsString(
            '@SequenceGenerator(sequenceName="DDC1784_ID_SEQ", allocationSize=1, initialValue=2)',
            $docComment
        );
    }

    /**
     * @group DDC-2079
     */
    public function testGenerateEntityWithMultipleInverseJoinColumns(): void
    {
        $metadata            = new ClassMetadataInfo($this->namespace . '\DDC2079Entity');
        $metadata->namespace = $this->namespace;
        $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_SEQUENCE);
        $metadata->mapManyToMany(
            [
                'fieldName'     => 'centroCustos',
                'targetEntity'  => 'DDC2079CentroCusto',
                'joinTable'     => [
                    'name'                  => 'unidade_centro_custo',
                    'joinColumns'           => [
                        ['name' => 'idorcamento',      'referencedColumnName' => 'idorcamento'],
                        ['name' => 'idunidade',        'referencedColumnName' => 'idunidade'],
                    ],
                    'inverseJoinColumns'    => [
                        ['name' => 'idcentrocusto',    'referencedColumnName' => 'idcentrocusto'],
                        ['name' => 'idpais',           'referencedColumnName' => 'idpais'],
                    ],
                ],
            ]
        );
        $this->generator->writeEntityClass($metadata, $this->tmpDir);

        $filename = $this->tmpDir . DIRECTORY_SEPARATOR
            . $this->namespace . DIRECTORY_SEPARATOR . 'DDC2079Entity.php';

        $this->assertFileExists($filename);
        require_once $filename;

        $property   = new ReflectionProperty($metadata->name, 'centroCustos');
        $docComment = $property->getDocComment();

        //joinColumns
        $this->assertStringContainsString(
            '@JoinColumn(name="idorcamento", referencedColumnName="idorcamento"),',
            $docComment
        );
        $this->assertStringContainsString(
            '@JoinColumn(name="idunidade", referencedColumnName="idunidade")',
            $docComment
        );
        //inverseJoinColumns
        $this->assertStringContainsString(
            '@JoinColumn(name="idcentrocusto", referencedColumnName="idcentrocusto"),',
            $docComment
        );
        $this->assertStringContainsString(
            '@JoinColumn(name="idpais", referencedColumnName="idpais")',
            $docComment
        );
    }

     /**
      * @group DDC-2172
      */
    public function testGetInheritanceTypeString(): void
    {
        $reflection = new ReflectionClass('\Doctrine\ORM\Mapping\ClassMetadataInfo');
        $method     = new ReflectionMethod($this->generator, 'getInheritanceTypeString');
        $constants  = $reflection->getConstants();
        $pattern    = '/^INHERITANCE_TYPE_/';

        $method->setAccessible(true);

        foreach ($constants as $name => $value) {
            if (! preg_match($pattern, $name)) {
                continue;
            }

            $expected = preg_replace($pattern, '', $name);
            $actual   = $method->invoke($this->generator, $value);

            $this->assertEquals($expected, $actual);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid provided InheritanceType: INVALID');

        $method->invoke($this->generator, 'INVALID');
    }

    /**
     * @group DDC-2172
     */
    public function testGetChangeTrackingPolicyString(): void
    {
        $reflection = new ReflectionClass('\Doctrine\ORM\Mapping\ClassMetadata');
        $method     = new ReflectionMethod($this->generator, 'getChangeTrackingPolicyString');
        $constants  = $reflection->getConstants();
        $pattern    = '/^CHANGETRACKING_/';

        $method->setAccessible(true);

        foreach ($constants as $name => $value) {
            if (! preg_match($pattern, $name)) {
                continue;
            }

            $expected = preg_replace($pattern, '', $name);
            $actual   = $method->invoke($this->generator, $value);

            $this->assertEquals($expected, $actual);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid provided ChangeTrackingPolicy: INVALID');

        $method->invoke($this->generator, 'INVALID');
    }

    /**
     * @group DDC-2172
     */
    public function testGetIdGeneratorTypeString(): void
    {
        $reflection = new ReflectionClass('\Doctrine\ORM\Mapping\ClassMetadataInfo');
        $method     = new ReflectionMethod($this->generator, 'getIdGeneratorTypeString');
        $constants  = $reflection->getConstants();
        $pattern    = '/^GENERATOR_TYPE_/';

        $method->setAccessible(true);

        foreach ($constants as $name => $value) {
            if (! preg_match($pattern, $name)) {
                continue;
            }

            $expected = preg_replace($pattern, '', $name);
            $actual   = $method->invoke($this->generator, $value);

            $this->assertEquals($expected, $actual);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid provided IdGeneratorType: INVALID');

        $method->invoke($this->generator, 'INVALID');
    }

    /**
     * @dataProvider getEntityTypeAliasDataProvider
     * @group DDC-1694
     */
    public function testEntityTypeAlias(array $field): void
    {
        $metadata = $this->generateEntityTypeFixture($field);
        $path     = $this->tmpDir . '/' . $this->namespace . '/EntityType.php';

        $this->assertFileExists($path);
        require_once $path;

        $entity    = new $metadata->name();
        $reflClass = new ReflectionClass($metadata->name);

        $type   = $field['phpType'];
        $name   = $field['fieldName'];
        $value  = $field['value'];
        $getter = 'get' . ucfirst($name);
        $setter = 'set' . ucfirst($name);

        $this->assertPhpDocVarType($type, $reflClass->getProperty($name));
        $this->assertPhpDocParamType($type, $reflClass->getMethod($setter));
        $this->assertPhpDocReturnType($type, $reflClass->getMethod($getter));

        $this->assertSame($entity, $entity->{$setter}($value));
        $this->assertEquals($value, $entity->{$getter}());
    }

    /**
     * @group DDC-2372
     */
    public function testTraitPropertiesAndMethodsAreNotDuplicated(): void
    {
        $cmf = new ClassMetadataFactory();
        $em  = $this->getTestEntityManager();
        $cmf->setEntityManager($em);

        $user                = new DDC2372User();
        $metadata            = $cmf->getMetadataFor(get_class($user));
        $metadata->name      = $this->namespace . '\DDC2372User';
        $metadata->namespace = $this->namespace;

        $this->generator->writeEntityClass($metadata, $this->tmpDir);

        $this->assertFileExists($this->tmpDir . '/' . $this->namespace . '/DDC2372User.php');
        require $this->tmpDir . '/' . $this->namespace . '/DDC2372User.php';

        $reflClass = new ReflectionClass($metadata->name);

        $this->assertSame($reflClass->hasProperty('address'), false);
        $this->assertSame($reflClass->hasMethod('setAddress'), false);
        $this->assertSame($reflClass->hasMethod('getAddress'), false);
    }

    /**
     * @group DDC-2372
     */
    public function testTraitPropertiesAndMethodsAreNotDuplicatedInChildClasses(): void
    {
        $cmf = new ClassMetadataFactory();
        $em  = $this->getTestEntityManager();
        $cmf->setEntityManager($em);

        $user                = new DDC2372Admin();
        $metadata            = $cmf->getMetadataFor(get_class($user));
        $metadata->name      = $this->namespace . '\DDC2372Admin';
        $metadata->namespace = $this->namespace;

        $this->generator->writeEntityClass($metadata, $this->tmpDir);

        $this->assertFileExists($this->tmpDir . '/' . $this->namespace . '/DDC2372Admin.php');
        require $this->tmpDir . '/' . $this->namespace . '/DDC2372Admin.php';

        $reflClass = new ReflectionClass($metadata->name);

        $this->assertSame($reflClass->hasProperty('address'), false);
        $this->assertSame($reflClass->hasMethod('setAddress'), false);
        $this->assertSame($reflClass->hasMethod('getAddress'), false);
    }

    /**
     * @group DDC-1590
     */
    public function testMethodsAndPropertiesAreNotDuplicatedInChildClasses(): void
    {
        $cmf = new ClassMetadataFactory();
        $em  = $this->getTestEntityManager();

        $cmf->setEntityManager($em);

        $ns    = $this->namespace;
        $nsdir = $this->tmpDir . '/' . $ns;

        $content = str_replace(
            'namespace Doctrine\Tests\Models\DDC1590',
            'namespace ' . $ns,
            file_get_contents(__DIR__ . '/../../Models/DDC1590/DDC1590User.php')
        );

        $fname = $nsdir . '/DDC1590User.php';
        file_put_contents($fname, $content);
        require $fname;

        $metadata = $cmf->getMetadataFor($ns . '\DDC1590User');
        $this->generator->writeEntityClass($metadata, $this->tmpDir);

        // class DDC1590User extends DDC1590Entity { ... }
        $source = file_get_contents($fname);

        // class _DDC1590User extends DDC1590Entity { ... }
        $source2 = str_replace('class DDC1590User', 'class _DDC1590User', $source);
        $fname2  = $nsdir . '/_DDC1590User.php';
        file_put_contents($fname2, $source2);
        require $fname2;

        // class __DDC1590User { ... }
        $source3 = str_replace('class DDC1590User extends DDC1590Entity', 'class __DDC1590User', $source);
        $fname3  = $nsdir . '/__DDC1590User.php';
        file_put_contents($fname3, $source3);
        require $fname3;

        // class _DDC1590User extends DDC1590Entity { ... }
        $rc2 = new ReflectionClass($ns . '\_DDC1590User');

        $this->assertTrue($rc2->hasProperty('name'));
        $this->assertTrue($rc2->hasProperty('id'));
        $this->assertTrue($rc2->hasProperty('createdAt'));

        $this->assertTrue($rc2->hasMethod('getName'));
        $this->assertTrue($rc2->hasMethod('setName'));
        $this->assertTrue($rc2->hasMethod('getId'));
        $this->assertFalse($rc2->hasMethod('setId'));
        $this->assertTrue($rc2->hasMethod('getCreatedAt'));
        $this->assertTrue($rc2->hasMethod('setCreatedAt'));

        // class __DDC1590User { ... }
        $rc3 = new ReflectionClass($ns . '\__DDC1590User');

        $this->assertTrue($rc3->hasProperty('name'));
        $this->assertFalse($rc3->hasProperty('id'));
        $this->assertFalse($rc3->hasProperty('createdAt'));

        $this->assertTrue($rc3->hasMethod('getName'));
        $this->assertTrue($rc3->hasMethod('setName'));
        $this->assertFalse($rc3->hasMethod('getId'));
        $this->assertFalse($rc3->hasMethod('setId'));
        $this->assertFalse($rc3->hasMethod('getCreatedAt'));
        $this->assertFalse($rc3->hasMethod('setCreatedAt'));
    }

    /**
     * @group DDC-3304
     */
    public function testGeneratedMutableEmbeddablesClass(): void
    {
        $embeddedMetadata = $this->generateTestEmbeddableFixture();
        $metadata         = $this->generateIsbnEmbeddableFixture(['test' => $embeddedMetadata]);

        $isbn = $this->newInstance($metadata);

        $this->assertTrue(class_exists($metadata->name), 'Class does not exist.');
        $this->assertFalse(method_exists($metadata->name, '__construct'), 'EntityGeneratorIsbn::__construct present.');
        $this->assertTrue(method_exists($metadata->name, 'getPrefix'), 'EntityGeneratorIsbn::getPrefix() missing.');
        $this->assertTrue(method_exists($metadata->name, 'setPrefix'), 'EntityGeneratorIsbn::setPrefix() missing.');
        $this->assertTrue(method_exists($metadata->name, 'getGroupNumber'), 'EntityGeneratorIsbn::getGroupNumber() missing.');
        $this->assertTrue(method_exists($metadata->name, 'setGroupNumber'), 'EntityGeneratorIsbn::setGroupNumber() missing.');
        $this->assertTrue(method_exists($metadata->name, 'getPublisherNumber'), 'EntityGeneratorIsbn::getPublisherNumber() missing.');
        $this->assertTrue(method_exists($metadata->name, 'setPublisherNumber'), 'EntityGeneratorIsbn::setPublisherNumber() missing.');
        $this->assertTrue(method_exists($metadata->name, 'getTitleNumber'), 'EntityGeneratorIsbn::getTitleNumber() missing.');
        $this->assertTrue(method_exists($metadata->name, 'setTitleNumber'), 'EntityGeneratorIsbn::setTitleNumber() missing.');
        $this->assertTrue(method_exists($metadata->name, 'getCheckDigit'), 'EntityGeneratorIsbn::getCheckDigit() missing.');
        $this->assertTrue(method_exists($metadata->name, 'setCheckDigit'), 'EntityGeneratorIsbn::setCheckDigit() missing.');
        $this->assertTrue(method_exists($metadata->name, 'getTest'), 'EntityGeneratorIsbn::getTest() missing.');
        $this->assertTrue(method_exists($metadata->name, 'setTest'), 'EntityGeneratorIsbn::setTest() missing.');

        $isbn->setPrefix(978);
        $this->assertSame(978, $isbn->getPrefix());

        $this->newInstance($embeddedMetadata);
        $test = new $embeddedMetadata->name();

        $isbn->setTest($test);
        $this->assertSame($test, $isbn->getTest());

        $reflMethod     = new ReflectionMethod($metadata->name, 'setTest');
        $reflParameters = $reflMethod->getParameters();
        $this->assertEquals($embeddedMetadata->name, $reflParameters[0]->getType()->getName());
    }

    /**
     * @group DDC-3304
     */
    public function testGeneratedImmutableEmbeddablesClass(): void
    {
        $this->generator->setEmbeddablesImmutable(true);
        $embeddedMetadata = $this->generateTestEmbeddableFixture();
        $metadata         = $this->generateIsbnEmbeddableFixture(['test' => $embeddedMetadata]);

        $this->loadEntityClass($embeddedMetadata);
        $this->loadEntityClass($metadata);

        $this->assertTrue(class_exists($metadata->name), 'Class does not exist.');
        $this->assertTrue(method_exists($metadata->name, '__construct'), 'EntityGeneratorIsbn::__construct missing.');
        $this->assertTrue(method_exists($metadata->name, 'getPrefix'), 'EntityGeneratorIsbn::getPrefix() missing.');
        $this->assertFalse(method_exists($metadata->name, 'setPrefix'), 'EntityGeneratorIsbn::setPrefix() present.');
        $this->assertTrue(method_exists($metadata->name, 'getGroupNumber'), 'EntityGeneratorIsbn::getGroupNumber() missing.');
        $this->assertFalse(method_exists($metadata->name, 'setGroupNumber'), 'EntityGeneratorIsbn::setGroupNumber() present.');
        $this->assertTrue(method_exists($metadata->name, 'getPublisherNumber'), 'EntityGeneratorIsbn::getPublisherNumber() missing.');
        $this->assertFalse(method_exists($metadata->name, 'setPublisherNumber'), 'EntityGeneratorIsbn::setPublisherNumber() present.');
        $this->assertTrue(method_exists($metadata->name, 'getTitleNumber'), 'EntityGeneratorIsbn::getTitleNumber() missing.');
        $this->assertFalse(method_exists($metadata->name, 'setTitleNumber'), 'EntityGeneratorIsbn::setTitleNumber() present.');
        $this->assertTrue(method_exists($metadata->name, 'getCheckDigit'), 'EntityGeneratorIsbn::getCheckDigit() missing.');
        $this->assertFalse(method_exists($metadata->name, 'setCheckDigit'), 'EntityGeneratorIsbn::setCheckDigit() present.');
        $this->assertTrue(method_exists($metadata->name, 'getTest'), 'EntityGeneratorIsbn::getTest() missing.');
        $this->assertFalse(method_exists($metadata->name, 'setTest'), 'EntityGeneratorIsbn::setTest() present.');

        $test = new $embeddedMetadata->name(1, new DateTime());
        $isbn = new $metadata->name($test, 978, 3, 12, 732320, 83);

        $reflMethod     = new ReflectionMethod($isbn, '__construct');
        $reflParameters = $reflMethod->getParameters();

        $this->assertCount(6, $reflParameters);

        $this->assertSame($embeddedMetadata->name, $reflParameters[0]->getType()->getName());
        $this->assertSame('test', $reflParameters[0]->getName());
        $this->assertFalse($reflParameters[0]->isOptional());

        $this->assertSame('prefix', $reflParameters[1]->getName());
        $this->assertFalse($reflParameters[1]->isOptional());

        $this->assertSame('groupNumber', $reflParameters[2]->getName());
        $this->assertFalse($reflParameters[2]->isOptional());

        $this->assertSame('publisherNumber', $reflParameters[3]->getName());
        $this->assertFalse($reflParameters[3]->isOptional());

        $this->assertSame('titleNumber', $reflParameters[4]->getName());
        $this->assertFalse($reflParameters[4]->isOptional());

        $this->assertSame('checkDigit', $reflParameters[5]->getName());
        $this->assertFalse($reflParameters[5]->isOptional());

        $reflMethod     = new ReflectionMethod($test, '__construct');
        $reflParameters = $reflMethod->getParameters();

        $this->assertCount(4, $reflParameters);

        $this->assertSame('field1', $reflParameters[0]->getName());
        $this->assertFalse($reflParameters[0]->isOptional());

        $this->assertSame('DateTime', $reflParameters[1]->getType()->getName());
        $this->assertSame('field3', $reflParameters[1]->getName());
        $this->assertFalse($reflParameters[1]->isOptional());

        $this->assertSame('field2', $reflParameters[2]->getName());
        $this->assertTrue($reflParameters[2]->isOptional());

        $this->assertSame('DateTime', $reflParameters[3]->getType()->getName());
        $this->assertSame('field4', $reflParameters[3]->getName());
        $this->assertTrue($reflParameters[3]->isOptional());
    }

    public function testRegenerateEntityClass(): void
    {
        $metadata = $this->generateBookEntityFixture();
        $this->loadEntityClass($metadata);

        $className = basename(str_replace('\\', '/', $metadata->name));
        $path      = $this->tmpDir . '/' . $this->namespace . '/' . $className . '.php';
        $classTest = file_get_contents($path);

        $this->generator->setRegenerateEntityIfExists(true);
        $this->generator->setBackupExisting(false);

        $this->generator->writeEntityClass($metadata, $this->tmpDir);
        $classNew = file_get_contents($path);

        $this->assertSame($classTest, $classNew);
    }

    /**
     * @return list<array{
     *     fieldName: string,
     *     phpType: string,
     *     dbType: string,
     *     value: mixed
     * }>
     */
    public function getEntityTypeAliasDataProvider(): array
    {
        return [
            [
                [
                    'fieldName' => 'datetimetz',
                    'phpType' => '\\DateTime',
                    'dbType' => 'datetimetz',
                    'value' => new DateTime(),
                ],
            ],
            [
                [
                    'fieldName' => 'datetime',
                    'phpType' => '\\DateTime',
                    'dbType' => 'datetime',
                    'value' => new DateTime(),
                ],
            ],
            [
                [
                    'fieldName' => 'date',
                    'phpType' => '\\DateTime',
                    'dbType' => 'date',
                    'value' => new DateTime(),
                ],
            ],
            [
                [
                    'fieldName' => 'time',
                    'phpType' => '\DateTime',
                    'dbType' => 'time',
                    'value' => new DateTime(),
                ],
            ],
            [
                [
                    'fieldName' => 'object',
                    'phpType' => '\stdClass',
                    'dbType' => 'object',
                    'value' => new stdClass(),
                ],
            ],
            [
                [
                    'fieldName' => 'bigint',
                    'phpType' => 'int',
                    'dbType' => 'bigint',
                    'value' => 11,
                ],
            ],
            [
                [
                    'fieldName' => 'smallint',
                    'phpType' => 'int',
                    'dbType' => 'smallint',
                    'value' => 22,
                ],
            ],
            [
                [
                    'fieldName' => 'text',
                    'phpType' => 'string',
                    'dbType' => 'text',
                    'value' => 'text',
                ],
            ],
            [
                [
                    'fieldName' => 'blob',
                    'phpType' => 'string',
                    'dbType' => 'blob',
                    'value' => 'blob',
                ],
            ],
            [
                [
                    'fieldName' => 'guid',
                    'phpType' => 'string',
                    'dbType' => 'guid',
                    'value' => '00000000-0000-0000-0000-000000000001',
                ],
            ],
            [
                [
                    'fieldName' => 'decimal',
                    'phpType' => 'string',
                    'dbType' => 'decimal',
                    'value' => '12.34',
                ],
            ],
        ];
    }

    /**
     * @psalm-return list<array{string, array{string}}>
     */
    public function getParseTokensInEntityFileData(): array
    {
        return [
            [
                '<?php namespace Foo\Bar; class Baz {}',
                ['Foo\Bar\Baz'],
            ],
            [
                '<?php namespace Foo\Bar; use Foo; class Baz {}',
                ['Foo\Bar\Baz'],
            ],
            [
                '<?php namespace /*Comment*/ Foo\Bar; /** Foo */class /* Comment */ Baz {}',
                ['Foo\Bar\Baz'],
            ],
            [
                '
<?php namespace
/*Comment*/
Foo\Bar
;

/** Foo */
class
/* Comment */
 Baz {}
     ',
                ['Foo\Bar\Baz'],
            ],
            [
                '
<?php namespace Foo\Bar; class Baz {
    public static function someMethod(){
        return self::class;
    }
}
',
                ['Foo\Bar\Baz'],
            ],
        ];
    }

    private function assertPhpDocVarType(string $type, ReflectionProperty $property): void
    {
        $docComment = $property->getDocComment();
        $regex      = '/@var\s+([\S]+)$/m';

        $this->assertMatchesRegularExpression($regex, $docComment);
        $this->assertEquals(1, preg_match($regex, $docComment, $matches));
        $this->assertEquals($type, $matches[1]);
    }

    private function assertPhpDocReturnType(string $type, ReflectionMethod $method): void
    {
        $docComment = $method->getDocComment();
        $regex      = '/@return\s+([\S]+)(\s+.*)$/m';

        $this->assertMatchesRegularExpression($regex, $docComment);
        $this->assertEquals(1, preg_match($regex, $docComment, $matches));
        $this->assertEquals($type, $matches[1]);
    }

    /**
     * @param ReflectionProperty $method
     */
    private function assertPhpDocParamType(string $type, ReflectionMethod $method): void
    {
        $this->assertEquals(1, preg_match('/@param\s+([^\s]+)/', $method->getDocComment(), $matches));
        $this->assertEquals($type, $matches[1]);
    }

    /**
     * @group 6703
     * @dataProvider columnOptionsProvider
     */
    public function testOptionsAreGeneratedProperly(string $expectedAnnotation, array $fieldConfiguration): void
    {
        $metadata            = new ClassMetadataInfo($this->namespace . '\GH6703Options');
        $metadata->namespace = $this->namespace;
        $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
        $metadata->mapField(['fieldName' => 'test'] + $fieldConfiguration);
        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_SEQUENCE);
        $this->generator->writeEntityClass($metadata, $this->tmpDir);

        $filename = $this->tmpDir . DIRECTORY_SEPARATOR . $this->namespace . DIRECTORY_SEPARATOR . 'GH6703Options.php';

        self::assertFileExists($filename);
        require_once $filename;

        $property   = new ReflectionProperty($metadata->name, 'test');
        $docComment = $property->getDocComment();

        self::assertStringContainsString($expectedAnnotation, $docComment);
    }

    public function columnOptionsProvider(): array
    {
        return [
            'string-default'   => [
                '@Column(name="test", type="string", length=10, options={"default"="testing"})',
                ['type' => 'string', 'length' => 10, 'options' => ['default' => 'testing']],
            ],
            'string-fixed'     => [
                '@Column(name="test", type="string", length=10, options={"fixed"=true})',
                ['type' => 'string', 'length' => 10, 'options' => ['fixed' => true]],
            ],
            'string-comment'   => [
                '@Column(name="test", type="string", length=10, options={"comment"="testing"})',
                ['type' => 'string', 'length' => 10, 'options' => ['comment' => 'testing']],
            ],
            'string-comment-quote'   => [
                '@Column(name="test", type="string", length=10, options={"comment"="testing ""quotes"""})',
                ['type' => 'string', 'length' => 10, 'options' => ['comment' => 'testing "quotes"']],
            ],
            'string-collation' => [
                '@Column(name="test", type="string", length=10, options={"collation"="utf8mb4_general_ci"})',
                ['type' => 'string', 'length' => 10, 'options' => ['collation' => 'utf8mb4_general_ci']],
            ],
            'string-check'     => [
                '@Column(name="test", type="string", length=10, options={"check"="CHECK (test IN (""test""))"})',
                ['type' => 'string', 'length' => 10, 'options' => ['check' => 'CHECK (test IN (""test""))']],
            ],
            'string-all'       => [
                '@Column(name="test", type="string", length=10, options={"default"="testing","fixed"=true,"comment"="testing","collation"="utf8mb4_general_ci","check"="CHECK (test IN (""test""))"})',
                [
                    'type' => 'string',
                    'length' => 10,
                    'options' => [
                        'default' => 'testing',
                        'fixed' => true,
                        'comment' => 'testing',
                        'collation' => 'utf8mb4_general_ci',
                        'check' => 'CHECK (test IN (""test""))',
                    ],
                ],
            ],
            'int-default'      => [
                '@Column(name="test", type="integer", options={"default"="10"})',
                ['type' => 'integer', 'options' => ['default' => 10]],
            ],
            'int-unsigned'     => [
                '@Column(name="test", type="integer", options={"unsigned"=true})',
                ['type' => 'integer', 'options' => ['unsigned' => true]],
            ],
            'int-comment'      => [
                '@Column(name="test", type="integer", options={"comment"="testing"})',
                ['type' => 'integer', 'options' => ['comment' => 'testing']],
            ],
            'int-check'        => [
                '@Column(name="test", type="integer", options={"check"="CHECK (test > 5)"})',
                ['type' => 'integer', 'options' => ['check' => 'CHECK (test > 5)']],
            ],
            'int-all'        => [
                '@Column(name="test", type="integer", options={"default"="10","unsigned"=true,"comment"="testing","check"="CHECK (test > 5)"})',
                [
                    'type' => 'integer',
                    'options' => [
                        'default' => 10,
                        'unsigned' => true,
                        'comment' => 'testing',
                        'check' => 'CHECK (test > 5)',
                    ],
                ],
            ],
        ];
    }
}

class EntityGeneratorAuthor
{
}
class EntityGeneratorComment
{
}

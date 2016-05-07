<?php

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\Mapping\RuntimeReflectionService;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\EntityGenerator;
use Doctrine\Tests\Models\DDC2372\DDC2372Admin;
use Doctrine\Tests\Models\DDC2372\DDC2372User;
use Doctrine\Tests\OrmTestCase;

class EntityGeneratorTest extends OrmTestCase
{

    /**
     * @var EntityGenerator
     */
    private $_generator;
    private $_tmpDir;
    private $_namespace;

    public function setUp()
    {
        $this->_namespace = uniqid("doctrine_");
        $this->_tmpDir = \sys_get_temp_dir();
        \mkdir($this->_tmpDir . \DIRECTORY_SEPARATOR . $this->_namespace);
        $this->_generator = new EntityGenerator();
        $this->_generator->setAnnotationPrefix("");
        $this->_generator->setGenerateAnnotations(true);
        $this->_generator->setGenerateStubMethods(true);
        $this->_generator->setRegenerateEntityIfExists(false);
        $this->_generator->setUpdateEntityIfExists(true);
        $this->_generator->setFieldVisibility(EntityGenerator::FIELD_VISIBLE_PROTECTED);
    }

    public function tearDown()
    {
        $ri = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->_tmpDir . '/' . $this->_namespace));
        foreach ($ri AS $file) {
            /* @var $file \SplFileInfo */
            if ($file->isFile()) {
                \unlink($file->getPathname());
            }
        }
        rmdir($this->_tmpDir . '/' . $this->_namespace);
    }

    /**
     * @param ClassMetadata[] $embeddedClasses
     *
     * @return ClassMetadata
     */
    public function generateBookEntityFixture(array $embeddedClasses = [])
    {
        $metadata = new ClassMetadata($this->_namespace . '\EntityGeneratorBook');

        $metadata->namespace = $this->_namespace;
        $metadata->customRepositoryClassName = $this->_namespace  . '\EntityGeneratorBookRepository';

        $metadata->table['name'] = 'book';
        $metadata->table['uniqueConstraints']['name_uniq'] = ['columns' => ['name']];
        $metadata->table['indexes']['status_idx'] = ['columns' => ['status']];

        $metadata->mapField(['fieldName' => 'name', 'type' => 'string']);
        $metadata->mapField(['fieldName' => 'status', 'type' => 'string', 'options' => ['default' => 'published']]);
        $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
        $metadata->mapOneToOne(
            ['fieldName' => 'author', 'targetEntity' => EntityGeneratorAuthor::class, 'mappedBy' => 'book']
        );
        $joinColumns = [
            ['name' => 'author_id', 'referencedColumnName' => 'id']
        ];
        $metadata->mapManyToMany(
            [
            'fieldName' => 'comments',
            'targetEntity' => EntityGeneratorComment::class,
            'fetch' => ClassMetadata::FETCH_EXTRA_LAZY,
            'joinTable' => [
                'name' => 'book_comment',
                'joinColumns' => [['name' => 'book_id', 'referencedColumnName' => 'id']],
                'inverseJoinColumns' => [['name' => 'comment_id', 'referencedColumnName' => 'id']],
            ],
            ]
        );
        $metadata->addLifecycleCallback('loading', 'postLoad');
        $metadata->addLifecycleCallback('willBeRemoved', 'preRemove');
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

        foreach ($embeddedClasses as $fieldName => $embeddedClass) {
            $this->mapNestedEmbedded($fieldName, $metadata, $embeddedClass);
            $this->mapEmbedded($fieldName, $metadata, $embeddedClass);
        }

        $this->_generator->writeEntityClass($metadata, $this->_tmpDir);

        return $metadata;
    }

    private function generateEntityTypeFixture(array $field)
    {
        $metadata = new ClassMetadata($this->_namespace . '\EntityType');

        $metadata->namespace = $this->_namespace;

        $metadata->table['name'] = 'entity_type';

        $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_AUTO);

        $name  = $field['fieldName'];
        $type  = $field['dbType'];

        $metadata->mapField(['fieldName' => $name, 'type' => $type]);

        $this->_generator->writeEntityClass($metadata, $this->_tmpDir);

        return $metadata;
    }

    /**
     * @return ClassMetadata
     */
    private function generateIsbnEmbeddableFixture(array $embeddedClasses = [])
    {
        $metadata = new ClassMetadata($this->_namespace . '\EntityGeneratorIsbn');

        $metadata->namespace = $this->_namespace;
        $metadata->isEmbeddedClass = true;

        $metadata->mapField(['fieldName' => 'prefix', 'type' => 'integer']);
        $metadata->mapField(['fieldName' => 'groupNumber', 'type' => 'integer']);
        $metadata->mapField(['fieldName' => 'publisherNumber', 'type' => 'integer']);
        $metadata->mapField(['fieldName' => 'titleNumber', 'type' => 'integer']);
        $metadata->mapField(['fieldName' => 'checkDigit', 'type' => 'integer']);

        foreach ($embeddedClasses as $fieldName => $embeddedClass) {
            $this->mapEmbedded($fieldName, $metadata, $embeddedClass);
        }

        $this->_generator->writeEntityClass($metadata, $this->_tmpDir);

        return $metadata;
    }

    /**
     * @return ClassMetadata
     */
    private function generateTestEmbeddableFixture()
    {
        $metadata = new ClassMetadata($this->_namespace . '\EntityGeneratorTestEmbeddable');

        $metadata->namespace = $this->_namespace;
        $metadata->isEmbeddedClass = true;

        $metadata->mapField(['fieldName' => 'field1', 'type' => 'integer']);
        $metadata->mapField(['fieldName' => 'field2', 'type' => 'integer', 'nullable' => true]);
        $metadata->mapField(['fieldName' => 'field3', 'type' => 'datetime']);
        $metadata->mapField(['fieldName' => 'field4', 'type' => 'datetime', 'nullable' => true]);

        $this->_generator->writeEntityClass($metadata, $this->_tmpDir);

        return $metadata;
    }

    /**
     * @param string        $fieldName
     * @param ClassMetadata $classMetadata
     * @param ClassMetadata $embeddableMetadata
     * @param string|null   $columnPrefix
     */
    private function mapEmbedded(
        $fieldName,
        ClassMetadata $classMetadata,
        ClassMetadata $embeddableMetadata,
        $columnPrefix = false
    ) {
        $classMetadata->mapEmbedded(
            ['fieldName' => $fieldName, 'class' => $embeddableMetadata->name, 'columnPrefix' => $columnPrefix]
        );
    }

    /**
     * @param string        $fieldName
     * @param ClassMetadata $classMetadata
     * @param ClassMetadata $embeddableMetadata
     */
    private function mapNestedEmbedded(
        $fieldName,
        ClassMetadata $classMetadata,
        ClassMetadata $embeddableMetadata
    ) {
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

    /**
     * @param ClassMetadata $metadata
     */
    private function loadEntityClass(ClassMetadata $metadata)
    {
        $className = basename(str_replace('\\', '/', $metadata->name));
        $path = $this->_tmpDir . '/' . $this->_namespace . '/' . $className . '.php';

        self::assertFileExists($path);

        require_once $path;
    }

    /**
     * @param  ClassMetadata $metadata
     *
     * @return mixed An instance of the given metadata's class.
     */
    public function newInstance(ClassMetadata $metadata)
    {
        $this->loadEntityClass($metadata);

        return new $metadata->name;
    }

    public function testGeneratedEntityClass()
    {
        $testMetadata = $this->generateTestEmbeddableFixture();
        $isbnMetadata = $this->generateIsbnEmbeddableFixture(['test' => $testMetadata]);
        $metadata = $this->generateBookEntityFixture(['isbn' => $isbnMetadata]);

        $book = $this->newInstance($metadata);
        self::assertTrue(class_exists($metadata->name), "Class does not exist.");
        self::assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', '__construct'), "EntityGeneratorBook::__construct() missing.");
        self::assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'getId'), "EntityGeneratorBook::getId() missing.");
        self::assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'setName'), "EntityGeneratorBook::setName() missing.");
        self::assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'getName'), "EntityGeneratorBook::getName() missing.");
        self::assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'setStatus'), "EntityGeneratorBook::setStatus() missing.");
        self::assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'getStatus'), "EntityGeneratorBook::getStatus() missing.");
        self::assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'setAuthor'), "EntityGeneratorBook::setAuthor() missing.");
        self::assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'getAuthor'), "EntityGeneratorBook::getAuthor() missing.");
        self::assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'getComments'), "EntityGeneratorBook::getComments() missing.");
        self::assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'addComment'), "EntityGeneratorBook::addComment() missing.");
        self::assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'removeComment'), "EntityGeneratorBook::removeComment() missing.");
        self::assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'setIsbn'), "EntityGeneratorBook::setIsbn() missing.");
        self::assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'getIsbn'), "EntityGeneratorBook::getIsbn() missing.");

        $reflClass = new \ReflectionClass($metadata->name);

        self::assertCount(6, $reflClass->getProperties());
        self::assertCount(15, $reflClass->getMethods());

        self::assertEquals('published', $book->getStatus());

        $book->setName('Jonathan H. Wage');
        self::assertEquals('Jonathan H. Wage', $book->getName());

        $reflMethod = new \ReflectionMethod($metadata->name, 'addComment');
        $addCommentParameters = $reflMethod->getParameters();
        self::assertEquals('comment', $addCommentParameters[0]->getName());

        $reflMethod = new \ReflectionMethod($metadata->name, 'removeComment');
        $removeCommentParameters = $reflMethod->getParameters();
        self::assertEquals('comment', $removeCommentParameters[0]->getName());

        $author = new EntityGeneratorAuthor();
        $book->setAuthor($author);
        self::assertEquals($author, $book->getAuthor());

        $comment = new EntityGeneratorComment();
        self::assertInstanceOf($metadata->name, $book->addComment($comment));
        self::assertInstanceOf(ArrayCollection::class, $book->getComments());
        self::assertEquals(new ArrayCollection([$comment]), $book->getComments());
        self::assertInternalType('boolean', $book->removeComment($comment));
        self::assertEquals(new ArrayCollection([]), $book->getComments());

        $this->newInstance($isbnMetadata);
        $isbn = new $isbnMetadata->name();

        $book->setIsbn($isbn);
        self::assertSame($isbn, $book->getIsbn());

        $reflMethod = new \ReflectionMethod($metadata->name, 'setIsbn');
        $reflParameters = $reflMethod->getParameters();
        self::assertEquals($isbnMetadata->name, $reflParameters[0]->getClass()->name);
    }

    public function testEntityUpdatingWorks()
    {
        $metadata = $this->generateBookEntityFixture(['isbn' => $this->generateIsbnEmbeddableFixture()]);

        $metadata->mapField(['fieldName' => 'test', 'type' => 'string']);

        $testEmbeddableMetadata = $this->generateTestEmbeddableFixture();
        $this->mapEmbedded('testEmbedded', $metadata, $testEmbeddableMetadata);

        $this->_generator->writeEntityClass($metadata, $this->_tmpDir);

        self::assertFileExists($this->_tmpDir . "/" . $this->_namespace . "/EntityGeneratorBook.php~");

        $book = $this->newInstance($metadata);
        $reflClass = new \ReflectionClass($metadata->name);

        self::assertTrue($reflClass->hasProperty('name'), "Regenerating keeps property 'name'.");
        self::assertTrue($reflClass->hasProperty('status'), "Regenerating keeps property 'status'.");
        self::assertTrue($reflClass->hasProperty('id'), "Regenerating keeps property 'id'.");
        self::assertTrue($reflClass->hasProperty('isbn'), "Regenerating keeps property 'isbn'.");

        self::assertTrue($reflClass->hasProperty('test'), "Check for property test failed.");
        self::assertTrue($reflClass->getProperty('test')->isProtected(), "Check for protected property test failed.");
        self::assertTrue($reflClass->hasProperty('testEmbedded'), "Check for property testEmbedded failed.");
        self::assertTrue($reflClass->getProperty('testEmbedded')->isProtected(), "Check for protected property testEmbedded failed.");
        self::assertTrue($reflClass->hasMethod('getTest'), "Check for method 'getTest' failed.");
        self::assertTrue($reflClass->getMethod('getTest')->isPublic(), "Check for public visibility of method 'getTest' failed.");
        self::assertTrue($reflClass->hasMethod('setTest'), "Check for method 'setTest' failed.");
        self::assertTrue($reflClass->getMethod('setTest')->isPublic(), "Check for public visibility of method 'setTest' failed.");
        self::assertTrue($reflClass->hasMethod('getTestEmbedded'), "Check for method 'getTestEmbedded' failed.");
        self::assertTrue(
            $reflClass->getMethod('getTestEmbedded')->isPublic(),
            "Check for public visibility of method 'getTestEmbedded' failed."
        );
        self::assertTrue($reflClass->hasMethod('setTestEmbedded'), "Check for method 'setTestEmbedded' failed.");
        self::assertTrue(
            $reflClass->getMethod('setTestEmbedded')->isPublic(),
            "Check for public visibility of method 'setTestEmbedded' failed."
        );
    }

    /**
     * @group DDC-3152
     */
    public function testDoesNotRegenerateExistingMethodsWithDifferentCase()
    {
        $metadata = $this->generateBookEntityFixture(['isbn' => $this->generateIsbnEmbeddableFixture()]);

        // Workaround to change existing fields case (just to simulate the use case)
        $metadata->fieldMappings['status']['fieldName'] = 'STATUS';
        $metadata->embeddedClasses['ISBN'] = $metadata->embeddedClasses['isbn'];
        unset($metadata->embeddedClasses['isbn']);

        // Should not throw a PHP fatal error
        $this->_generator->writeEntityClass($metadata, $this->_tmpDir);

        self::assertFileExists($this->_tmpDir . "/" . $this->_namespace . "/EntityGeneratorBook.php~");

        $this->newInstance($metadata);
        $reflClass = new \ReflectionClass($metadata->name);

        self::assertTrue($reflClass->hasProperty('status'));
        self::assertTrue($reflClass->hasProperty('STATUS'));
        self::assertTrue($reflClass->hasProperty('isbn'));
        self::assertTrue($reflClass->hasProperty('ISBN'));
        self::assertTrue($reflClass->hasMethod('getStatus'));
        self::assertTrue($reflClass->hasMethod('setStatus'));
        self::assertTrue($reflClass->hasMethod('getIsbn'));
        self::assertTrue($reflClass->hasMethod('setIsbn'));
    }

    /**
     * @group DDC-2121
     */
    public function testMethodDocBlockShouldStartWithBackSlash()
    {
        $embeddedMetadata = $this->generateIsbnEmbeddableFixture();
        $metadata = $this->generateBookEntityFixture(['isbn' => $embeddedMetadata]);
        $book     = $this->newInstance($metadata);

        self::assertPhpDocVarType('\Doctrine\Common\Collections\Collection', new \ReflectionProperty($book, 'comments'));
        self::assertPhpDocReturnType('\Doctrine\Common\Collections\Collection', new \ReflectionMethod($book, 'getComments'));
        self::assertPhpDocParamType('\Doctrine\Tests\ORM\Tools\EntityGeneratorComment', new \ReflectionMethod($book, 'addComment'));
        self::assertPhpDocReturnType('EntityGeneratorBook', new \ReflectionMethod($book, 'addComment'));
        self::assertPhpDocParamType('\Doctrine\Tests\ORM\Tools\EntityGeneratorComment', new \ReflectionMethod($book, 'removeComment'));
        self::assertPhpDocReturnType('boolean', new \ReflectionMethod($book, 'removeComment'));

        self::assertPhpDocVarType('\Doctrine\Tests\ORM\Tools\EntityGeneratorAuthor', new \ReflectionProperty($book, 'author'));
        self::assertPhpDocReturnType('\Doctrine\Tests\ORM\Tools\EntityGeneratorAuthor|null', new \ReflectionMethod($book, 'getAuthor'));
        self::assertPhpDocParamType('\Doctrine\Tests\ORM\Tools\EntityGeneratorAuthor|null', new \ReflectionMethod($book, 'setAuthor'));

        $expectedClassName = '\\' . $embeddedMetadata->name;
        self::assertPhpDocVarType($expectedClassName, new \ReflectionProperty($book, 'isbn'));
        self::assertPhpDocReturnType($expectedClassName, new \ReflectionMethod($book, 'getIsbn'));
        self::assertPhpDocParamType($expectedClassName, new \ReflectionMethod($book, 'setIsbn'));
    }

    public function testEntityExtendsStdClass()
    {
        $this->_generator->setClassToExtend('stdClass');
        $metadata = $this->generateBookEntityFixture();

        $book = $this->newInstance($metadata);
        self::assertInstanceOf('stdClass', $book);

        $metadata = $this->generateIsbnEmbeddableFixture();
        $isbn = $this->newInstance($metadata);
        self::assertInstanceOf('stdClass', $isbn);
    }

    public function testLifecycleCallbacks()
    {
        $metadata = $this->generateBookEntityFixture();

        $book = $this->newInstance($metadata);
        $reflClass = new \ReflectionClass($metadata->name);

        self::assertTrue($reflClass->hasMethod('loading'), "Check for postLoad lifecycle callback.");
        self::assertTrue($reflClass->hasMethod('willBeRemoved'), "Check for preRemove lifecycle callback.");
    }

    public function testLoadMetadata()
    {
        $embeddedMetadata = $this->generateIsbnEmbeddableFixture();
        $metadata = $this->generateBookEntityFixture(['isbn' => $embeddedMetadata]);

        $book = $this->newInstance($metadata);

        $reflectionService = new RuntimeReflectionService();

        $cm = new ClassMetadata($metadata->name);
        $cm->initializeReflection($reflectionService);

        $driver = $this->createAnnotationDriver();
        $driver->loadMetadataForClass($cm->name, $cm);

        self::assertEquals($cm->getTableName(), $metadata->getTableName());
        self::assertEquals($cm->lifecycleCallbacks, $metadata->lifecycleCallbacks);
        self::assertEquals($cm->identifier, $metadata->identifier);
        self::assertEquals($cm->idGenerator, $metadata->idGenerator);
        self::assertEquals($cm->customRepositoryClassName, $metadata->customRepositoryClassName);
        self::assertEquals($cm->embeddedClasses, $metadata->embeddedClasses);
        self::assertEquals($cm->isEmbeddedClass, $metadata->isEmbeddedClass);

        self::assertEquals(ClassMetadata::FETCH_EXTRA_LAZY, $cm->associationMappings['comments']['fetch']);

        $isbn = $this->newInstance($embeddedMetadata);

        $cm = new ClassMetadata($embeddedMetadata->name);
        $cm->initializeReflection($reflectionService);

        $driver->loadMetadataForClass($cm->name, $cm);

        self::assertEquals($cm->embeddedClasses, $embeddedMetadata->embeddedClasses);
        self::assertEquals($cm->isEmbeddedClass, $embeddedMetadata->isEmbeddedClass);
    }

    public function testLoadPrefixedMetadata()
    {
        $this->_generator->setAnnotationPrefix('ORM\\');
        $embeddedMetadata = $this->generateIsbnEmbeddableFixture();
        $metadata = $this->generateBookEntityFixture(['isbn' => $embeddedMetadata]);

        $reader = new AnnotationReader();
        $driver = new AnnotationDriver($reader, []);

        $book = $this->newInstance($metadata);

        $reflectionService = new RuntimeReflectionService();

        $cm = new ClassMetadata($metadata->name);
        $cm->initializeReflection($reflectionService);

        $driver->loadMetadataForClass($cm->name, $cm);

        self::assertEquals($cm->getTableName(), $metadata->getTableName());
        self::assertEquals($cm->lifecycleCallbacks, $metadata->lifecycleCallbacks);
        self::assertEquals($cm->identifier, $metadata->identifier);
        self::assertEquals($cm->idGenerator, $metadata->idGenerator);
        self::assertEquals($cm->customRepositoryClassName, $metadata->customRepositoryClassName);

        $isbn = $this->newInstance($embeddedMetadata);

        $cm = new ClassMetadata($embeddedMetadata->name);
        $cm->initializeReflection($reflectionService);

        $driver->loadMetadataForClass($cm->name, $cm);

        self::assertEquals($cm->embeddedClasses, $embeddedMetadata->embeddedClasses);
        self::assertEquals($cm->isEmbeddedClass, $embeddedMetadata->isEmbeddedClass);
    }

    /**
     * @group DDC-3272
     */
    public function testMappedSuperclassAnnotationGeneration()
    {
        $metadata                     = new ClassMetadata($this->_namespace . '\EntityGeneratorBook');
        $metadata->namespace          = $this->_namespace;
        $metadata->isMappedSuperclass = true;

        $this->_generator->setAnnotationPrefix('ORM\\');
        $this->_generator->writeEntityClass($metadata, $this->_tmpDir);
        $this->newInstance($metadata); // force instantiation (causes autoloading to kick in)

        $driver = new AnnotationDriver(new AnnotationReader(), []);
        $cm     = new ClassMetadata($metadata->name);

        $cm->initializeReflection(new RuntimeReflectionService);
        $driver->loadMetadataForClass($cm->name, $cm);

        self::assertTrue($cm->isMappedSuperclass);
    }

    /**
     * @dataProvider getParseTokensInEntityFileData
     */
    public function testParseTokensInEntityFile($php, $classes)
    {
        $r = new \ReflectionObject($this->_generator);
        $m = $r->getMethod('parseTokensInEntityFile');
        $m->setAccessible(true);

        $p = $r->getProperty('staticReflection');
        $p->setAccessible(true);

        $ret = $m->invoke($this->_generator, $php);
        self::assertEquals($classes, array_keys($p->getValue($this->_generator)));
    }

    /**
     * @group DDC-1784
     */
    public function testGenerateEntityWithSequenceGenerator()
    {
        $metadata               = new ClassMetadata($this->_namespace . '\DDC1784Entity');
        $metadata->namespace    = $this->_namespace;
        $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_SEQUENCE);
        $metadata->setSequenceGeneratorDefinition(
            [
            'sequenceName'      => 'DDC1784_ID_SEQ',
            'allocationSize'    => 1,
            'initialValue'      => 2
            ]
        );
        $this->_generator->writeEntityClass($metadata, $this->_tmpDir);

        $filename = $this->_tmpDir . DIRECTORY_SEPARATOR
                  . $this->_namespace . DIRECTORY_SEPARATOR . 'DDC1784Entity.php';

        self::assertFileExists($filename);
        require_once $filename;


        $reflection = new \ReflectionProperty($metadata->name, 'id');
        $docComment = $reflection->getDocComment();

        self::assertContains('@Id', $docComment);
        self::assertContains('@Column(name="id", type="integer")', $docComment);
        self::assertContains('@GeneratedValue(strategy="SEQUENCE")', $docComment);
        self::assertContains('@SequenceGenerator(sequenceName="DDC1784_ID_SEQ", allocationSize=1, initialValue=2)', $docComment);
    }

    /**
     * @group DDC-2079
     */
    public function testGenerateEntityWithMultipleInverseJoinColumns()
    {
        $metadata               = new ClassMetadata($this->_namespace . '\DDC2079Entity');
        $metadata->namespace    = $this->_namespace;
        $metadata->mapField(['fieldName' => 'id', 'type' => 'integer', 'id' => true]);
        $metadata->setIdGeneratorType(ClassMetadata::GENERATOR_TYPE_SEQUENCE);
        $metadata->mapManyToMany(
            [
            'fieldName'     => 'centroCustos',
            'targetEntity'  => 'DDC2079CentroCusto',
            'joinTable'     => [
                'name'                  => 'unidade_centro_custo',
                'joinColumns'           => [
                    ['name' => 'idorcamento',      'referencedColumnName' => 'idorcamento'],
                    ['name' => 'idunidade',        'referencedColumnName' => 'idunidade']
                ],
                'inverseJoinColumns'    => [
                    ['name' => 'idcentrocusto',    'referencedColumnName' => 'idcentrocusto'],
                    ['name' => 'idpais',           'referencedColumnName' => 'idpais'],
                ],
            ],
            ]
        );
        $this->_generator->writeEntityClass($metadata, $this->_tmpDir);

        $filename = $this->_tmpDir . DIRECTORY_SEPARATOR
            . $this->_namespace . DIRECTORY_SEPARATOR . 'DDC2079Entity.php';

        self::assertFileExists($filename);
        require_once $filename;

        $property   = new \ReflectionProperty($metadata->name, 'centroCustos');
        $docComment = $property->getDocComment();

        //joinColumns
        self::assertContains('@JoinColumn(name="idorcamento", referencedColumnName="idorcamento"),', $docComment);
        self::assertContains('@JoinColumn(name="idunidade", referencedColumnName="idunidade")', $docComment);
        //inverseJoinColumns
        self::assertContains('@JoinColumn(name="idcentrocusto", referencedColumnName="idcentrocusto"),', $docComment);
        self::assertContains('@JoinColumn(name="idpais", referencedColumnName="idpais")', $docComment);

    }

     /**
     * @group DDC-2172
     */
    public function testGetInheritanceTypeString()
    {
        $reflection = new \ReflectionClass('\Doctrine\ORM\Mapping\ClassMetadata');
        $method     = new \ReflectionMethod($this->_generator, 'getInheritanceTypeString');
        $constants  = $reflection->getConstants();
        $pattern    = '/^INHERITANCE_TYPE_/';

        $method->setAccessible(true);

        foreach ($constants as $name => $value) {
            if( ! preg_match($pattern, $name)) {
                continue;
            }

            $expected = preg_replace($pattern, '', $name);
            $actual   = $method->invoke($this->_generator, $value);

            self::assertEquals($expected, $actual);
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid provided InheritanceType: INVALID');

        $method->invoke($this->_generator, 'INVALID');
    }

    /**
    * @group DDC-2172
    */
    public function testGetChangeTrackingPolicyString()
    {
        $reflection = new \ReflectionClass('\Doctrine\ORM\Mapping\ClassMetadata');
        $method     = new \ReflectionMethod($this->_generator, 'getChangeTrackingPolicyString');
        $constants  = $reflection->getConstants();
        $pattern    = '/^CHANGETRACKING_/';

        $method->setAccessible(true);

        foreach ($constants as $name => $value) {
            if( ! preg_match($pattern, $name)) {
                continue;
            }

            $expected = preg_replace($pattern, '', $name);
            $actual   = $method->invoke($this->_generator, $value);

            self::assertEquals($expected, $actual);
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid provided ChangeTrackingPolicy: INVALID');

        $method->invoke($this->_generator, 'INVALID');
    }

    /**
     * @group DDC-2172
     */
    public function testGetIdGeneratorTypeString()
    {
        $reflection = new \ReflectionClass('\Doctrine\ORM\Mapping\ClassMetadata');
        $method     = new \ReflectionMethod($this->_generator, 'getIdGeneratorTypeString');
        $constants  = $reflection->getConstants();
        $pattern    = '/^GENERATOR_TYPE_/';

        $method->setAccessible(true);

        foreach ($constants as $name => $value) {
            if( ! preg_match($pattern, $name)) {
                continue;
            }

            $expected = preg_replace($pattern, '', $name);
            $actual   = $method->invoke($this->_generator, $value);

            self::assertEquals($expected, $actual);
        }

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid provided IdGeneratorType: INVALID');

        $method->invoke($this->_generator, 'INVALID');
    }

    /**
     * @dataProvider getEntityTypeAliasDataProvider
     *
     * @group DDC-1694
     */
    public function testEntityTypeAlias(array $field)
    {
        $metadata   = $this->generateEntityTypeFixture($field);
        $path       = $this->_tmpDir . '/'. $this->_namespace . '/EntityType.php';

        self::assertFileExists($path);
        require_once $path;

        $entity     = new $metadata->name;
        $reflClass  = new \ReflectionClass($metadata->name);

        $type   = $field['phpType'];
        $name   = $field['fieldName'];
        $value  = $field['value'];
        $getter = "get" . ucfirst($name);
        $setter = "set" . ucfirst($name);

        self::assertPhpDocVarType($type, $reflClass->getProperty($name));
        self::assertPhpDocParamType($type, $reflClass->getMethod($setter));
        self::assertPhpDocReturnType($type, $reflClass->getMethod($getter));

        self::assertSame($entity, $entity->{$setter}($value));
        self::assertEquals($value, $entity->{$getter}());
    }

    /**
     * @group DDC-2372
     */
    public function testTraitPropertiesAndMethodsAreNotDuplicated()
    {
        $cmf = new ClassMetadataFactory();
        $em = $this->_getTestEntityManager();
        $cmf->setEntityManager($em);

        $user = new DDC2372User();
        $metadata = $cmf->getMetadataFor(get_class($user));
        $metadata->name = $this->_namespace . "\DDC2372User";
        $metadata->namespace = $this->_namespace;

        $this->_generator->writeEntityClass($metadata, $this->_tmpDir);

        self::assertFileExists($this->_tmpDir . "/" . $this->_namespace . "/DDC2372User.php");
        require $this->_tmpDir . "/" . $this->_namespace . "/DDC2372User.php";

        $reflClass = new \ReflectionClass($metadata->name);

        self::assertSame($reflClass->hasProperty('address'), false);
        self::assertSame($reflClass->hasMethod('setAddress'), false);
        self::assertSame($reflClass->hasMethod('getAddress'), false);
    }

    /**
     * @group DDC-2372
     */
    public function testTraitPropertiesAndMethodsAreNotDuplicatedInChildClasses()
    {
        $cmf = new ClassMetadataFactory();
        $em = $this->_getTestEntityManager();
        $cmf->setEntityManager($em);

        $user = new DDC2372Admin();
        $metadata = $cmf->getMetadataFor(get_class($user));
        $metadata->name = $this->_namespace . "\DDC2372Admin";
        $metadata->namespace = $this->_namespace;

        $this->_generator->writeEntityClass($metadata, $this->_tmpDir);

        self::assertFileExists($this->_tmpDir . "/" . $this->_namespace . "/DDC2372Admin.php");
        require $this->_tmpDir . "/" . $this->_namespace . "/DDC2372Admin.php";

        $reflClass = new \ReflectionClass($metadata->name);

        self::assertSame($reflClass->hasProperty('address'), false);
        self::assertSame($reflClass->hasMethod('setAddress'), false);
        self::assertSame($reflClass->hasMethod('getAddress'), false);
    }

    /**
     * @group DDC-1590
     */
    public function testMethodsAndPropertiesAreNotDuplicatedInChildClasses()
    {
        $cmf    = new ClassMetadataFactory();
        $em     = $this->_getTestEntityManager();

        $cmf->setEntityManager($em);

        $ns     = $this->_namespace;
        $nsdir  = $this->_tmpDir . '/' . $ns;

        $content = str_replace(
            'namespace Doctrine\Tests\Models\DDC1590',
            'namespace ' . $ns,
            file_get_contents(__DIR__ . '/../../Models/DDC1590/DDC1590User.php')
        );

        $fname = $nsdir . "/DDC1590User.php";
        file_put_contents($fname, $content);
        require $fname;


        $metadata = $cmf->getMetadataFor($ns . '\DDC1590User');
        $this->_generator->writeEntityClass($metadata, $this->_tmpDir);

        // class DDC1590User extends DDC1590Entity { ... }
        $source = file_get_contents($fname);

        // class _DDC1590User extends DDC1590Entity { ... }
        $source2    = str_replace('class DDC1590User', 'class _DDC1590User', $source);
        $fname2     = $nsdir . "/_DDC1590User.php";
        file_put_contents($fname2, $source2);
        require $fname2;

        // class __DDC1590User { ... }
        $source3    = str_replace('class DDC1590User extends DDC1590Entity', 'class __DDC1590User', $source);
        $fname3     = $nsdir . "/__DDC1590User.php";
        file_put_contents($fname3, $source3);
        require $fname3;


        // class _DDC1590User extends DDC1590Entity { ... }
        $rc2 = new \ReflectionClass($ns.'\_DDC1590User');

        self::assertTrue($rc2->hasProperty('name'));
        self::assertTrue($rc2->hasProperty('id'));
        self::assertTrue($rc2->hasProperty('created_at'));

        self::assertTrue($rc2->hasMethod('getName'));
        self::assertTrue($rc2->hasMethod('setName'));
        self::assertTrue($rc2->hasMethod('getId'));
        self::assertFalse($rc2->hasMethod('setId'));
        self::assertTrue($rc2->hasMethod('getCreatedAt'));
        self::assertTrue($rc2->hasMethod('setCreatedAt'));


        // class __DDC1590User { ... }
        $rc3 = new \ReflectionClass($ns.'\__DDC1590User');

        self::assertTrue($rc3->hasProperty('name'));
        self::assertFalse($rc3->hasProperty('id'));
        self::assertFalse($rc3->hasProperty('created_at'));

        self::assertTrue($rc3->hasMethod('getName'));
        self::assertTrue($rc3->hasMethod('setName'));
        self::assertFalse($rc3->hasMethod('getId'));
        self::assertFalse($rc3->hasMethod('setId'));
        self::assertFalse($rc3->hasMethod('getCreatedAt'));
        self::assertFalse($rc3->hasMethod('setCreatedAt'));
    }

    /**
     * @group DDC-3304
     */
    public function testGeneratedMutableEmbeddablesClass()
    {
        $embeddedMetadata = $this->generateTestEmbeddableFixture();
        $metadata = $this->generateIsbnEmbeddableFixture(['test' => $embeddedMetadata]);

        $isbn = $this->newInstance($metadata);

        self::assertTrue(class_exists($metadata->name), "Class does not exist.");
        self::assertFalse(method_exists($metadata->name, '__construct'), "EntityGeneratorIsbn::__construct present.");
        self::assertTrue(method_exists($metadata->name, 'getPrefix'), "EntityGeneratorIsbn::getPrefix() missing.");
        self::assertTrue(method_exists($metadata->name, 'setPrefix'), "EntityGeneratorIsbn::setPrefix() missing.");
        self::assertTrue(method_exists($metadata->name, 'getGroupNumber'), "EntityGeneratorIsbn::getGroupNumber() missing.");
        self::assertTrue(method_exists($metadata->name, 'setGroupNumber'), "EntityGeneratorIsbn::setGroupNumber() missing.");
        self::assertTrue(method_exists($metadata->name, 'getPublisherNumber'), "EntityGeneratorIsbn::getPublisherNumber() missing.");
        self::assertTrue(method_exists($metadata->name, 'setPublisherNumber'), "EntityGeneratorIsbn::setPublisherNumber() missing.");
        self::assertTrue(method_exists($metadata->name, 'getTitleNumber'), "EntityGeneratorIsbn::getTitleNumber() missing.");
        self::assertTrue(method_exists($metadata->name, 'setTitleNumber'), "EntityGeneratorIsbn::setTitleNumber() missing.");
        self::assertTrue(method_exists($metadata->name, 'getCheckDigit'), "EntityGeneratorIsbn::getCheckDigit() missing.");
        self::assertTrue(method_exists($metadata->name, 'setCheckDigit'), "EntityGeneratorIsbn::setCheckDigit() missing.");
        self::assertTrue(method_exists($metadata->name, 'getTest'), "EntityGeneratorIsbn::getTest() missing.");
        self::assertTrue(method_exists($metadata->name, 'setTest'), "EntityGeneratorIsbn::setTest() missing.");

        $isbn->setPrefix(978);
        self::assertSame(978, $isbn->getPrefix());

        $this->newInstance($embeddedMetadata);
        $test = new $embeddedMetadata->name();

        $isbn->setTest($test);
        self::assertSame($test, $isbn->getTest());

        $reflMethod = new \ReflectionMethod($metadata->name, 'setTest');
        $reflParameters = $reflMethod->getParameters();
        self::assertEquals($embeddedMetadata->name, $reflParameters[0]->getClass()->name);
    }

    /**
     * @group DDC-3304
     */
    public function testGeneratedImmutableEmbeddablesClass()
    {
        $this->_generator->setEmbeddablesImmutable(true);
        $embeddedMetadata = $this->generateTestEmbeddableFixture();
        $metadata = $this->generateIsbnEmbeddableFixture(['test' => $embeddedMetadata]);

        $this->loadEntityClass($embeddedMetadata);
        $this->loadEntityClass($metadata);

        self::assertTrue(class_exists($metadata->name), "Class does not exist.");
        self::assertTrue(method_exists($metadata->name, '__construct'), "EntityGeneratorIsbn::__construct missing.");
        self::assertTrue(method_exists($metadata->name, 'getPrefix'), "EntityGeneratorIsbn::getPrefix() missing.");
        self::assertFalse(method_exists($metadata->name, 'setPrefix'), "EntityGeneratorIsbn::setPrefix() present.");
        self::assertTrue(method_exists($metadata->name, 'getGroupNumber'), "EntityGeneratorIsbn::getGroupNumber() missing.");
        self::assertFalse(method_exists($metadata->name, 'setGroupNumber'), "EntityGeneratorIsbn::setGroupNumber() present.");
        self::assertTrue(method_exists($metadata->name, 'getPublisherNumber'), "EntityGeneratorIsbn::getPublisherNumber() missing.");
        self::assertFalse(method_exists($metadata->name, 'setPublisherNumber'), "EntityGeneratorIsbn::setPublisherNumber() present.");
        self::assertTrue(method_exists($metadata->name, 'getTitleNumber'), "EntityGeneratorIsbn::getTitleNumber() missing.");
        self::assertFalse(method_exists($metadata->name, 'setTitleNumber'), "EntityGeneratorIsbn::setTitleNumber() present.");
        self::assertTrue(method_exists($metadata->name, 'getCheckDigit'), "EntityGeneratorIsbn::getCheckDigit() missing.");
        self::assertFalse(method_exists($metadata->name, 'setCheckDigit'), "EntityGeneratorIsbn::setCheckDigit() present.");
        self::assertTrue(method_exists($metadata->name, 'getTest'), "EntityGeneratorIsbn::getTest() missing.");
        self::assertFalse(method_exists($metadata->name, 'setTest'), "EntityGeneratorIsbn::setTest() present.");

        $test = new $embeddedMetadata->name(1, new \DateTime());
        $isbn = new $metadata->name($test, 978, 3, 12, 732320, 83);

        $reflMethod = new \ReflectionMethod($isbn, '__construct');
        $reflParameters = $reflMethod->getParameters();

        self::assertCount(6, $reflParameters);

        self::assertSame($embeddedMetadata->name, $reflParameters[0]->getClass()->name);
        self::assertSame('test', $reflParameters[0]->getName());
        self::assertFalse($reflParameters[0]->isOptional());

        self::assertSame('prefix', $reflParameters[1]->getName());
        self::assertFalse($reflParameters[1]->isOptional());

        self::assertSame('groupNumber', $reflParameters[2]->getName());
        self::assertFalse($reflParameters[2]->isOptional());

        self::assertSame('publisherNumber', $reflParameters[3]->getName());
        self::assertFalse($reflParameters[3]->isOptional());

        self::assertSame('titleNumber', $reflParameters[4]->getName());
        self::assertFalse($reflParameters[4]->isOptional());

        self::assertSame('checkDigit', $reflParameters[5]->getName());
        self::assertFalse($reflParameters[5]->isOptional());

        $reflMethod = new \ReflectionMethod($test, '__construct');
        $reflParameters = $reflMethod->getParameters();

        self::assertCount(4, $reflParameters);

        self::assertSame('field1', $reflParameters[0]->getName());
        self::assertFalse($reflParameters[0]->isOptional());

        self::assertSame('DateTime', $reflParameters[1]->getClass()->name);
        self::assertSame('field3', $reflParameters[1]->getName());
        self::assertFalse($reflParameters[1]->isOptional());

        self::assertSame('field2', $reflParameters[2]->getName());
        self::assertTrue($reflParameters[2]->isOptional());

        self::assertSame('DateTime', $reflParameters[3]->getClass()->name);
        self::assertSame('field4', $reflParameters[3]->getName());
        self::assertTrue($reflParameters[3]->isOptional());
    }

    public function testRegenerateEntityClass()
    {
        $metadata = $this->generateBookEntityFixture();
        $this->loadEntityClass($metadata);

        $className = basename(str_replace('\\', '/', $metadata->name));
        $path = $this->_tmpDir . '/' . $this->_namespace . '/' . $className . '.php';
        $classTest = file_get_contents($path);

        $this->_generator->setRegenerateEntityIfExists(true);
        $this->_generator->setBackupExisting(false);

        $this->_generator->writeEntityClass($metadata, $this->_tmpDir);
        $classNew = file_get_contents($path);

        self::assertSame($classTest,$classNew);
    }

    /**
     * @return array
     */
    public function getEntityTypeAliasDataProvider()
    {
        return [
            [
                [
                'fieldName' => 'datetimetz',
                'phpType' => '\\DateTime',
                'dbType' => 'datetimetz',
                'value' => new \DateTime
                ]
            ],
            [
                [
                'fieldName' => 'datetime',
                'phpType' => '\\DateTime',
                'dbType' => 'datetime',
                'value' => new \DateTime
                ]
            ],
            [
                [
                'fieldName' => 'date',
                'phpType' => '\\DateTime',
                'dbType' => 'date',
                'value' => new \DateTime
                ]
            ],
            [
                [
                'fieldName' => 'time',
                'phpType' => '\DateTime',
                'dbType' => 'time',
                'value' => new \DateTime
                ]
            ],
            [
                [
                'fieldName' => 'object',
                'phpType' => '\stdClass',
                'dbType' => 'object',
                'value' => new \stdClass()
                ]
            ],
            [
                [
                'fieldName' => 'bigint',
                'phpType' => 'int',
                'dbType' => 'bigint',
                'value' => 11
                ]
            ],
            [
                [
                'fieldName' => 'smallint',
                'phpType' => 'int',
                'dbType' => 'smallint',
                'value' => 22
                ]
            ],
            [
                [
                'fieldName' => 'text',
                'phpType' => 'string',
                'dbType' => 'text',
                'value' => 'text'
                ]
            ],
            [
                [
                'fieldName' => 'blob',
                'phpType' => 'string',
                'dbType' => 'blob',
                'value' => 'blob'
                ]
            ],
            [
                [
                'fieldName' => 'decimal',
                'phpType' => 'string',
                'dbType' => 'decimal',
                'value' => '12.34'
                ],
            ]
        ];
    }

    public function getParseTokensInEntityFileData()
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

    /**
     * @param string $type
     * @param \ReflectionProperty $property
     */
    private function assertPhpDocVarType($type, \ReflectionProperty $property)
    {
        $docComment = $property->getDocComment();
        $regex      = '/@var\s+([\S]+)$/m';

        self::assertRegExp($regex, $docComment);
        self::assertEquals(1, preg_match($regex, $docComment, $matches));
        self::assertEquals($type, $matches[1]);
    }

    /**
     * @param string $type
     * @param \ReflectionMethod $method
     */
    private function assertPhpDocReturnType($type, \ReflectionMethod $method)
    {
        $docComment = $method->getDocComment();
        $regex      = '/@return\s+([\S]+)(\s+.*)$/m';

        self::assertRegExp($regex, $docComment);
        self::assertEquals(1, preg_match($regex, $docComment, $matches));
        self::assertEquals($type, $matches[1]);
    }

    /**
     * @param string $type
     * @param \ReflectionProperty $method
     */
    private function assertPhpDocParamType($type, \ReflectionMethod $method)
    {
        self::assertEquals(1, preg_match('/@param\s+([^\s]+)/', $method->getDocComment(), $matches));
        self::assertEquals($type, $matches[1]);
    }
}

class EntityGeneratorAuthor {}
class EntityGeneratorComment {}

<?php

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\Tools\SchemaTool,
    Doctrine\ORM\Tools\EntityGenerator,
    Doctrine\ORM\Tools\Export\ClassMetadataExporter,
    Doctrine\ORM\Mapping\ClassMetadataInfo;

require_once __DIR__ . '/../../TestInit.php';

class EntityGeneratorTest extends \Doctrine\Tests\OrmTestCase
{
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

    public function generateBookEntityFixture()
    {
        $metadata = new ClassMetadataInfo($this->_namespace . '\EntityGeneratorBook');
        $metadata->namespace = $this->_namespace;
        $metadata->customRepositoryClassName = $this->_namespace  . '\EntityGeneratorBookRepository';

        $metadata->table['name'] = 'book';
        $metadata->table['uniqueConstraints']['name_uniq'] = array('columns' => array('name'));
        $metadata->table['indexes']['status_idx'] = array('columns' => array('status'));
        $metadata->mapField(array('fieldName' => 'name', 'type' => 'string'));
        $metadata->mapField(array('fieldName' => 'status', 'type' => 'string', 'default' => 'published'));
        $metadata->mapField(array('fieldName' => 'id', 'type' => 'integer', 'id' => true));
        $metadata->mapOneToOne(array('fieldName' => 'author', 'targetEntity' => 'Doctrine\Tests\ORM\Tools\EntityGeneratorAuthor', 'mappedBy' => 'book'));
        $joinColumns = array(
            array('name' => 'author_id', 'referencedColumnName' => 'id')
        );
        $metadata->mapManyToMany(array(
            'fieldName' => 'comments',
            'targetEntity' => 'Doctrine\Tests\ORM\Tools\EntityGeneratorComment',
            'joinTable' => array(
                'name' => 'book_comment',
                'joinColumns' => array(array('name' => 'book_id', 'referencedColumnName' => 'id')),
                'inverseJoinColumns' => array(array('name' => 'comment_id', 'referencedColumnName' => 'id')),
            ),
        ));
        $metadata->addLifecycleCallback('loading', 'postLoad');
        $metadata->addLifecycleCallback('willBeRemoved', 'preRemove');
        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);

        $this->_generator->writeEntityClass($metadata, $this->_tmpDir);

        return $metadata;
    }

    /**
     * @param  ClassMetadataInfo $metadata
     * @return EntityGeneratorBook
     */
    public function newInstance($metadata)
    {
        $path = $this->_tmpDir . '/'. $this->_namespace . '/EntityGeneratorBook.php';
        $this->assertFileExists($path);
        require_once $path;

        return new $metadata->name;
    }

    public function testGeneratedEntityClass()
    {
        $metadata = $this->generateBookEntityFixture();

        $book = $this->newInstance($metadata);

        $this->assertTrue(class_exists($metadata->name), "Class does not exist.");
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', '__construct'), "EntityGeneratorBook::__construct() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'getId'), "EntityGeneratorBook::getId() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'setName'), "EntityGeneratorBook::setName() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'getName'), "EntityGeneratorBook::getName() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'setAuthor'), "EntityGeneratorBook::setAuthor() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'getAuthor'), "EntityGeneratorBook::getAuthor() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'getComments'), "EntityGeneratorBook::getComments() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'addComment'), "EntityGeneratorBook::addComment() missing.");
        $this->assertTrue(method_exists($metadata->namespace . '\EntityGeneratorBook', 'removeComment'), "EntityGeneratorBook::removeComment() missing.");

        $this->assertEquals('published', $book->getStatus());

        $book->setName('Jonathan H. Wage');
        $this->assertEquals('Jonathan H. Wage', $book->getName());

        $author = new EntityGeneratorAuthor();
        $book->setAuthor($author);
        $this->assertEquals($author, $book->getAuthor());

        $comment = new EntityGeneratorComment();
        $book->addComment($comment);
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $book->getComments());
        $this->assertEquals(new \Doctrine\Common\Collections\ArrayCollection(array($comment)), $book->getComments());
        $book->removeComment($comment);
        $this->assertEquals(new \Doctrine\Common\Collections\ArrayCollection(array()), $book->getComments());
    }

    public function testEntityUpdatingWorks()
    {
        $metadata = $this->generateBookEntityFixture();
        $metadata->mapField(array('fieldName' => 'test', 'type' => 'string'));

        $this->_generator->writeEntityClass($metadata, $this->_tmpDir);

        $this->assertFileExists($this->_tmpDir . "/" . $this->_namespace . "/EntityGeneratorBook.php~");

        $book = $this->newInstance($metadata);
        $reflClass = new \ReflectionClass($metadata->name);

        $this->assertTrue($reflClass->hasProperty('name'), "Regenerating keeps property 'name'.");
        $this->assertTrue($reflClass->hasProperty('status'), "Regenerating keeps property 'status'.");
        $this->assertTrue($reflClass->hasProperty('id'), "Regenerating keeps property 'id'.");

        $this->assertTrue($reflClass->hasProperty('test'), "Check for property test failed.");
        $this->assertTrue($reflClass->getProperty('test')->isPrivate(), "Check for private property test failed.");
        $this->assertTrue($reflClass->hasMethod('getTest'), "Check for method 'getTest' failed.");
        $this->assertTrue($reflClass->getMethod('getTest')->isPublic(), "Check for public visibility of method 'getTest' failed.");
        $this->assertTrue($reflClass->hasMethod('setTest'), "Check for method 'getTest' failed.");
        $this->assertTrue($reflClass->getMethod('getTest')->isPublic(), "Check for public visibility of method 'getTest' failed.");
    }

    public function testEntityExtendsStdClass()
    {
        $this->_generator->setClassToExtend('stdClass');
        $metadata = $this->generateBookEntityFixture();

        $book = $this->newInstance($metadata);
        $this->assertInstanceOf('stdClass', $book);
    }

    public function testLifecycleCallbacks()
    {
        $metadata = $this->generateBookEntityFixture();

        $book = $this->newInstance($metadata);
        $reflClass = new \ReflectionClass($metadata->name);

        $this->assertTrue($reflClass->hasMethod('loading'), "Check for postLoad lifecycle callback.");
        $this->assertTrue($reflClass->hasMethod('willBeRemoved'), "Check for preRemove lifecycle callback.");
    }

    public function testLoadMetadata()
    {
        $metadata = $this->generateBookEntityFixture();

        $book = $this->newInstance($metadata);

        $cm = new \Doctrine\ORM\Mapping\ClassMetadata($metadata->name);
        $cm->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);

        $driver = $this->createAnnotationDriver();
        $driver->loadMetadataForClass($cm->name, $cm);

        $this->assertEquals($cm->columnNames, $metadata->columnNames);
        $this->assertEquals($cm->getTableName(), $metadata->getTableName());
        $this->assertEquals($cm->lifecycleCallbacks, $metadata->lifecycleCallbacks);
        $this->assertEquals($cm->identifier, $metadata->identifier);
        $this->assertEquals($cm->idGenerator, $metadata->idGenerator);
        $this->assertEquals($cm->customRepositoryClassName, $metadata->customRepositoryClassName);
    }

    public function testLoadPrefixedMetadata()
    {
        $this->_generator->setAnnotationPrefix('ORM\\');
        $metadata = $this->generateBookEntityFixture();

        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $driver = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, array());

        $book = $this->newInstance($metadata);

        $cm = new \Doctrine\ORM\Mapping\ClassMetadata($metadata->name);
        $cm->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);

        $driver->loadMetadataForClass($cm->name, $cm);

        $this->assertEquals($cm->columnNames, $metadata->columnNames);
        $this->assertEquals($cm->getTableName(), $metadata->getTableName());
        $this->assertEquals($cm->lifecycleCallbacks, $metadata->lifecycleCallbacks);
        $this->assertEquals($cm->identifier, $metadata->identifier);
        $this->assertEquals($cm->idGenerator, $metadata->idGenerator);
        $this->assertEquals($cm->customRepositoryClassName, $metadata->customRepositoryClassName);
    }

    /**
     * @dataProvider getParseTokensInEntityFileData
     */
    public function testParseTokensInEntityFile($php, $classes)
    {
        $r = new \ReflectionObject($this->_generator);
        $m = $r->getMethod('_parseTokensInEntityFile');
        $m->setAccessible(true);

        $p = $r->getProperty('_staticReflection');
        $p->setAccessible(true);

        $ret = $m->invoke($this->_generator, $php);
        $this->assertEquals($classes, array_keys($p->getValue($this->_generator)));
    }

    /**
     * @group DDC-1784
     */
    public function testGenerateEntityWithSequenceGenerator()
    {
        $metadata               = new ClassMetadataInfo($this->_namespace . '\DDC1784Entity');
        $metadata->namespace    = $this->_namespace;
        $metadata->mapField(array('fieldName' => 'id', 'type' => 'integer', 'id' => true));
        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_SEQUENCE);
        $metadata->setSequenceGeneratorDefinition(array(
            'sequenceName'      => 'DDC1784_ID_SEQ',
            'allocationSize'    => 1,
            'initialValue'      => 2
        ));
        $this->_generator->writeEntityClass($metadata, $this->_tmpDir);

        $filename = $this->_tmpDir . DIRECTORY_SEPARATOR
                  . $this->_namespace . DIRECTORY_SEPARATOR . 'DDC1784Entity.php';
        
        $this->assertFileExists($filename);
        require_once $filename;


        $reflection = new \ReflectionProperty($metadata->name, 'id');
        $docComment = $reflection->getDocComment();

        $this->assertContains('@Id', $docComment);
        $this->assertContains('@Column(name="id", type="integer")', $docComment);
        $this->assertContains('@GeneratedValue(strategy="SEQUENCE")', $docComment);
        $this->assertContains('@SequenceGenerator(sequenceName="DDC1784_ID_SEQ", allocationSize=1, initialValue=2)', $docComment);
    }


    public function getParseTokensInEntityFileData()
    {
        return array(
            array(
                '<?php namespace Foo\Bar; class Baz {}',
                array('Foo\Bar\Baz'),
            ),
            array(
                '<?php namespace Foo\Bar; use Foo; class Baz {}',
                array('Foo\Bar\Baz'),
            ),
            array(
                '<?php namespace /*Comment*/ Foo\Bar; /** Foo */class /* Comment */ Baz {}',
                array('Foo\Bar\Baz'),
            ),
            array(
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
                array('Foo\Bar\Baz'),
            ),
        );
    }
}

class EntityGeneratorAuthor {}
class EntityGeneratorComment {}

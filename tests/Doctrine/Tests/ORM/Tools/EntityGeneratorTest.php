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

    public function setUp()
    {
        $this->_generator = new EntityGenerator();
        $this->_generator->setGenerateAnnotations(true);
        $this->_generator->setGenerateStubMethods(true);
        $this->_generator->setRegenerateEntityIfExists(false);
        $this->_generator->setUpdateEntityIfExists(true);
    }

    public function testWriteEntityClass()
    {
        $metadata = new ClassMetadataInfo('EntityGeneratorBook');
        $metadata->table['name'] = 'book';
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
        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);

        $this->_generator->writeEntityClass($metadata, __DIR__);

        $path = __DIR__ . '/EntityGeneratorBook.php';
        $this->assertTrue(file_exists($path));
        require_once $path;

        return $metadata;
    }

    /**
     * @depends testWriteEntityClass
     * @param ClassMetadata $metadata
     */
    public function testGeneratedEntityClass($metadata)
    {
        $this->assertTrue(method_exists('\EntityGeneratorBook', 'getId'));
        $this->assertTrue(method_exists('\EntityGeneratorBook', 'setName'));
        $this->assertTrue(method_exists('\EntityGeneratorBook', 'getName'));
        $this->assertTrue(method_exists('\EntityGeneratorBook', 'setAuthor'));
        $this->assertTrue(method_exists('\EntityGeneratorBook', 'getAuthor'));
        $this->assertTrue(method_exists('\EntityGeneratorBook', 'getComments'));
        $this->assertTrue(method_exists('\EntityGeneratorBook', 'addComments'));

        $book = new \EntityGeneratorBook();
        $this->assertEquals('published', $book->getStatus());

        $book->setName('Jonathan H. Wage');
        $this->assertEquals('Jonathan H. Wage', $book->getName());

        $author = new EntityGeneratorAuthor();
        $book->setAuthor($author);
        $this->assertEquals($author, $book->getAuthor());

        $comment = new EntityGeneratorComment();
        $book->addComments($comment);
        $this->assertEquals(array($comment), $book->getComments());

        return $metadata;
    }

    /**
     * @depends testGeneratedEntityClass
     * @param ClassMetadata $metadata
     */
    public function testEntityUpdatingWorks($metadata)
    {
        $metadata->mapField(array('fieldName' => 'test', 'type' => 'string'));
        $this->_generator->writeEntityClass($metadata, __DIR__);

        $code = file_get_contents(__DIR__ . '/EntityGeneratorBook.php');

        $this->assertTrue(strstr($code, 'private $test;') !== false);
        $this->assertTrue(strstr($code, 'private $test;') !== false);
        $this->assertTrue(strstr($code, 'public function getTest(') !== false);
        $this->assertTrue(strstr($code, 'public function setTest(') !== false);

        unlink(__DIR__ . '/EntityGeneratorBook.php');
    }
}

class EntityGeneratorAuthor {}
class EntityGeneratorComment {}
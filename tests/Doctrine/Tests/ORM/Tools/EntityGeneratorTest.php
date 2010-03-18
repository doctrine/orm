<?php

namespace Doctrine\Tests\ORM\Tools;

use Doctrine\ORM\Tools\SchemaTool,
    Doctrine\ORM\Tools\EntityGenerator,
    Doctrine\ORM\Tools\Export\ClassMetadataExporter,
    Doctrine\ORM\Mapping\ClassMetadataInfo;

require_once __DIR__ . '/../../TestInit.php';

class EntityGeneratorTest extends \Doctrine\Tests\OrmTestCase
{
    public function testWriteEntityClass()
    {
        $metadata = new ClassMetadataInfo('EntityGeneratorBook');
        $metadata->primaryTable['name'] = 'book';
        $metadata->mapField(array('fieldName' => 'name', 'type' => 'varchar'));
        $metadata->mapField(array('fieldName' => 'id', 'type' => 'integer', 'id' => true));
        $metadata->mapOneToOne(array('fieldName' => 'other', 'targetEntity' => 'Other', 'mappedBy' => 'this'));
        $joinColumns = array(
            array('name' => 'other_id', 'referencedColumnName' => 'id')
        );
        $metadata->mapOneToOne(array('fieldName' => 'association', 'targetEntity' => 'Other', 'joinColumns' => $joinColumns));
        $metadata->mapManyToMany(array(
            'fieldName' => 'author',
            'targetEntity' => 'Author',
            'joinTable' => array(
                'name' => 'book_author',
                'joinColumns' => array(array('name' => 'bar_id', 'referencedColumnName' => 'id')),
                'inverseJoinColumns' => array(array('name' => 'baz_id', 'referencedColumnName' => 'id')),
            ),
        ));
        $metadata->setIdGeneratorType(ClassMetadataInfo::GENERATOR_TYPE_AUTO);

        $generator = new EntityGenerator();
        $generator->setGenerateAnnotations(true);
        $generator->setGenerateStubMethods(true);
        $generator->setRegenerateEntityIfExists(false);
        $generator->setUpdateEntityIfExists(true);
        $generator->writeEntityClass($metadata, __DIR__);

        $path = __DIR__ . '/EntityGeneratorBook.php';
        $this->assertTrue(file_exists($path));
        require_once $path;
    }

    /**
     * @depends testWriteEntityClass
     */
    public function testGeneratedEntityClassMethods()
    {
        $this->assertTrue(method_exists('\EntityGeneratorBook', 'getId'));
        $this->assertTrue(method_exists('\EntityGeneratorBook', 'setName'));
        $this->assertTrue(method_exists('\EntityGeneratorBook', 'getName'));
        $this->assertTrue(method_exists('\EntityGeneratorBook', 'setOther'));
        $this->assertTrue(method_exists('\EntityGeneratorBook', 'getOther'));
        $this->assertTrue(method_exists('\EntityGeneratorBook', 'setAssociation'));
        $this->assertTrue(method_exists('\EntityGeneratorBook', 'getAssociation'));
        $this->assertTrue(method_exists('\EntityGeneratorBook', 'getAuthor'));
        $this->assertTrue(method_exists('\EntityGeneratorBook', 'addAuthor'));

        $book = new \EntityGeneratorBook();

        $book->setName('Jonathan H. Wage');
        $this->assertEquals('Jonathan H. Wage', $book->getName());

        $book->setOther('Other');
        $this->assertEquals('Other', $book->getOther());

        $book->setAssociation('Test');
        $this->assertEquals('Test', $book->getAssociation());

        $book->addAuthor('Test');
        $this->assertEquals(array('Test'), $book->getAuthor());

        unlink(__DIR__ . '/EntityGeneratorBook.php');
    }
}
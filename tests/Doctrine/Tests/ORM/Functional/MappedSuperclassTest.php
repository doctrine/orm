<?php

namespace Doctrine\Tests\ORM\Functional;

require_once __DIR__ . '/../../TestInit.php';

/**
 * MappedSuperclassTest
 *
 * @author robo
 */
class MappedSuperclassTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('directorytree');
        parent::setUp();
    }

    public function testCRUD()
    {
        $root = new \Doctrine\Tests\Models\DirectoryTree\Directory();
        $root->setName('Root');
        $root->setPath('/root');

        $directory = new \Doctrine\Tests\Models\DirectoryTree\Directory($root);
        $directory->setName('TestA');
        $directory->setPath('/root/dir');

        $file = new \Doctrine\Tests\Models\DirectoryTree\File($directory);
        $file->setName('test-b.html');

        $this->_em->persist($root);
        $this->_em->persist($directory);
        $this->_em->persist($file);

        $this->_em->flush();
        $this->_em->clear();

        $cleanFile = $this->_em->find(get_class($file), $file->getId());

        $this->assertInstanceOf('Doctrine\Tests\Models\DirectoryTree\Directory', $cleanFile->getParent());
        $this->assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $cleanFile->getParent());
        $this->assertEquals($directory->getId(), $cleanFile->getParent()->getId());
        $this->assertInstanceOf('Doctrine\Tests\Models\DirectoryTree\Directory', $cleanFile->getParent()->getParent());
        $this->assertEquals($root->getId(), $cleanFile->getParent()->getParent()->getId());
    }
}

<?php

namespace Shitty\Tests\ORM\Functional;

/**
 * MappedSuperclassTest
 *
 * @author robo
 */
class MappedSuperclassTest extends \Shitty\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        $this->useModelSet('directorytree');
        parent::setUp();
    }

    public function testCRUD()
    {
        $root = new \Shitty\Tests\Models\DirectoryTree\Directory();
        $root->setName('Root');
        $root->setPath('/root');

        $directory = new \Shitty\Tests\Models\DirectoryTree\Directory($root);
        $directory->setName('TestA');
        $directory->setPath('/root/dir');

        $file = new \Shitty\Tests\Models\DirectoryTree\File($directory);
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

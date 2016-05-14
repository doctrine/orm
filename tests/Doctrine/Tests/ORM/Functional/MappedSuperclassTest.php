<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\DirectoryTree\Directory;
use Doctrine\Tests\Models\DirectoryTree\File;
use Doctrine\Tests\OrmFunctionalTestCase;
use Doctrine\Tests\Models\DirectoryTree;

/**
 * MappedSuperclassTest
 *
 * @author robo
 */
class MappedSuperclassTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        $this->useModelSet('directorytree');

        parent::setUp();
    }

    public function testCRUD()
    {
        $root = new Directory();
        $root->setName('Root');
        $root->setPath('/root');

        $directory = new Directory($root);
        $directory->setName('TestA');
        $directory->setPath('/root/dir');

        $file = new File($directory);
        $file->setName('test-b.html');

        $this->_em->persist($root);
        $this->_em->persist($directory);
        $this->_em->persist($file);

        $this->_em->flush();
        $this->_em->clear();

        $cleanFile = $this->_em->find(DirectoryTree\File::class, $file->getId());

        self::assertInstanceOf(DirectoryTree\Directory::class, $cleanFile->getParent());
        self::assertInstanceOf('Doctrine\ORM\Proxy\Proxy', $cleanFile->getParent());
        self::assertEquals($directory->getId(), $cleanFile->getParent()->getId());
        self::assertInstanceOf(DirectoryTree\Directory::class, $cleanFile->getParent()->getParent());
        self::assertEquals($root->getId(), $cleanFile->getParent()->getParent()->getId());
    }
}

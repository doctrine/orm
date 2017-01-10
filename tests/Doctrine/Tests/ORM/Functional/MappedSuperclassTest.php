<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Tests\Models\DirectoryTree\Directory;
use Doctrine\Tests\Models\DirectoryTree\File;
use Doctrine\Tests\OrmFunctionalTestCase;

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

        $this->em->persist($root);
        $this->em->persist($directory);
        $this->em->persist($file);

        $this->em->flush();
        $this->em->clear();

        $cleanFile = $this->em->find(File::class, $file->getId());

        self::assertInstanceOf(Directory::class, $cleanFile->getParent());
        self::assertInstanceOf(Proxy::class, $cleanFile->getParent());
        self::assertEquals($directory->getId(), $cleanFile->getParent()->getId());
        self::assertInstanceOf(Directory::class, $cleanFile->getParent()->getParent());
        self::assertEquals($root->getId(), $cleanFile->getParent()->getParent()->getId());
    }
}

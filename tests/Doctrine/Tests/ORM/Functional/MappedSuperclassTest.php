<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\DirectoryTree\Directory;
use Doctrine\Tests\Models\DirectoryTree\File;
use Doctrine\Tests\OrmFunctionalTestCase;
use ProxyManager\Proxy\GhostObjectInterface;

/**
 * MappedSuperclassTest
 */
class MappedSuperclassTest extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        $this->useModelSet('directorytree');

        parent::setUp();
    }

    public function testCRUD() : void
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
        self::assertInstanceOf(GhostObjectInterface::class, $cleanFile->getParent());
        self::assertEquals($directory->getId(), $cleanFile->getParent()->getId());
        self::assertInstanceOf(Directory::class, $cleanFile->getParent()->getParent());
        self::assertEquals($root->getId(), $cleanFile->getParent()->getParent()->getId());
    }
}

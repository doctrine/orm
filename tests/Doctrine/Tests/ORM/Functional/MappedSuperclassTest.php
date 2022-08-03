<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Tests\Models\DirectoryTree\Directory;
use Doctrine\Tests\Models\DirectoryTree\File;
use Doctrine\Tests\OrmFunctionalTestCase;

use function get_class;

/**
 * MappedSuperclassTest
 */
class MappedSuperclassTest extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->useModelSet('directorytree');
        parent::setUp();
    }

    public function testCRUD(): void
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

        $cleanFile = $this->_em->find(get_class($file), $file->getId());

        $this->assertInstanceOf(Directory::class, $cleanFile->getParent());
        $this->assertInstanceOf(Proxy::class, $cleanFile->getParent());
        $this->assertEquals($directory->getId(), $cleanFile->getParent()->getId());
        $this->assertInstanceOf(Directory::class, $cleanFile->getParent()->getParent());
        $this->assertEquals($root->getId(), $cleanFile->getParent()->getParent()->getId());
    }
}

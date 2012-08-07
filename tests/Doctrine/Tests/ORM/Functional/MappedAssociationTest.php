<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\MappedAssociation\PrimaryIsForeign\FileFolder;
use Doctrine\Tests\Models\MappedAssociation\PrimaryIsForeign\Paper;
use Doctrine\Tests\Models\MappedAssociation\PrimaryIsForeign\Photo;
use Doctrine\Tests\Models\MappedAssociation\DiscretePrimary\Shelf;
use Doctrine\Tests\Models\MappedAssociation\DiscretePrimary\Book;
use Doctrine\Tests\Models\MappedAssociation\DiscretePrimary\Video;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Mapped association tests
 *
 * @group Doctrine.MappedAssociation
 */
class MappedAssociationTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    const FILEFOLDER         = 'Doctrine\Tests\Models\MappedAssociation\PrimaryIsForeign\FileFolder';
    const PAPER              = 'Doctrine\Tests\Models\MappedAssociation\PrimaryIsForeign\Paper';
    const PHOTO              = 'Doctrine\Tests\Models\MappedAssociation\PrimaryIsForeign\Photo';
    const SHELF              = 'Doctrine\Tests\Models\MappedAssociation\DiscretePrimary\Shelf';
    const BOOK               = 'Doctrine\Tests\Models\MappedAssociation\DiscretePrimary\Book';
    const VIDEO              = 'Doctrine\Tests\Models\MappedAssociation\DiscretePrimary\Video';

    protected function setUp()
    {
        $this->useModelSet('mappedassociation');
        parent::setUp();
    }

    /**
     * Test mapped association with mapped entity having primary key as a foreign key.
     */
    public function testSimplePrimaryIsForeignMappedAssociation()
    {
        /**
         * Create file folder 0
         */
        $fileFolder0 = new FileFolder();
        $fileFolder0->setTitle('Folder 0');
        $this->_em->persist($fileFolder0);
        $this->_em->flush();
        $id0 = $fileFolder0->getId();

        /**
         * Create file folder 1
         */
        $fileFolder1 = new FileFolder();
        $fileFolder1->setTitle('Folder 1');
        $this->_em->persist($fileFolder1);
        $this->_em->flush();
        $id1 = $fileFolder1->getId();

        $content1 = new Paper;
        $content1->setDescription('Expense report');
        $fileFolder1->setContent($content1);
        $this->_em->flush();

        /**
         * Create file folder 2
         */
        $fileFolder2 = new FileFolder();
        $fileFolder2->setTitle('Folder 2');
        $this->_em->persist($fileFolder2);
        $this->_em->flush();
        $id2 = $fileFolder2->getId();

        $content2 = new Photo;
        $content2->setDescription('Family photo');
        $fileFolder2->setContent($content2);
        $this->_em->flush();

        /**
         * Clear entity manager for tests
         */
        $this->_em->clear();

        /**
         * Check descriminator column was set properly
         */
        $fileFolderRepository = $this->_em->getRepository($this::FILEFOLDER);
        $query = $fileFolderRepository->createNativeNamedQuery('get-class-by-id');

        $results = $query->setParameter(1, $id0)
            ->getResult();
        $this->assertEquals($results[0]['contentclass'], null);

        $results = $query->setParameter(1, $id1)
            ->getResult();
        $this->assertEquals($results[0]['contentclass'], get_class($content1));

        $results = $query->setParameter(1, $id2)
            ->getResult();
        $this->assertEquals($results[0]['contentclass'], get_class($content2));

        /**
         * Check can get container from mapped association
         */
        $paperRepository = $this->_em->getRepository($this::PAPER);
        $queryPaper1 = $paperRepository->find($id1);
        $this->assertEquals($content1->getFileFolder()->getTitle(), $queryPaper1->getFileFolder()->getTitle());

        $photoRepository = $this->_em->getRepository($this::PHOTO);
        $queryPhoto2 = $photoRepository->find($id2);
        $this->assertEquals($content2->getFileFolder()->getTitle(), $queryPhoto2->getFileFolder()->getTitle());

        /**
         * Clear the EM so we're not comparing proxies (or set join eager)
         */
        $this->_em->clear();

        /**
         * Check container entity retrieval
         */
        $queryFileFolder0 = $fileFolderRepository->find($id0);
        $this->assertEquals($fileFolder0, $queryFileFolder0);

        $queryFileFolder1 = $fileFolderRepository->find($id1);
        $this->assertEquals($fileFolder1, $queryFileFolder1);

        $queryFileFolder2 = $fileFolderRepository->find($id2);
        $this->assertEquals($fileFolder2, $queryFileFolder2);

        /**
         * Remove container entities and clear EM
         */
        $this->_em->remove($queryFileFolder0);
        $this->_em->remove($queryFileFolder1);
        $this->_em->remove($queryFileFolder2);
        $this->_em->flush();
        $this->_em->clear();

        /**
         * Check containers and mapped associations removed
         */
        $queryFileFolder0 = $fileFolderRepository->find($id0);
        $this->assertEquals(null, $queryFileFolder0);

        $queryFileFolder1 = $fileFolderRepository->find($id1);
        $this->assertEquals(null, $queryFileFolder1);

        $queryFileFolder2 = $fileFolderRepository->find($id2);
        $this->assertEquals(null, $queryFileFolder2);

        $fileFolderRepository = $this->_em->getRepository($this::PAPER);
        $results = $fileFolderRepository->findAll();
        $this->assertEmpty($results);

        $fileFolderRepository = $this->_em->getRepository($this::PHOTO);
        $results = $fileFolderRepository->findAll();
        $this->assertEmpty($results);
    }

    /**
     * Test mapped association with mapped entity have its own identifier and container owning side.
     */
    public function testSimpleDiscretePrimaryMappedAssociation()
    {
        /**
         * Create shelf 0
         */
        $shelf0 = new Shelf();
        $shelf0->setBookcase('Basement');
        $this->_em->persist($shelf0);
        $this->_em->flush();
        $id0 = $shelf0->getId();

        /**
         * Create shelf 1
         */
        $shelf1 = new Shelf();
        $shelf1->setBookcase('Bedroom');

        $object1 = new Book;
        $object1->setDescription('To Kill a Mockingbird');
        $shelf1->setObject($object1);
        $this->_em->persist($shelf1);
        $this->_em->flush();
        $id1 = $shelf1->getId();
        $ido1 = $object1->getId();

        /**
         * Create shelf 2
         */
        $shelf2 = new Shelf();
        $shelf2->setBookcase('Theater');

        $object2 = new Video;
        $object2->setDescription('Die Hard');
        $shelf2->setObject($object2);
        $this->_em->persist($shelf2);
        $this->_em->flush();
        $id2 = $shelf2->getId();
        $ido2 = $object2->getId();

        /**
         * Clear entity manager for tests
         */
        $this->_em->clear();

        /**
         * Check descriminator column was set properly
         */
        $repository = $this->_em->getRepository($this::SHELF);
        $query = $repository->createNativeNamedQuery('get-class-by-id');

        $query = $query->setParameter(1, $id0);
        $results = $query->getResult();
        $this->assertEquals($results[0]['objectclass'], null);

        $query = $query->setParameter(1, $id1);
        $results = $query->getResult();
        $this->assertEquals($results[0]['objectclass'], get_class($object1));

        $results = $query->setParameter(1, $id2)
            ->getResult();
        $this->assertEquals($results[0]['objectclass'], get_class($object2));

        /**
         * Check can get container from mapped association
         */
        $bookRepository = $this->_em->getRepository($this::BOOK);
        $queryBook1 = $bookRepository->find($ido1);
        $this->assertEquals($object1->getShelf()->getBookcase(), $queryBook1->getShelf()->getBookcase());

        $videoRepository = $this->_em->getRepository($this::VIDEO);
        $queryVideo2 = $videoRepository->find($ido2);
        $this->assertEquals($object2->getShelf()->getBookcase(), $queryVideo2->getShelf()->getBookcase());

        /**
         * Check container entity retrieval
         */
        $queryShelf0 = $repository->find($id0);
        $this->assertEquals($shelf0, $queryShelf0);

        $queryShelf1 = $repository->find($id1);
        $this->assertEquals($shelf1, $queryShelf1);

        $queryShelf2 = $repository->find($id2);
        $this->assertEquals($shelf2, $queryShelf2);

        /**
         * Remove container entities and clear EM
         */
        $this->_em->remove($queryShelf0);
        $this->_em->remove($queryShelf1);
        $this->_em->remove($queryShelf2);
        $this->_em->flush();
        $this->_em->clear();

        /**
         * Check containers and mapped associations removed
         */
        $queryShelf0 = $repository->find($id0);
        $this->assertEquals(null, $queryShelf0);

        $queryShelf1 = $repository->find($id1);
        $this->assertEquals(null, $queryShelf1);

        $queryShelf2 = $repository->find($id2);
        $this->assertEquals(null, $queryShelf2);

        $repository = $this->_em->getRepository($this::BOOK);
        $results = $repository->findAll();
        $this->assertEmpty($results);

        $repository = $this->_em->getRepository($this::VIDEO);
        $results = $repository->findAll();
        $this->assertEmpty($results);
    }
}

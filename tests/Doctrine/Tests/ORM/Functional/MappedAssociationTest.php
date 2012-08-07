<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Tests\Models\MappedAssociation\FileFolder;
use Doctrine\Tests\Models\MappedAssociation\Paper;
use Doctrine\Tests\Models\MappedAssociation\Photo;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Mapped association tests
 *
 * @group Doctrine.MappedAssociation
 */
class MappedAssociationTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    const FILEFOLDER         = 'Doctrine\Tests\Models\MappedAssociation\FileFolder';
    const PAPER              = 'Doctrine\Tests\Models\MappedAssociation\Paper';
    const PHOTO              = 'Doctrine\Tests\Models\MappedAssociation\Photo';

    protected function setUp()
    {
        $this->useModelSet('mappedassociation');
        parent::setUp();
    }

    /**
     * Test mapped association with mapped entity having primary key as a foreign key.
     */
    public function testMappedAssociation()
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
         * Check discriminator column was set properly
         */
        $fileFolderRepository = $this->_em->getRepository($this::FILEFOLDER);
        $query = $fileFolderRepository->createNativeNamedQuery('get-class-by-id');

        $results = $query->setParameter(1, $id0)
            ->getResult();
        $this->assertEquals($results[0]['content_class'], null);

        $results = $query->setParameter(1, $id1)
            ->getResult();
        $this->assertEquals($results[0]['content_class'], get_class($content1));

        $results = $query->setParameter(1, $id2)
            ->getResult();
        $this->assertEquals($results[0]['content_class'], get_class($content2));

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
}

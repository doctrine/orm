<?php

namespace Doctrine\Tests\ORM\Functional;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Cascade dependant identity test
 *
 * @group Doctrine.CascadeDependantIdentity
 */
class CascadeDependantIdentityTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    const FILEFOLDER  = 'Doctrine\Tests\ORM\Functional\FileFolder';
    const PAPER       = 'Doctrine\Tests\ORM\Functional\Paper';
    const FILEFOLDER2 = 'Doctrine\Tests\ORM\Functional\FileFolder2';
    const PAPER2      = 'Doctrine\Tests\ORM\Functional\Paper2';

    private static $_created = false;

    protected function setUp() {
        parent::setUp();
        if (!static::$_created) {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\FileFolder'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Paper'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\FileFolder2'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\Paper2'),
            ));
            static::$_created = true;
        }
    }

    protected function tearDown()
    {
        $conn = static::$_sharedConn;

        $this->_sqlLoggerStack->enabled = false;

        $conn->executeUpdate('DELETE FROM cdi_paper');
        $conn->executeUpdate('DELETE FROM cdi_filefolder');
        $conn->executeUpdate('DELETE FROM cdi_paper2');
        $conn->executeUpdate('DELETE FROM cdi_filefolder2');

        $this->_em->clear();
    }

    public function testCascadeDependantIdentityGenerated()
    {
        $fileFolder1 = new FileFolder();
        $fileFolder1->setTitle('Folder 1');
        $this->_em->persist($fileFolder1);
        $this->_em->flush();
        $id1 = $fileFolder1->getId();

        $paper1 = new Paper;
        $paper1->setDescription('Expense report');

        $fileFolder1->setPaper($paper1);

        $this->_em->flush();
        $this->_em->clear();

        $paperRepository = $this->_em->getRepository(self::PAPER);
        $queryPaper1 = $paperRepository->find($id1);
        $this->assertEquals($paper1->getFileFolder()->getTitle(), $queryPaper1->getFileFolder()->getTitle());

        $this->_em->clear();

        $fileFolderRepository = $this->_em->getRepository(self::FILEFOLDER);
        $queryFileFolder1 = $fileFolderRepository->find($id1);
        $this->assertEquals($fileFolder1, $queryFileFolder1);
    }

    public function testCascadeDependantIdentityAssigned()
    {
        $fileFolder1 = new FileFolder2();
        $fileFolder1->setId(2);
        $fileFolder1->setTitle('Folder 1');

        $id1 = $fileFolder1->getId();

        $paper1 = new Paper2;
        $paper1->setDescription('Expense report');

        $fileFolder1->setPaper($paper1);

        $this->_em->persist($fileFolder1);
        $this->_em->flush();
        $this->_em->clear();

        $paperRepository = $this->_em->getRepository(self::PAPER2);
        $queryPaper1 = $paperRepository->find($id1);
        $this->assertEquals($paper1->getFileFolder()->getTitle(), $queryPaper1->getFileFolder()->getTitle());

        $this->_em->clear();

        $fileFolderRepository = $this->_em->getRepository(self::FILEFOLDER2);
        $queryFileFolder1 = $fileFolderRepository->find($id1);
        $this->assertEquals($fileFolder1, $queryFileFolder1);
    }

}

/**
 * @Entity
 * @Table(name="cdi_filefolder")
 */
class FileFolder
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     *
     * @var int $id
     */
    private $id;

    /**
     * @Column(type="string", length=128)
     *
     * @var string $title
     */
    private $title;

    /**
     * @OneToOne(targetEntity="Paper", mappedBy="fileFolder", cascade={"all"}, orphanRemoval=true)
     *
     * @var Paper $paper
     */
    private $paper;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param Paper $paper
     */
    public function setPaper(Paper $paper)
    {
        $this->paper = $paper;
    }

    /**
     * @return Paper
     */
    public function getPaper()
    {
        return $this->paper;
    }
}

/**
 * @Entity
 * @Table(name="cdi_paper")
 */
class Paper
{
    /**
     * @Id
     * @OneToOne(targetEntity="FileFolder", inversedBy="paper")
     *
     * @var FileFolder $fileFolder
     */
    private $fileFolder;

    /**
     * @Column(type="string", length=128)
     *
     * @var string $description
     */
    private $description;

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return FileFolder
     */
    public function getFileFolder()
    {
        return $this->fileFolder;
    }

    /**
     * @param FileFolder $fileFolder
     */
    public function setFileFolder(FileFolder $fileFolder)
    {
        $this->fileFolder = $fileFolder;
    }
}

/**
 * @Entity
 * @Table(name="cdi_filefolder2")
 */
class FileFolder2
{
    /**
     * @Id
     * @Column(type="integer")
     *
     * @var int $id
     */
    private $id;

    /**
     * @Column(type="string", length=128)
     *
     * @var string $title
     */
    private $title;

    /**
     * @OneToOne(targetEntity="Paper2", mappedBy="fileFolder", cascade={"all"}, orphanRemoval=true)
     *
     * @var Paper $paper
     */
    private $paper;

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param Paper2 $paper
     */
    public function setPaper(Paper2 $paper)
    {
        $this->paper = $paper;
    }

    /**
     * @return Paper2
     */
    public function getPaper()
    {
        return $this->paper;
    }
}

/**
 * @Entity
 * @Table(name="cdi_paper2")
 */
class Paper2
{
    /**
     * @Id
     * @OneToOne(targetEntity="FileFolder2", inversedBy="paper")
     *
     * @var FileFolder $fileFolder
     */
    private $fileFolder;

    /**
     * @Column(type="string", length=128)
     *
     * @var string $description
     */
    private $description;

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return FileFolder2
     */
    public function getFileFolder()
    {
        return $this->fileFolder;
    }

    /**
     * @param FileFolder2 $fileFolder
     */
    public function setFileFolder(FileFolder2 $fileFolder)
    {
        $this->fileFolder = $fileFolder;
    }
}

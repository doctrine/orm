<?php


namespace Doctrine\Tests\ORM\Functional;


use Doctrine\ORM\Tools\ToolsException;

class MergeSharedEntitiesTest extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(
                array(
                    $this->_em->getClassMetadata(__NAMESPACE__ . '\MSEFile'),
                    $this->_em->getClassMetadata(__NAMESPACE__ . '\MSEPicture'),
                )
            );
        } catch (ToolsException $ignored) {
        }
    }

    public function testMergeSharedNewEntities()
    {

        /** @var MSEPicture $picture */
        $file = new MSEFile;

        $picture = new MSEPicture;
        $picture->file = $file;
        $picture->otherFile = $file;

        $em = $this->_em;

        $picture = $em->merge($picture);

        $this->assertEquals($picture->file, $picture->otherFile, "Identical entities must remain identical");
    }

    public function testMergeSharedManagedEntities()
    {

        /** @var MSEPicture $picture */
        $file = new MSEFile;

        $picture = new MSEPicture;
        $picture->file = $file;
        $picture->otherFile = $file;

        $em = $this->_em;
        $em->persist($file);
        $em->flush();
        $em->clear();

        $picture = $em->merge($picture);

        $this->assertEquals($picture->file, $picture->otherFile, "Identical entities must remain identical");
    }

    public function testMergeSharedManagedEntitiesSerialize()
    {

        /** @var MSEPicture $picture */
        $file = new MSEFile;

        $picture = new MSEPicture;
        $picture->file = $file;
        $picture->otherFile = $file;

        $serializedPicture = serialize($picture);

        $em = $this->_em;
        $em->persist($file);
        $em->flush();
        $em->clear();

        $picture = unserialize($serializedPicture);
        $picture = $em->merge($picture);

        $this->assertEquals($picture->file, $picture->otherFile, "Identical entities must remain identical");
    }

}

/**
 * @Entity
 */
class MSEPicture
{
    /**
     * @Column(name="picture_id", type="integer")
     * @Id @GeneratedValue
     */
    public $pictureId;

    /**
     * @ManyToOne(targetEntity="MSEFile", cascade={"persist", "merge"})
     * @JoinColumn(name="file_id", referencedColumnName="file_id")
     */
    public $file;

    /**
     * @ManyToOne(targetEntity="MSEFile", cascade={"persist", "merge"})
     * @JoinColumn(name="other_file_id", referencedColumnName="file_id")
     */
    public $otherFile;
}

/**
 * @Entity
 */
class MSEFile
{
    /**
     * @Column(name="file_id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    public $fileId;

}

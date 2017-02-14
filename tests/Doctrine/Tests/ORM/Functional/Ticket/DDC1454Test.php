<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\UnitOfWork;
use Doctrine\ORM\Annotation as ORM;

/**
 * @group DDC-1454
 */
class DDC1454Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(DDC1454File::class),
                    $this->em->getClassMetadata(DDC1454Picture::class),
                ]
            );
        } catch (\Exception $ignored) {
        }
    }

    public function testFailingCase()
    {
        $pic = new DDC1454Picture();

        self::assertSame(UnitOfWork::STATE_NEW, $this->em->getUnitOfWork()->getEntityState($pic));
    }
}

/**
 * @ORM\Entity
 */
class DDC1454Picture extends DDC1454File
{
}

/**
 * @ORM\Entity
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="discr", type="string")
 * @ORM\DiscriminatorMap({"file" = "DDC1454File", "picture" = "DDC1454Picture"})
 */
class DDC1454File
{
    /**
     * @ORM\Column(name="file_id", type="integer")
     * @ORM\Id
     */
    public $fileId;

    public function __construct()
    {
        $this->fileId = rand();
    }

    /**
     * Get fileId
     */
    public function getFileId()
    {
        return $this->fileId;
    }

}

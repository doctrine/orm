<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Tests\OrmFunctionalTestCase;
use Exception;
use function getrandmax;
use function random_int;

/**
 * @group DDC-1454
 */
class DDC1454Test extends OrmFunctionalTestCase
{
    protected function setUp() : void
    {
        parent::setUp();

        try {
            $this->schemaTool->createSchema(
                [
                    $this->em->getClassMetadata(DDC1454File::class),
                    $this->em->getClassMetadata(DDC1454Picture::class),
                ]
            );
        } catch (Exception $ignored) {
        }
    }

    public function testFailingCase() : void
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
 * @ORM\DiscriminatorMap({"file" = DDC1454File::class, "picture" = DDC1454Picture::class})
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
        $this->fileId = random_int(0, getrandmax());
    }

    /**
     * Get fileId
     */
    public function getFileId()
    {
        return $this->fileId;
    }
}

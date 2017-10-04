<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\UnitOfWork;

/**
 * @group DDC-1454
 */
class DDC1454Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(
                [
                    $this->_em->getClassMetadata(DDC1454File::class),
                    $this->_em->getClassMetadata(DDC1454Picture::class),
                ]
            );
        } catch (\Exception $ignored) {
        }
    }

    public function testFailingCase()
    {
        $pic = new DDC1454Picture();

        self::assertSame(UnitOfWork::STATE_NEW, $this->_em->getUnitOfWork()->getEntityState($pic));
    }
}

/**
 * @Entity
 */
class DDC1454Picture extends DDC1454File
{
}

/**
 * @Entity
 * @InheritanceType("JOINED")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({"file" = "DDC1454File", "picture" = "DDC1454Picture"})
 */
class DDC1454File
{
    /**
     * @Column(name="file_id", type="integer")
     * @Id
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

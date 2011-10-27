<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\UnitOfWork;

require_once __DIR__ . '/../../../TestInit.php';

/**
 * @group DDC-1454
 */
class DDC1454Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1454File'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC1454Picture'),
            ));
        } catch (\Exception $ignored) {

        }
    }

    public function testFailingCase()
    {
        $pic = new DDC1454Picture();
        $this->_em->getUnitOfWork()->getEntityState($pic);
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
 * @DiscriminatorMap({"picture" = "DDC1454Picture"})
 */
class DDC1454File
{
    /**
     * @Column(name="file_id", type="integer")
     * @Id
     */
    public $fileId;

    public function __construct() {
        $this->fileId = rand();
    }

    /**
     * Get fileId
     */
    public function getFileId() {
        return $this->fileId;
    }

}
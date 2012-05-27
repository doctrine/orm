<?php

namespace Doctrine\Tests\ORM\Functional;

require_once __DIR__ . '/../../TestInit.php';

/**
 * Functional Query tests.
 *
 * @group DDC-692
 */
class ReadOnlyTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\ReadOnlyEntity'),
            ));
        } catch(\Exception $e) {
        }
    }

    public function testReadOnlyEntityNeverChangeTracked()
    {
        $readOnly = new ReadOnlyEntity("Test1", 1234);
        $this->_em->persist($readOnly);
        $this->_em->flush();

        $readOnly->name = "Test2";
        $readOnly->number = 4321;

        $this->_em->flush();
        $this->_em->clear();

        $dbReadOnly = $this->_em->find('Doctrine\Tests\ORM\Functional\ReadOnlyEntity', $readOnly->id);
        $this->assertEquals("Test1", $dbReadOnly->name);
        $this->assertEquals(1234, $dbReadOnly->number);
    }

    /**
     * @group DDC-1659
     */
    public function testClearReadOnly()
    {
        $readOnly = new ReadOnlyEntity("Test1", 1234);
        $this->_em->persist($readOnly);
        $this->_em->flush();
        $this->_em->getUnitOfWork()->markReadOnly($readOnly);

        $this->_em->clear();

        $this->assertFalse($this->_em->getUnitOfWork()->isReadOnly($readOnly));
    }
}

/**
 * @Entity(readOnly=true)
 */
class ReadOnlyEntity
{
    /**
     * @Id @GeneratedValue @Column(type="integer")
     * @var int
     */
    public $id;
    /** @column(type="string") */
    public $name;
    /** @Column(type="integer", name="number_col") */
    public $number;

    public function __construct($name, $number)
    {
        $this->name = $name;
        $this->number = $number;
    }
}

<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

/**
 * Class DDC2895Test
 * @package Doctrine\Tests\ORM\Functional\Ticket
 * @author http://github.com/gwagner
 */
class DDC2895Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC2895'),
            ));
        } catch(\Exception $e) {

        }
    }

    public function testPostLoadOneToManyInheritance()
    {
        $cm = $this->_em->getClassMetadata(__NAMESPACE__ . '\\DDC2895');

        $this->assertEquals(
            array(
                "prePersist" => array("setLastModifiedPreUpdate"),
                "preUpdate" => array("setLastModifiedPreUpdate"),
            ),
            $cm->lifecycleCallbacks
        );

        $ddc2895 = new DDC2895();

        $this->_em->persist($ddc2895);
        $this->_em->flush();
        $this->_em->clear();

        /** @var DDC2895 $ddc2895 */
        $ddc2895 = $this->_em->find(get_class($ddc2895), $ddc2895->id);

        $this->assertNotNull($ddc2895->getLastModified());

    }
}

/**
 * @MappedSuperclass
 * @HasLifecycleCallbacks
 */
abstract class AbstractDDC2895
{
    /**
     * @Column(name="last_modified", type="datetimetz", nullable=false)
     * @var \DateTime
     */
    protected $lastModified;

    /**
     * @PrePersist
     * @PreUpdate
     */
    public function setLastModifiedPreUpdate()
    {
        $this->setLastModified(new \DateTime());
    }

    /**
     * @param \DateTime $lastModified
     */
    public function setLastModified( $lastModified )
    {
        $this->lastModified = $lastModified;
    }

    /**
     * @return \DateTime
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }
}

/**
 * @Entity
 * @HasLifecycleCallbacks
 */
class DDC2895 extends AbstractDDC2895
{
    /** @Id @GeneratedValue @Column(type="integer") */
    public $id;

    /**
     * @param mixed $id
     */
    public function setId( $id )
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}
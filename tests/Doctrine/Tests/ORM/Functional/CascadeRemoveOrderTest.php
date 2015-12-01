<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @group CascadeRemoveOrderTest
 */
class CascadeRemoveOrderTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\CascadeRemoveOrderEntityO'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\CascadeRemoveOrderEntityG'),
        ));
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->_schemaTool->dropSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\CascadeRemoveOrderEntityO'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\CascadeRemoveOrderEntityG'),
        ));
    }

    public function testSingle()
    {
        $eO = new CascadeRemoveOrderEntityO();
        $eG = new CascadeRemoveOrderEntityG($eO);

        $this->_em->persist($eO);
        $this->_em->flush();
        $this->_em->clear();

        $eOloaded = $this->_em->find('Doctrine\Tests\ORM\Functional\CascadeRemoveOrderEntityO', $eO->getId());

        $this->_em->remove($eOloaded);
        $this->_em->flush();
    }

    public function testMany()
    {
        $eO  = new CascadeRemoveOrderEntityO();
        $eG1 = new CascadeRemoveOrderEntityG($eO);
        $eG2 = new CascadeRemoveOrderEntityG($eO);
        $eG3 = new CascadeRemoveOrderEntityG($eO);

        $eO->setOneToOneG($eG2);

        $this->_em->persist($eO);
        $this->_em->flush();
        $this->_em->clear();

        $eOloaded = $this->_em->find('Doctrine\Tests\ORM\Functional\CascadeRemoveOrderEntityO', $eO->getId());

        $this->_em->remove($eOloaded);
        $this->_em->flush();
    }
}

/**
 * @Entity
 */
class CascadeRemoveOrderEntityO
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @OneToOne(targetEntity="Doctrine\Tests\ORM\Functional\CascadeRemoveOrderEntityG")
     * @JoinColumn(nullable=true, onDelete="SET NULL")
     */
    private $oneToOneG;

    /**
     * @OneToMany(
     *     targetEntity="Doctrine\Tests\ORM\Functional\CascadeRemoveOrderEntityG",
     *     mappedBy="ownerO",
     *     cascade={"persist", "remove"}
     * )
     */
    private $oneToManyG;


    public function __construct()
    {
        $this->oneToManyG = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setOneToOneG(CascadeRemoveOrderEntityG $eG)
    {
        $this->oneToOneG = $eG;
    }

    public function getOneToOneG()
    {
        return $this->oneToOneG;
    }

    public function addOneToManyG(CascadeRemoveOrderEntityG $eG)
    {
        $this->oneToManyG->add($eG);
    }

    public function getOneToManyGs()
    {
        return $this->oneToManyG->toArray();
    }
}

/**
 * @Entity
 */
class CascadeRemoveOrderEntityG
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @ManyToOne(
     *     targetEntity="Doctrine\Tests\ORM\Functional\CascadeRemoveOrderEntityO",
     *     inversedBy="oneToMany"
     * )
     */
    private $ownerO;

    public function __construct(CascadeRemoveOrderEntityO $eO, $position=1)
    {
        $this->position = $position;
        $this->ownerO= $eO;
        $this->ownerO->addOneToManyG($this);
    }

    public function getId()
    {
        return $this->id;
    }
}
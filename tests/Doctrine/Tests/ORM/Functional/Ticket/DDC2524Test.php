<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @group DDC-2524
 */
class DDC2524Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2524EntityO'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2524EntityG'),
        ));
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->_schemaTool->dropSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2524EntityO'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2524EntityG'),
        ));
    }

    public function testSingle()
    {
        $eO = new DDC2524EntityO();
        $eG = new DDC2524EntityG($eO);

        $this->_em->persist($eO);
        $this->_em->flush();
        $this->_em->clear();

        $eOloaded = $this->_em->find(__NAMESPACE__ . '\DDC2524EntityO', $eO->getId());

        $this->_em->remove($eOloaded);
        $this->_em->flush();
    }

    public function testMany()
    {
        $eO = new DDC2524EntityO();
        $eG1 = new DDC2524EntityG($eO);
        $eG2 = new DDC2524EntityG($eO);
        $eG3 = new DDC2524EntityG($eO);

        $eO->setOneToOneG($eG2);

        $this->_em->persist($eO);
        $this->_em->flush();
        $this->_em->clear();

        $eOloaded = $this->_em->find(__NAMESPACE__ . '\DDC2524EntityO', $eO->getId());

        $this->_em->remove($eOloaded);
        $this->_em->flush();
    }
}

/**
 * @Entity
 */
class DDC2524EntityO
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @OneToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DDC2524EntityG")
     * @JoinColmun(nullable=true)
     **/
    private $oneToOneG;

    /**
     * @OneToMany(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DDC2524EntityG", mappedBy="ownerO", cascade={"persist", "remove"})
     **/
    private $oneToManyG;

    public function __construct()
    {
        $this->oneToManyG = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function setOneToOneG(DDC2524EntityG $eG)
    {
        $this->oneToOneG = $eG;
    }

    public function getOneToOneG()
    {
        return $this->oneToOneG;
    }

    public function addOneToManyG(DDC2524EntityG $eG)
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
class DDC2524EntityG
{
    /**
     * @Id @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @ManyToOne(targetEntity="Doctrine\Tests\ORM\Functional\Ticket\DDC2524EntityO", inversedBy="oneToManyG")
     **/
    private $ownerO;

    public function __construct(DDC2524EntityO $eO, $position = 1)
    {
        $this->position = $position;
        $this->ownerO   = $eO;

        $this->ownerO->addOneToManyG($this);
    }

    public function getId()
    {
        return $this->id;
    }
}

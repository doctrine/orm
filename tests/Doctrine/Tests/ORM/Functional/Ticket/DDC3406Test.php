<?php
namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * @group DDC-3406
 */
class DDC3406Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();
        
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3406User'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3406PAddress'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3406Plant'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC3406Hierarchy')
        ));
    }

    public function testIssue()
    {
        $hierarchy = new DDC3406Hierarchy();
        
        $plant = new DDC3406Plant();
        $plant->setHierarchy($hierarchy);
        
        $address = new DDC3406PAddress();
        $address->setPlant($plant);
        
        $user = new DDC3406User();
        $user->setAddress($address);
        
        $this->_em->persist($hierarchy);
        $this->_em->flush();
        $this->_em->persist($plant);
        $this->_em->persist($address);
        $this->_em->flush();
        $this->_em->persist($user);
        $this->_em->flush();
        
        $this->_em->clear();
        
        $user = $this->_em->find(__NAMESPACE__ . '\DDC3406User', $user->getAddress()
            ->getId());
        
        $this->assertInstanceOf(__NAMESPACE__ . '\DDC3406User', $user);
        $this->assertInstanceOf(__NAMESPACE__ . '\DDC3406PAddress', $user->getAddress());
        $this->assertInstanceOf(__NAMESPACE__ . '\DDC3406Plant', $user->getAddress()
            ->getPlant());
        $this->assertInstanceOf(__NAMESPACE__ . '\DDC3406Hierarchy', $user->getAddress()
            ->getPlant()
            ->getHierarchy());
    }
}

/**
 * @Entity
 */
class DDC3406User
{

    /**
     * @Id
     * @OneToOne(targetEntity="DDC3406PAddress", inversedBy="user", fetch="EAGER")
     * @JoinColumn(name="sysAddress_addId", referencedColumnName="addId")
     *
     * @var DDC3406PAddress
     */
    protected $address;

    public function setAddress(DDC3406PAddress $address)
    {
        $this->address = $address;
    }

    public function getAddress()
    {
        return $this->address;
    }
}

/**
 * @Entity
 */
class DDC3406PAddress
{

    /**
     * @Id
     * @Column(name="addId", type="integer")
     * @GeneratedValue
     *
     * @var integer
     */
    protected $id;

    /**
     * @OneToOne(targetEntity="DDC3406User", mappedBy="address", fetch="EAGER")
     *
     * @var User
     */
    protected $user;

    /**
     * @ManyToOne(targetEntity="DDC3406Plant", fetch="EAGER")
     * @JoinColumn(name="sysPlant_plaId", referencedColumnName="plaId", nullable=true)
     *
     * @var DDC3406Plant
     */
    protected $plant;

    public function getId()
    {
        return $this->id;
    }

    public function getUser()
    {
        return $this->user;
    }

    public function setPlant(DDC3406Plant $plant)
    {
        $this->plant = $plant;
    }

    public function getPlant()
    {
        return $this->plant;
    }
}

/**
 * @Entity
 */
class DDC3406Plant
{

    /**
     * @Id
     * @GeneratedValue(strategy="NONE")
     * @OneToOne(targetEntity="DDC3406Hierarchy", fetch="EAGER")
     * @JoinColumn(name="plaId", referencedColumnName="hieId")
     *
     * @var DDC3406Hierarchy
     */
    protected $hierarchy;

    public function setHierarchy(DDC3406Hierarchy $hierarchy)
    {
        $this->hierarchy = $hierarchy;
    }

    public function getHierarchy()
    {
        return $this->hierarchy;
    }
}

/**
 * @Entity
 */
class DDC3406Hierarchy
{

    /**
     * @Id
     * @GeneratedValue
     * @Column(name="hieId", type="integer")
     *
     * @var integer
     */
    protected $id;

    public function getId()
    {
        return $this->id;
    }
}

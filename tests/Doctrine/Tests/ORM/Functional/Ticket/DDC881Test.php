<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC881Test extends \Doctrine\Tests\OrmFunctionalTestCase
{

    protected function setUp()
    {
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC881User'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC881Phonenumber'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC881Phonecall'),
            ));
        } catch (\Exception $e) {
            
        }
    }

    /**
     * @group DDC-117
     * @group DDC-881
     */
    public function testIssue()
    {
        /* Create two test users: albert and alfons */
        $albert = new DDC881User;
        $albert->setName("albert");
        $this->_em->persist($albert);

        $alfons = new DDC881User;
        $alfons->setName("alfons");
        $this->_em->persist($alfons);

        $this->_em->flush();

        /* Assign two phone numbers to each user */
        $phoneAlbert1 = new DDC881PhoneNumber();
        $phoneAlbert1->setUser($albert);
        $phoneAlbert1->setId(1);
        $phoneAlbert1->setPhoneNumber("albert home: 012345");
        $this->_em->persist($phoneAlbert1);

        $phoneAlbert2 = new DDC881PhoneNumber();
        $phoneAlbert2->setUser($albert);
        $phoneAlbert2->setId(2);
        $phoneAlbert2->setPhoneNumber("albert mobile: 67890");
        $this->_em->persist($phoneAlbert2);

        $phoneAlfons1 = new DDC881PhoneNumber();
        $phoneAlfons1->setId(1);
        $phoneAlfons1->setUser($alfons);
        $phoneAlfons1->setPhoneNumber("alfons home: 012345");
        $this->_em->persist($phoneAlfons1);

        $phoneAlfons2 = new DDC881PhoneNumber();
        $phoneAlfons2->setId(2);
        $phoneAlfons2->setUser($alfons);
        $phoneAlfons2->setPhoneNumber("alfons mobile: 67890");
        $this->_em->persist($phoneAlfons2);

        /* We call alfons and albert once on their mobile numbers */
        $call1 = new DDC881PhoneCall();
        $call1->setPhoneNumber($phoneAlfons2);
        $this->_em->persist($call1);

        $call2 = new DDC881PhoneCall();
        $call2->setPhoneNumber($phoneAlbert2);
        $this->_em->persist($call2);

        $this->_em->flush();
        $this->_em->clear();

        // fetch-join that foreign-key/primary-key entity association
        $dql = "SELECT c, p FROM " . __NAMESPACE__ . "\DDC881PhoneCall c JOIN c.phonenumber p";
        $calls = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($calls));
        $this->assertNotInstanceOf('Doctrine\ORM\Proxy\Proxy', $calls[0]->getPhoneNumber());
        $this->assertNotInstanceOf('Doctrine\ORM\Proxy\Proxy', $calls[1]->getPhoneNumber());

        $dql = "SELECT p, c FROM " . __NAMESPACE__ . "\DDC881PhoneNumber p JOIN p.calls c";
        $numbers = $this->_em->createQuery($dql)->getResult();

        $this->assertEquals(2, count($numbers));
        $this->assertInstanceOf('Doctrine\ORM\PersistentCollection', $numbers[0]->getCalls());
        $this->assertTrue($numbers[0]->getCalls()->isInitialized());
    }

}

/**
 * @Entity
 */
class DDC881User
{

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @Column(type="string")
     */
    private $name;
    /**
     * @OneToMany(targetEntity="DDC881PhoneNumber",mappedBy="id")
     */
    private $phoneNumbers;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}

/**
 * @Entity
 */
class DDC881PhoneNumber
{

    /**
     * @Id
     * @Column(type="integer")
     */
    private $id;
    /**
     * @Id
     * @ManyToOne(targetEntity="DDC881User",cascade={"all"})
     */
    private $user;
    /**
     * @Column(type="string")
     */
    private $phonenumber;

    /**
     * @OneToMany(targetEntity="DDC881PhoneCall", mappedBy="phonenumber")
     */
    private $calls;

    public function __construct()
    {
        $this->calls = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function setUser(DDC881User $user)
    {
        $this->user = $user;
    }

    public function setPhoneNumber($phoneNumber)
    {
        $this->phonenumber = $phoneNumber;
    }

    public function getCalls()
    {
        return $this->calls;
    }
}

/**
 * @Entity
 */
class DDC881PhoneCall
{

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ManyToOne(targetEntity="DDC881PhoneNumber", inversedBy="calls", cascade={"all"})
     * @JoinColumns({
     *  @JoinColumn(name="phonenumber_id", referencedColumnName="id"),
     *  @JoinColumn(name="user_id", referencedColumnName="user_id")
     * })
     */
    private $phonenumber;
    /**
     * @Column(type="string",nullable=true)
     */
    private $callDate;

    public function setPhoneNumber(DDC881PhoneNumber $phoneNumber)
    {
        $this->phonenumber = $phoneNumber;
    }

    public function getPhoneNumber()
    {
        return $this->phonenumber;
    }
}
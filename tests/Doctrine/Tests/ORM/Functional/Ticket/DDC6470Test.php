<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Tools\ToolsException;

/**
 */
class DDC6470Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->enableSecondLevelCache();
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(
                [
                    $this->_em->getClassMetadata(DDC6470User::class),
                    $this->_em->getClassMetadata(DDC6470Driver::class),
                ]
            );
        } catch (ToolsException $exc) {
        }
    }

    public function testIssue()
    {
        $driver1 = new DDC6470Driver('Bar');

        $this->_em->persist($driver1);
        $this->_em->flush();
        $this->_em->clear();


        $this->assertTrue($this->_em->getCache()->containsEntity(DDC6470User::class, ['id' => $driver1->getUserProfile()->getId()]));
        $queryCount = $this->getCurrentQueryCount();
        $driver3 = $this->_em->createQueryBuilder()
            ->from(DDC6470Driver::class, 'd')
            ->select('d')
            ->where('d.id = :id')
            ->setParameter('id', $driver1->getId())
            ->getQuery()
            ->getOneOrNullResult();
        $this->assertEquals($queryCount+1, $this->getCurrentQueryCount());
        $this->assertInstanceOf(DDC6470Driver::class, $driver3);
        $this->assertInstanceOf(DDC6470User::class, $driver3->getUserProfile());
    }
}

/**
 * @Entity
 * @Table(name="ddc6470_drivers")
 */
class DDC6470Driver
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * @Cache("NONSTRICT_READ_WRITE")
     * @OneToOne(targetEntity="DDC6470User", mappedBy="user", cascade={"persist"})
     */
    protected $userProfile;

    public function __construct($name)
    {
        $this->name = $name;
        $this->userProfile = new DDC6470User('Foo', $this);
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param \Entities\User $userProfile
     */
    public function setUserProfile($userProfile)
    {
        $this->userProfile = $userProfile;
    }

    /**
     * @return \Entities\User
     */
    public function getUserProfile()
    {
        return $this->userProfile;
    }

}

/**
 * @Entity
 * @Table(name="ddc6470_users")
 * @Cache("NONSTRICT_READ_WRITE")
 */
class DDC6470User
{
    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @Column(type="string")
     * @var string
     */
    protected $name;
    /**
     *
     * @OneToOne(targetEntity="DDC6470Driver", inversedBy="userProfile")
     * @JoinColumn(name="user_id", referencedColumnName="id", onDelete="cascade")
     * @Cache(usage="READ_ONLY")
     */
    protected $user;

    public function __construct($name, DDC6470Driver $user)
    {
        $this->name = $name;
        $this->user = $user;
    }

    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

}

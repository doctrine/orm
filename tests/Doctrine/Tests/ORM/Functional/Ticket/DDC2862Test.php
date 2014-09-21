<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Tools\ToolsException;

/**
 * @group DDC-2862
 * @group DDC-2183
 */
class DDC2862Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function setUp()
    {
        $this->enableSecondLevelCache();
        parent::setUp();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(DDC2862User::CLASSNAME),
                $this->_em->getClassMetadata(DDC2862Driver::CLASSNAME),
            ));
        } catch (ToolsException $exc) {
        }
    }

    public function testIssue()
    {
        $user1    = new DDC2862User('Foo');
        $driver1  = new DDC2862Driver('Bar' , $user1);

        $this->_em->persist($user1);
        $this->_em->persist($driver1);
        $this->_em->flush();
        $this->_em->clear();

        $this->assertTrue($this->_em->getCache()->containsEntity(DDC2862User::CLASSNAME, array('id' => $user1->getId())));
        $this->assertTrue($this->_em->getCache()->containsEntity(DDC2862Driver::CLASSNAME, array('id' => $driver1->getId())));

        $queryCount = $this->getCurrentQueryCount();
        $driver2    = $this->_em->find(DDC2862Driver::CLASSNAME, $driver1->getId());

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
        $this->assertInstanceOf(DDC2862Driver::CLASSNAME, $driver2);
        $this->assertInstanceOf(DDC2862User::CLASSNAME, $driver2->getUserProfile());

        $driver2->setName('Franta');

        $this->_em->flush();
        $this->_em->clear();

        $this->assertTrue($this->_em->getCache()->containsEntity(DDC2862User::CLASSNAME, array('id' => $user1->getId())));
        $this->assertTrue($this->_em->getCache()->containsEntity(DDC2862Driver::CLASSNAME, array('id' => $driver1->getId())));

        $queryCount = $this->getCurrentQueryCount();
        $driver3    = $this->_em->find(DDC2862Driver::CLASSNAME, $driver1->getId());

        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
        $this->assertInstanceOf(DDC2862Driver::CLASSNAME, $driver3);
        $this->assertInstanceOf(DDC2862User::CLASSNAME, $driver3->getUserProfile());
        $this->assertEquals('Franta', $driver3->getName());
        $this->assertEquals('Foo', $driver3->getUserProfile()->getName());
    }

    public function testIssueReopened()
    {
        $user1    = new DDC2862User('Foo');
        $driver1  = new DDC2862Driver('Bar' , $user1);

        $this->_em->persist($user1);
        $this->_em->persist($driver1);
        $this->_em->flush();
        $this->_em->clear();

        $this->_em->getCache()->evictEntityRegion(DDC2862User::CLASSNAME);
        $this->_em->getCache()->evictEntityRegion(DDC2862Driver::CLASSNAME);

        $this->assertFalse($this->_em->getCache()->containsEntity(DDC2862User::CLASSNAME, array('id' => $user1->getId())));
        $this->assertFalse($this->_em->getCache()->containsEntity(DDC2862Driver::CLASSNAME, array('id' => $driver1->getId())));

        $queryCount = $this->getCurrentQueryCount();
        $driver2    = $this->_em->find(DDC2862Driver::CLASSNAME, $driver1->getId());

        $this->assertInstanceOf(DDC2862Driver::CLASSNAME, $driver2);
        $this->assertInstanceOf(DDC2862User::CLASSNAME, $driver2->getUserProfile());
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $this->_em->clear();

        $this->assertFalse($this->_em->getCache()->containsEntity(DDC2862User::CLASSNAME, array('id' => $user1->getId())));
        $this->assertTrue($this->_em->getCache()->containsEntity(DDC2862Driver::CLASSNAME, array('id' => $driver1->getId())));

        $queryCount = $this->getCurrentQueryCount();
        $driver3    = $this->_em->find(DDC2862Driver::CLASSNAME, $driver1->getId());

        $this->assertInstanceOf(DDC2862Driver::CLASSNAME, $driver3);
        $this->assertInstanceOf(DDC2862User::CLASSNAME, $driver3->getUserProfile());
        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
        $this->assertEquals('Foo', $driver3->getUserProfile()->getName());
        $this->assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $queryCount = $this->getCurrentQueryCount();
        $driver4    = $this->_em->find(DDC2862Driver::CLASSNAME, $driver1->getId());

        $this->assertInstanceOf(DDC2862Driver::CLASSNAME, $driver4);
        $this->assertInstanceOf(DDC2862User::CLASSNAME, $driver4->getUserProfile());
        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
        $this->assertEquals('Foo', $driver4->getUserProfile()->getName());
        $this->assertEquals($queryCount, $this->getCurrentQueryCount());
    }
}

/**
 * @Entity
 * @Table(name="ddc2862_drivers")
 * @Cache("NONSTRICT_READ_WRITE")
 */
class DDC2862Driver
{
    const CLASSNAME = __CLASS__;

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
     * @Cache()
     * @OneToOne(targetEntity="DDC2862User")
     * @var User
     */
    protected $userProfile;

    public function __construct($name, $userProfile = null)
    {
        $this->name        = $name;
        $this->userProfile = $userProfile;
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
 * @Table(name="ddc2862_users")
 * @Cache("NONSTRICT_READ_WRITE")
 */
class DDC2862User
{
    const CLASSNAME = __CLASS__;

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

    public function __construct($name)
    {
        $this->name = $name;
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

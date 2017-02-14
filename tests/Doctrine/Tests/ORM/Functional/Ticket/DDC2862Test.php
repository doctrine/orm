<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
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
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(DDC2862User::class),
                $this->em->getClassMetadata(DDC2862Driver::class),
                ]
            );
        } catch (ToolsException $exc) {
        }
    }

    public function testIssue()
    {
        $user1    = new DDC2862User('Foo');
        $driver1  = new DDC2862Driver('Bar' , $user1);

        $this->em->persist($user1);
        $this->em->persist($driver1);
        $this->em->flush();
        $this->em->clear();

        self::assertTrue($this->em->getCache()->containsEntity(DDC2862User::class, ['id' => $user1->getId()]));
        self::assertTrue($this->em->getCache()->containsEntity(DDC2862Driver::class, ['id' => $driver1->getId()]));

        $queryCount = $this->getCurrentQueryCount();
        $driver2    = $this->em->find(DDC2862Driver::class, $driver1->getId());

        self::assertEquals($queryCount, $this->getCurrentQueryCount());
        self::assertInstanceOf(DDC2862Driver::class, $driver2);
        self::assertInstanceOf(DDC2862User::class, $driver2->getUserProfile());

        $driver2->setName('Franta');

        $this->em->flush();
        $this->em->clear();

        self::assertTrue($this->em->getCache()->containsEntity(DDC2862User::class, ['id' => $user1->getId()]));
        self::assertTrue($this->em->getCache()->containsEntity(DDC2862Driver::class, ['id' => $driver1->getId()]));

        $queryCount = $this->getCurrentQueryCount();
        $driver3    = $this->em->find(DDC2862Driver::class, $driver1->getId());

        self::assertEquals($queryCount, $this->getCurrentQueryCount());
        self::assertInstanceOf(DDC2862Driver::class, $driver3);
        self::assertInstanceOf(DDC2862User::class, $driver3->getUserProfile());
        self::assertEquals('Franta', $driver3->getName());
        self::assertEquals('Foo', $driver3->getUserProfile()->getName());
    }

    public function testIssueReopened()
    {
        $user1    = new DDC2862User('Foo');
        $driver1  = new DDC2862Driver('Bar' , $user1);

        $this->em->persist($user1);
        $this->em->persist($driver1);
        $this->em->flush();
        $this->em->clear();

        $this->em->getCache()->evictEntityRegion(DDC2862User::class);
        $this->em->getCache()->evictEntityRegion(DDC2862Driver::class);

        self::assertFalse($this->em->getCache()->containsEntity(DDC2862User::class, ['id' => $user1->getId()]));
        self::assertFalse($this->em->getCache()->containsEntity(DDC2862Driver::class, ['id' => $driver1->getId()]));

        $queryCount = $this->getCurrentQueryCount();
        $driver2    = $this->em->find(DDC2862Driver::class, $driver1->getId());

        self::assertInstanceOf(DDC2862Driver::class, $driver2);
        self::assertInstanceOf(DDC2862User::class, $driver2->getUserProfile());
        self::assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $this->em->clear();

        self::assertFalse($this->em->getCache()->containsEntity(DDC2862User::class, ['id' => $user1->getId()]));
        self::assertTrue($this->em->getCache()->containsEntity(DDC2862Driver::class, ['id' => $driver1->getId()]));

        $queryCount = $this->getCurrentQueryCount();
        $driver3    = $this->em->find(DDC2862Driver::class, $driver1->getId());

        self::assertInstanceOf(DDC2862Driver::class, $driver3);
        self::assertInstanceOf(DDC2862User::class, $driver3->getUserProfile());
        self::assertEquals($queryCount, $this->getCurrentQueryCount());
        self::assertEquals('Foo', $driver3->getUserProfile()->getName());
        self::assertEquals($queryCount + 1, $this->getCurrentQueryCount());

        $queryCount = $this->getCurrentQueryCount();
        $driver4    = $this->em->find(DDC2862Driver::class, $driver1->getId());

        self::assertInstanceOf(DDC2862Driver::class, $driver4);
        self::assertInstanceOf(DDC2862User::class, $driver4->getUserProfile());
        self::assertEquals($queryCount, $this->getCurrentQueryCount());
        self::assertEquals('Foo', $driver4->getUserProfile()->getName());
        self::assertEquals($queryCount, $this->getCurrentQueryCount());
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="ddc2862_drivers")
 * @ORM\Cache("NONSTRICT_READ_WRITE")
 */
class DDC2862Driver
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * @ORM\Cache()
     * @ORM\OneToOne(targetEntity="DDC2862User")
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
 * @ORM\Entity
 * @ORM\Table(name="ddc2862_users")
 * @ORM\Cache("NONSTRICT_READ_WRITE")
 */
class DDC2862User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    protected $id;

    /**
     * @ORM\Column(type="string")
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

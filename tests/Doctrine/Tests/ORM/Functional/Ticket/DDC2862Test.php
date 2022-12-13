<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Mapping\Cache;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-2862
 * @group DDC-2183
 */
class DDC2862Test extends OrmFunctionalTestCase
{
    protected function setUp(): void
    {
        $this->enableSecondLevelCache();

        parent::setUp();

        $this->createSchemaForModels(DDC2862User::class, DDC2862Driver::class);
    }

    public function testIssue(): void
    {
        $user1   = new DDC2862User('Foo');
        $driver1 = new DDC2862Driver('Bar', $user1);

        $this->_em->persist($user1);
        $this->_em->persist($driver1);
        $this->_em->flush();
        $this->_em->clear();

        self::assertTrue($this->_em->getCache()->containsEntity(DDC2862User::class, ['id' => $user1->getId()]));
        self::assertTrue($this->_em->getCache()->containsEntity(DDC2862Driver::class, ['id' => $driver1->getId()]));

        $this->getQueryLog()->reset()->enable();
        $driver2 = $this->_em->find(DDC2862Driver::class, $driver1->getId());

        $this->assertQueryCount(0);
        self::assertInstanceOf(DDC2862Driver::class, $driver2);
        self::assertInstanceOf(DDC2862User::class, $driver2->getUserProfile());

        $driver2->setName('Franta');

        $this->_em->flush();
        $this->_em->clear();

        self::assertTrue($this->_em->getCache()->containsEntity(DDC2862User::class, ['id' => $user1->getId()]));
        self::assertTrue($this->_em->getCache()->containsEntity(DDC2862Driver::class, ['id' => $driver1->getId()]));

        $this->getQueryLog()->reset()->enable();
        $driver3 = $this->_em->find(DDC2862Driver::class, $driver1->getId());

        $this->assertQueryCount(0);
        self::assertInstanceOf(DDC2862Driver::class, $driver3);
        self::assertInstanceOf(DDC2862User::class, $driver3->getUserProfile());
        self::assertEquals('Franta', $driver3->getName());
        self::assertEquals('Foo', $driver3->getUserProfile()->getName());
    }

    public function testIssueReopened(): void
    {
        $user1   = new DDC2862User('Foo');
        $driver1 = new DDC2862Driver('Bar', $user1);

        $this->_em->persist($user1);
        $this->_em->persist($driver1);
        $this->_em->flush();
        $this->_em->clear();

        $this->_em->getCache()->evictEntityRegion(DDC2862User::class);
        $this->_em->getCache()->evictEntityRegion(DDC2862Driver::class);

        self::assertFalse($this->_em->getCache()->containsEntity(DDC2862User::class, ['id' => $user1->getId()]));
        self::assertFalse($this->_em->getCache()->containsEntity(DDC2862Driver::class, ['id' => $driver1->getId()]));

        $this->getQueryLog()->reset()->enable();
        $driver2 = $this->_em->find(DDC2862Driver::class, $driver1->getId());

        self::assertInstanceOf(DDC2862Driver::class, $driver2);
        self::assertInstanceOf(DDC2862User::class, $driver2->getUserProfile());
        $this->assertQueryCount(1);

        $this->_em->clear();

        self::assertFalse($this->_em->getCache()->containsEntity(DDC2862User::class, ['id' => $user1->getId()]));
        self::assertTrue($this->_em->getCache()->containsEntity(DDC2862Driver::class, ['id' => $driver1->getId()]));

        $this->getQueryLog()->reset()->enable();
        $driver3 = $this->_em->find(DDC2862Driver::class, $driver1->getId());

        self::assertInstanceOf(DDC2862Driver::class, $driver3);
        self::assertInstanceOf(DDC2862User::class, $driver3->getUserProfile());
        $this->assertQueryCount(0);
        self::assertEquals('Foo', $driver3->getUserProfile()->getName());
        $this->assertQueryCount(1);

        $this->getQueryLog()->reset()->enable();
        $driver4 = $this->_em->find(DDC2862Driver::class, $driver1->getId());

        self::assertInstanceOf(DDC2862Driver::class, $driver4);
        self::assertInstanceOf(DDC2862User::class, $driver4->getUserProfile());
        $this->assertQueryCount(0);
        self::assertEquals('Foo', $driver4->getUserProfile()->getName());
        $this->assertQueryCount(0);
    }
}

/**
 * @Entity
 * @Table(name="ddc2862_drivers")
 * @Cache("NONSTRICT_READ_WRITE")
 */
class DDC2862Driver
{
    /**
     * @var int
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @Column(type="string", length=255)
     * @var string
     */
    protected $name;

    /**
     * @Cache()
     * @OneToOne(targetEntity="DDC2862User")
     * @var DDC2862User
     */
    protected $userProfile;

    public function __construct($name, $userProfile = null)
    {
        $this->name        = $name;
        $this->userProfile = $userProfile;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setUserProfile(DDC2862User $userProfile): void
    {
        $this->userProfile = $userProfile;
    }

    public function getUserProfile(): DDC2862User
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
    /**
     * @var int
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    protected $id;

    /**
     * @Column(type="string", length=255)
     * @var string
     */
    protected $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }
}

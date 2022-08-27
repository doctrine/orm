<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Deprecations\PHPUnit\VerifyDeprecations;
use Doctrine\ORM\Mapping\ChangeTrackingPolicy;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\Persistence\NotifyPropertyChanged;
use Doctrine\Persistence\PropertyChangedListener;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * NativeQueryTest
 */
class NotifyPolicyTest extends OrmFunctionalTestCase
{
    use VerifyDeprecations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->expectDeprecationWithIdentifier('https://github.com/doctrine/orm/issues/8383');

        $this->createSchemaForModels(NotifyUser::class, NotifyGroup::class);
    }

    public function testChangeTracking(): void
    {
        $user  = new NotifyUser();
        $group = new NotifyGroup();
        $user->setName('roman');
        $group->setName('dev');

        $user->getGroups()->add($group);
        $group->getUsers()->add($user);

        $this->_em->persist($user);
        $this->_em->persist($group);

        self::assertCount(1, $user->listeners);
        self::assertCount(1, $group->listeners);

        $this->_em->flush();
        $this->_em->clear();

        self::assertCount(1, $user->listeners);
        self::assertCount(1, $group->listeners);

        $userId  = $user->getId();
        $groupId = $group->getId();
        unset($user, $group);

        $user = $this->_em->find(NotifyUser::class, $userId);
        self::assertEquals(1, $user->getGroups()->count());
        $group = $this->_em->find(NotifyGroup::class, $groupId);
        self::assertEquals(1, $group->getUsers()->count());

        self::assertCount(1, $user->listeners);
        self::assertCount(1, $group->listeners);

        $group2 = new NotifyGroup();
        $group2->setName('nerds');
        $this->_em->persist($group2);
        $user->getGroups()->add($group2);
        $group2->getUsers()->add($user);

        $group->setName('geeks');

        $this->_em->flush();
        $this->_em->clear();

        self::assertCount(1, $user->listeners);
        self::assertCount(1, $group->listeners);

        $group2Id = $group2->getId();
        unset($group2, $user);

        $user = $this->_em->find(NotifyUser::class, $userId);
        self::assertEquals(2, $user->getGroups()->count());
        $group2 = $this->_em->find(NotifyGroup::class, $group2Id);
        self::assertEquals(1, $group2->getUsers()->count());
        $group = $this->_em->find(NotifyGroup::class, $groupId);
        self::assertEquals(1, $group->getUsers()->count());
        self::assertEquals('geeks', $group->getName());
    }
}

class NotifyBaseEntity implements NotifyPropertyChanged
{
    /** @psalm-var list<PropertyChangedListener> */
    public $listeners = [];

    public function addPropertyChangedListener(PropertyChangedListener $listener): void
    {
        $this->listeners[] = $listener;
    }

    protected function onPropertyChanged($propName, $oldValue, $newValue): void
    {
        if ($this->listeners) {
            foreach ($this->listeners as $listener) {
                $listener->propertyChanged($this, $propName, $oldValue, $newValue);
            }
        }
    }
}

/**
 * @Entity
 * @ChangeTrackingPolicy("NOTIFY")
 */
class NotifyUser extends NotifyBaseEntity
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @var string
     * @Column
     */
    private $name;

    /**
     * @psalm-var Collection<int, NotifyGroup>
     * @ManyToMany(targetEntity="NotifyGroup")
     */
    private $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->onPropertyChanged('name', $this->name, $name);
        $this->name = $name;
    }

    /** @psalm-return Collection<int, NotifyGroup> */
    public function getGroups(): Collection
    {
        return $this->groups;
    }
}

/** @Entity */
class NotifyGroup extends NotifyBaseEntity
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @var string
     * @Column
     */
    private $name;

    /**
     * @psalm-var Collection<int, NotifyUser>
     * @ManyToMany(targetEntity="NotifyUser", mappedBy="groups")
     */
    private $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->onPropertyChanged('name', $this->name, $name);
        $this->name = $name;
    }

    /** @psalm-return Collection<int, NotifyUser> */
    public function getUsers(): Collection
    {
        return $this->users;
    }
}

<?php

namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\PropertyChangedListener;
use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * NativeQueryTest
 *
 * @author robo
 */
class NotifyPolicyTest extends OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        try {
            $this->schemaTool->createSchema(
                [
                $this->em->getClassMetadata(NotifyUser::class),
                $this->em->getClassMetadata(NotifyGroup::class)
                ]
            );
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
    }

    public function testChangeTracking()
    {
        $user = new NotifyUser();
        $group = new NotifyGroup();
        $user->setName('roman');
        $group->setName('dev');

        $user->getGroups()->add($group);
        $group->getUsers()->add($user);

        $this->em->persist($user);
        $this->em->persist($group);

        self::assertEquals(1, count($user->listeners));
        self::assertEquals(1, count($group->listeners));

        $this->em->flush();
        $this->em->clear();

        self::assertEquals(1, count($user->listeners));
        self::assertEquals(1, count($group->listeners));

        $userId = $user->getId();
        $groupId = $group->getId();
        unset($user, $group);

        $user = $this->em->find(NotifyUser::class, $userId);
        self::assertEquals(1, $user->getGroups()->count());
        $group = $this->em->find(NotifyGroup::class, $groupId);
        self::assertEquals(1, $group->getUsers()->count());

        self::assertEquals(1, count($user->listeners));
        self::assertEquals(1, count($group->listeners));

        $group2 = new NotifyGroup();
        $group2->setName('nerds');
        $this->em->persist($group2);
        $user->getGroups()->add($group2);
        $group2->getUsers()->add($user);

        $group->setName('geeks');

        $this->em->flush();
        $this->em->clear();

        self::assertEquals(1, count($user->listeners));
        self::assertEquals(1, count($group->listeners));

        $group2Id = $group2->getId();
        unset($group2, $user);

        $user = $this->em->find(NotifyUser::class, $userId);
        self::assertEquals(2, $user->getGroups()->count());
        $group2 = $this->em->find(NotifyGroup::class, $group2Id);
        self::assertEquals(1, $group2->getUsers()->count());
        $group = $this->em->find(NotifyGroup::class, $groupId);
        self::assertEquals(1, $group->getUsers()->count());
        self::assertEquals('geeks', $group->getName());
    }
}

class NotifyBaseEntity implements NotifyPropertyChanged {
    public $listeners = [];

    public function addPropertyChangedListener(PropertyChangedListener $listener) {
        $this->listeners[] = $listener;
    }

    protected function onPropertyChanged($propName, $oldValue, $newValue) {
        if ($this->listeners) {
            foreach ($this->listeners as $listener) {
                $listener->propertyChanged($this, $propName, $oldValue, $newValue);
            }
        }
    }
}

/** @ORM\Entity @ORM\ChangeTrackingPolicy("NOTIFY") */
class NotifyUser extends NotifyBaseEntity {
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    private $id;

    /** @ORM\Column */
    private $name;

    /** @ORM\ManyToMany(targetEntity="NotifyGroup") */
    private $groups;

    public function __construct() {
        $this->groups = new ArrayCollection;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->onPropertyChanged('name', $this->name, $name);
        $this->name = $name;
    }

    public function getGroups() {
        return $this->groups;
    }
}

/** @ORM\Entity */
class NotifyGroup extends NotifyBaseEntity {
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue */
    private $id;

    /** @ORM\Column */
    private $name;

    /** @ORM\ManyToMany(targetEntity="NotifyUser", mappedBy="groups") */
    private $users;

    public function __construct() {
        $this->users = new ArrayCollection;
    }

    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function setName($name) {
        $this->onPropertyChanged('name', $this->name, $name);
        $this->name = $name;
    }

    public function getUsers() {
        return $this->users;
    }
}


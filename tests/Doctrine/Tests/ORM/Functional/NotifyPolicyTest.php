<?php
namespace Doctrine\Tests\ORM\Functional;

use Doctrine\Common\Collections\ArrayCollection,
    Doctrine\Common\NotifyPropertyChanged,
    Doctrine\Common\PropertyChangedListener;

require_once __DIR__ . '/../../TestInit.php';

/**
 * NativeQueryTest
 *
 * @author robo
 */
class NotifyPolicyTest extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp() {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\NotifyUser'),
                $this->_em->getClassMetadata('Doctrine\Tests\ORM\Functional\NotifyGroup')
            ));
        } catch (\Exception $e) {
            // Swallow all exceptions. We do not test the schema tool here.
        }
    }

    public function testChangeTracking()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);

        $user = new NotifyUser();
        $group = new NotifyGroup();
        $user->setName('roman');
        $group->setName('dev');

        $user->getGroups()->add($group);
        $group->getUsers()->add($user);

        $this->_em->persist($user);
        $this->_em->persist($group);

        $this->assertEquals(1, count($user->listeners));
        $this->assertEquals(1, count($group->listeners));

        $this->_em->flush();
        $this->_em->clear();

        $this->assertEquals(1, count($user->listeners));
        $this->assertEquals(1, count($group->listeners));

        $userId = $user->getId();
        $groupId = $group->getId();
        unset($user, $group);

        $user = $this->_em->find(__NAMESPACE__.'\NotifyUser', $userId);
        $this->assertEquals(1, $user->getGroups()->count());
        $group = $this->_em->find(__NAMESPACE__.'\NotifyGroup', $groupId);
        $this->assertEquals(1, $group->getUsers()->count());

        $this->assertEquals(1, count($user->listeners));
        $this->assertEquals(1, count($group->listeners));

        $group2 = new NotifyGroup();
        $group2->setName('nerds');
        $this->_em->persist($group2);
        $user->getGroups()->add($group2);
        $group2->getUsers()->add($user);

        $group->setName('geeks');

        $this->_em->flush();
        $this->_em->clear();

        $this->assertEquals(1, count($user->listeners));
        $this->assertEquals(1, count($group->listeners));

        $group2Id = $group2->getId();
        unset($group2, $user);

        $user = $this->_em->find(__NAMESPACE__.'\NotifyUser', $userId);
        $this->assertEquals(2, $user->getGroups()->count());
        $group2 = $this->_em->find(__NAMESPACE__.'\NotifyGroup', $group2Id);
        $this->assertEquals(1, $group2->getUsers()->count());
        $group = $this->_em->find(__NAMESPACE__.'\NotifyGroup', $groupId);
        $this->assertEquals(1, $group->getUsers()->count());
        $this->assertEquals('geeks', $group->getName());
    }
}

class NotifyBaseEntity implements NotifyPropertyChanged {
    public $listeners = array();

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

/** @Entity @ChangeTrackingPolicy("NOTIFY") */
class NotifyUser extends NotifyBaseEntity {
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;

    /** @Column */
    private $name;

    /** @ManyToMany(targetEntity="NotifyGroup") */
    private $groups;

    function __construct() {
        $this->groups = new ArrayCollection;
    }

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->name;
    }

    function setName($name) {
        $this->onPropertyChanged('name', $this->name, $name);
        $this->name = $name;
    }

    function getGroups() {
        return $this->groups;
    }
}

/** @Entity */
class NotifyGroup extends NotifyBaseEntity {
    /** @Id @Column(type="integer") @GeneratedValue */
    private $id;

    /** @Column */
    private $name;

    /** @ManyToMany(targetEntity="NotifyUser", mappedBy="groups") */
    private $users;

    function __construct() {
        $this->users = new ArrayCollection;
    }

    function getId() {
        return $this->id;
    }

    function getName() {
        return $this->name;
    }

    function setName($name) {
        $this->onPropertyChanged('name', $this->name, $name);
        $this->name = $name;
    }

    function getUsers() {
        return $this->users;
    }
}


<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

class DDC211Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC211User'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC211Group')
        ));
    }

    public function testIssue()
    {
        //$this->_em->getConnection()->getConfiguration()->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger);

        $user = new DDC211User;
        $user->setName('John Doe');

        $this->_em->persist($user);
        $this->_em->flush();

        $groupNames = array('group 1', 'group 2', 'group 3', 'group 4');
        foreach ($groupNames as $name) {

            $group = new DDC211Group;
            $group->setName($name);
            $this->_em->persist($group);
            $this->_em->flush();

            if (!$user->getGroups()->contains($group)) {
                $user->getGroups()->add($group);
                $group->getUsers()->add($user);
                $this->_em->flush();
            }
        }

        $this->assertEquals(4, $user->getGroups()->count());

    }
}


/**
 * @Entity
 * @Table(name="ddc211_users")
*/
class DDC211User
{
    /**
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(name="name", type="string")
     */
    protected $name;

    /**
    * @ManyToMany(targetEntity="DDC211Group", inversedBy="users")
    *   @JoinTable(name="user_groups",
    *       joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
    *       inverseJoinColumns={@JoinColumn(name="group_id", referencedColumnName="id")}
    *   )
    */
    protected $groups;

    public function __construct() {
        $this->groups = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function setName($name) { $this->name = $name; }

    public function getGroups() { return $this->groups; }
}

/**
 * @Entity
 * @Table(name="ddc211_groups")
 */
class DDC211Group
{
    /**
     * @Id
     * @Column(name="id", type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(name="name", type="string")
     */
    protected $name;

    /**
    * @ManyToMany(targetEntity="DDC211User", mappedBy="groups")
    */
    protected $users;

    public function __construct() {
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function setName($name) { $this->name = $name; }

    public function getUsers() { return $this->users; }
}


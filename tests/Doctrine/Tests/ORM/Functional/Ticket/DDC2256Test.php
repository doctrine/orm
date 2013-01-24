<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

require_once __DIR__ . '/../../../TestInit.php';

use Doctrine\ORM\Query\ResultSetMapping;

/**
 * @group DDC-2256
 */
class DDC2256Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setup()
    {
        parent::setup();
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2256User'),
            $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2256Group')
        ));
    }

    public function testIssue()
    {
        $config = $this->_em->getConfiguration();
        $config->addEntityNamespace('MyNamespace', 'Doctrine\Tests\ORM\Functional\Ticket');

        $user = new DDC2256User();
        $user->name = 'user';
        $group = new DDC2256Group();
        $group->name = 'group';
        $user->group = $group;

        $this->_em->persist($user);
        $this->_em->persist($group);
        $this->_em->flush();

        $rsm = new ResultSetMapping();

        $rsm->addEntityResult('MyNamespace:DDC2256Group', 'g');
        $rsm->addFieldResult('g', 'id', 'id');
        $rsm->addFieldResult('g', 'name', 'name');

        $rsm->addJoinedEntityResult('MyNamespace:DDC2256User', 'u', 'g', 'users');
        $rsm->addFieldResult('u', 'user_id', 'id');
        $rsm->addFieldResult('u', 'user_name', 'name');

        $this->_em->createNativeQuery('SELECT g.id, g.name, u.id as user_id, u.name as user_name FROM ddc2256_groups g LEFT JOIN ddc2256_users u ON u.group_id = g.id', $rsm)->getResult();
    }
}

/**
 * @Entity
 * @Table(name="ddc2256_users")
 */
class DDC2256User
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $name;

    /**
     * @ManyToOne(targetEntity="DDC2256Group", inversedBy="users")A
     * @JoinColumn(name="group_id")
     */
    public $group;
}

/**
 * @Entity
 * @Table(name="ddc2256_groups")
 */
class DDC2256Group
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @Column(type="string")
     */
    public $name;

    /**
     * @OneToMany(targetEntity="DDC2256User", mappedBy="group")
     */
    public $users;

    public function __construct()
    {
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
    }
}


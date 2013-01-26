<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

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
        $this->_em->clear();

        $sql = 'SELECT u.id, u.name, g.id as group_id, g.name as group_name FROM ddc2256_users u LEFT JOIN ddc2256_groups g ON u.group_id = g.id';

        // Test ResultSetMapping.
        $rsm = new ResultSetMapping();

        $rsm->addEntityResult('MyNamespace:DDC2256User', 'u');
        $rsm->addFieldResult('u', 'id', 'id');
        $rsm->addFieldResult('u', 'name', 'name');

        $rsm->addJoinedEntityResult('MyNamespace:DDC2256Group', 'g', 'u', 'group');
        $rsm->addFieldResult('g', 'group_id', 'id');
        $rsm->addFieldResult('g', 'group_name', 'name');

        $this->_em->createNativeQuery($sql, $rsm)->getResult();

        // Test ResultSetMappingBuilder.
        $rsm = new ResultSetMappingBuilder($this->_em);
        $rsm->addRootEntityFromClassMetadata('MyNamespace:DDC2256User', 'u');
        $rsm->addJoinedEntityFromClassMetadata('MyNamespace:DDC2256Group', 'g', 'u', 'group', array('id' => 'group_id', 'name' => 'group_name'));

        $this->_em->createNativeQuery($sql, $rsm)->getResult();
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


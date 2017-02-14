<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\ORM\Query\ResultSetMappingBuilder;

/**
 * @group DDC-2256
 */
class DDC2256Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->schemaTool->createSchema(
            [
                $this->em->getClassMetadata(DDC2256User::class),
                $this->em->getClassMetadata(DDC2256Group::class)
            ]
        );
    }

    public function testIssue()
    {
        $config = $this->em->getConfiguration();
        $config->addEntityNamespace('MyNamespace', __NAMESPACE__);

        $user = new DDC2256User();
        $user->name = 'user';
        $group = new DDC2256Group();
        $group->name = 'group';
        $user->group = $group;

        $this->em->persist($user);
        $this->em->persist($group);
        $this->em->flush();
        $this->em->clear();

        $sql = 'SELECT u.id, u.name, g.id as group_id, g.name as group_name FROM ddc2256_users u LEFT JOIN ddc2256_groups g ON u.group_id = g.id';

        // Test ResultSetMapping.
        $rsm = new ResultSetMapping();

        $rsm->addEntityResult('MyNamespace:DDC2256User', 'u');
        $rsm->addFieldResult('u', 'id', 'id');
        $rsm->addFieldResult('u', 'name', 'name');

        $rsm->addJoinedEntityResult('MyNamespace:DDC2256Group', 'g', 'u', 'group');
        $rsm->addFieldResult('g', 'group_id', 'id');
        $rsm->addFieldResult('g', 'group_name', 'name');

        self::assertCount(1, $this->em->createNativeQuery($sql, $rsm)->getResult());

        // Test ResultSetMappingBuilder.
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata('MyNamespace:DDC2256User', 'u');
        $rsm->addJoinedEntityFromClassMetadata('MyNamespace:DDC2256Group', 'g', 'u', 'group', ['id' => 'group_id', 'name' => 'group_name']);

        self::assertCount(1, $this->em->createNativeQuery($sql, $rsm)->getResult());
    }
}

/**
 * @ORM\Entity
 * @ORM\Table(name="ddc2256_users")
 */
class DDC2256User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public $name;

    /**
     * @ORM\ManyToOne(targetEntity="DDC2256Group", inversedBy="users")A
     * @ORM\JoinColumn(name="group_id")
     */
    public $group;
}

/**
 * @ORM\Entity
 * @ORM\Table(name="ddc2256_groups")
 */
class DDC2256Group
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @ORM\Column(type="string")
     */
    public $name;

    /**
     * @ORM\OneToMany(targetEntity="DDC2256User", mappedBy="group")
     */
    public $users;

    public function __construct()
    {
        $this->users = new \Doctrine\Common\Collections\ArrayCollection();
    }
}


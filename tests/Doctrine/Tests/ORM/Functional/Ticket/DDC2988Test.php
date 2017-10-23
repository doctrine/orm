<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @group DDC-2988
 */
class DDC2988Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected $groups;

    protected function setUp()
    {
        parent::setUp();
        try {
            $this->_schemaTool->createSchema([
                $this->_em->getClassMetadata(DDC2988User::class),
                $this->_em->getClassMetadata(DDC2988Group::class),
            ]);
        } catch (\Exception $e) {
            return;
        }

        $group    = new DDC2988Group();
        $this->_em->persist($group);

        $user           = new DDC2988User();
        $user->groups[] = $group;
        $this->_em->persist($user);

        $this->_em->flush();
        $this->_em->clear();
    }


    public function testManyToManyFindBy()
    {
        $userRepository  = $this->_em->getRepository(DDC2988User::class);
        $groupRepository = $this->_em->getRepository(DDC2988Group::class);
        $groups          = $groupRepository->findAll();
        $result          = $userRepository->findBy(array('groups' => $groups));
    }
}

/** @Entity */
class DDC2988User
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;

    /** @ManyToMany(targetEntity="DDC2988Group")
     * @JoinTable(name="users_to_groups",
     *      joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    public $groups;

    public function __contruct()
    {
        $this->groups = new ArrayCollection();
    }
}

/** @Entity */
class DDC2988Group
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}

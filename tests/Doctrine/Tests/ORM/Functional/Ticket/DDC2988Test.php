<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\JoinColumns;
use Doctrine\ORM\Mapping\JoinTable;

/**
 * @group DDC-2988
 */
class DDC2988Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    protected $groups;

    /**
     * {@inheritDoc}
     */
    protected function setup()
    {
        parent::setup();

        try {
            $this->_schemaTool->createSchema(array(
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2988User'),
                $this->_em->getClassMetadata(__NAMESPACE__ . '\DDC2988Group'),
            ));
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
        $userRepository  = $this->_em->getRepository(__NAMESPACE__ . '\DDC2988User');
        $groupRepository = $this->_em->getRepository(__NAMESPACE__ . '\DDC2988Group');
        $groups          = $groupRepository->findAll();
        $result          = $userRepository->findBy(array('groups' => $groups));
    }
}

/** @Entity  @Table(name="ddc_2988_user") */
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

/** @Entity  @Table(name="ddc_2988_group") */
class DDC2988Group
{
    /** @Id @Column(type="integer") @GeneratedValue */
    public $id;
}

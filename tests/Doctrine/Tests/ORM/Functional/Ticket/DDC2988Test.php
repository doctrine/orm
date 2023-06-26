<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Tests\OrmFunctionalTestCase;

/**
 * @group DDC-2988
 */
class DDC2988Test extends OrmFunctionalTestCase
{
    public function testManyToManyFindBy(): void
    {
        $this->_schemaTool->createSchema([
            $this->_em->getClassMetadata(DDC2988User::class),
            $this->_em->getClassMetadata(DDC2988Group::class),
        ]);

        $group = new DDC2988Group();
        $this->_em->persist($group);

        $user           = new DDC2988User();
        $user->groups[] = $group;
        $this->_em->persist($user);

        $this->_em->flush();
        $this->_em->clear();

        $userRepository  = $this->_em->getRepository(DDC2988User::class);
        $groupRepository = $this->_em->getRepository(DDC2988Group::class);
        $groups          = $groupRepository->findAll();

        $userRepository->findBy(['groups' => $groups]);
    }
}

/**
 * @ORM\Entity
 */
class DDC2988User
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\ManyToMany(targetEntity="DDC2988Group")
     * @ORM\JoinTable(name="users_to_groups",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     * )
     */
    public $groups;

    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }
}

/**
 * @ORM\Entity
 */
class DDC2988Group
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     *
     * @var int
     */
    public $id;
}

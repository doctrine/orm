<?php

namespace Doctrine\Tests\ORM\Functional\Ticket;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;

/**
 * @group DDC-3343
 */
class DDC3343Test extends \Doctrine\Tests\OrmFunctionalTestCase
{
    public function testEntityNotDeletedWhenRemovedFromExtraLazyAssociation()
    {
        $this->_schemaTool->createSchema(array(
            $this->_em->getClassMetadata(DDC3343User::CLASSNAME),
            $this->_em->getClassMetadata(DDC3343Group::CLASSNAME),
        ));

        // Save a group and an associated user (in an extra lazy association)
        $group = new DDC3343Group();
        $user  = new DDC3343User();

        $group->users->add($user);

        $this->_em->persist($group);
        $this->_em->persist($user);
        $this->_em->flush();

        // Fetch the group and the user again and remove the user from the collection.
        $this->_em->clear();

        $group = $this->_em->find(DDC3343Group::CLASSNAME, $group->id);
        $user  = $this->_em->find(DDC3343User::CLASSNAME, $user->id);

        $group->users->removeElement($user);

        // Even though the collection is extra lazy, the user should not have been deleted.
        $this->_em->clear();

        /* @var $user DDC3343User */
        $user = $this->_em->find(DDC3343User::CLASSNAME, $user->id);
        $this->assertInstanceOf(DDC3343User::CLASSNAME, $user);

        $this->assertNull($user->group);
    }
}

/**
 * @Entity
 */
class DDC3343User
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @ManyToOne(targetEntity="DDC3343Group", inversedBy="users")
     */
    public $group;
}

/**
 * @Entity
 */
class DDC3343Group
{
    const CLASSNAME = __CLASS__;

    /**
     * @Id
     * @GeneratedValue
     * @Column(type="integer")
     */
    public $id;

    /**
     * @OneToMany(targetEntity="DDC3343User", mappedBy="group", fetch="EXTRA_LAZY")
     */
    public $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }
}

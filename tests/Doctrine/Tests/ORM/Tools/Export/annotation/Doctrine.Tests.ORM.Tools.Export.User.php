<?php

namespace Doctrine\Tests\ORM\Tools\Export;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(name="cms_users",options={"engine"="MyISAM","foo"={"bar"="baz"}})
 */
class User
{
    /** @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue(strategy="AUTO") */
    public $id;

    /**
     * @ORM\Column(length=50, nullable=true, unique=true)
     */
    public $name;

    /**
     * @ORM\Column(name="user_email", columnDefinition="CHAR(32) NOT NULL")
     */
    public $email;

    /**
     * @ORM\Column(type="integer", options={"unsigned"=true})
     */
    public $age;

    /**
     * @ORM\OneToOne(targetEntity="Doctrine\Tests\ORM\Tools\Export\Address", inversedBy="user", cascade={"persist"}, orphanRemoval=true, fetch="EAGER")
     * @ORM\JoinColumn(name="address_id", onDelete="CASCADE")
     */
    public $address;

    /**
     * @ORM\ManyToOne(targetEntity="Doctrine\Tests\ORM\Tools\Export\Group")
     */
    public $mainGroup;

    /**
     *
     * @ORM\OneToMany(targetEntity="Doctrine\Tests\ORM\Tools\Export\Phonenumber", mappedBy="user", cascade={"persist", "merge"}, orphanRemoval=true)
     * @ORM\OrderBy({"number"="ASC"})
     */
    public $phonenumbers;

    /**
     * @ORM\ManyToMany(targetEntity="Doctrine\Tests\ORM\Tools\Export\Group", cascade={"all"}, fetch="EXTRA_LAZY")
     * @ORM\JoinTable(name="cms_users_groups",
     *    joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, unique=false)},
     *    inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id", columnDefinition="INT NULL")}
     * )
     */
    public $groups;

    /**
     * @ORM\PrePersist
     */
    public function doStuffOnPrePersist()
    {
    }

    /**
     * @ORM\PrePersist
     */
    public function doOtherStuffOnPrePersistToo()
    {
    }

    /**
     * @ORM\PostPersist
     */
    public function doStuffOnPostPersist()
    {
    }
}

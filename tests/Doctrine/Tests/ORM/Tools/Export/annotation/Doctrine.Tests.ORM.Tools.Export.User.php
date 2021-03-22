<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Tools\Export;

use Doctrine\Common\Collections\Collection;

/**
 * @Entity
 * @HasLifecycleCallbacks
 * @EntityListeners({
 *     Doctrine\Tests\ORM\Tools\Export\UserListener::class,
 *     Doctrine\Tests\ORM\Tools\Export\GroupListener::class,
 *     Doctrine\Tests\ORM\Tools\Export\AddressListener::class
 * })
 * @Table(name="cms_users",options={"engine"="MyISAM","foo"={"bar"="baz"}})
 */
class User
{
    /**
     * @var int
     * @Id
     * @Column(type="integer") @generatedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var string
     * @Column(length=50, nullable=true, unique=true)
     */
    public $name;

    /**
     * @var string
     * @Column(name="user_email", columnDefinition="CHAR(32) NOT NULL")
     */
    public $email;

    /**
     * @var int
     * @Column(type="integer", options={"unsigned"=true})
     */
    public $age;

    /**
     * @var Address
     * @OneToOne(targetEntity="Doctrine\Tests\ORM\Tools\Export\Address", inversedBy="user", cascade={"persist"}, orphanRemoval=true, fetch="EAGER")
     * @JoinColumn(name="address_id", onDelete="CASCADE")
     */
    public $address;

    /**
     * @var Group
     * @ManyToOne(targetEntity="Doctrine\Tests\ORM\Tools\Export\Group")
     */
    public $mainGroup;

    /**
     * @psalm-var Collection<int, Phonenumber>
     * @OneToMany(targetEntity="Doctrine\Tests\ORM\Tools\Export\Phonenumber", mappedBy="user", cascade={"persist", "merge"}, orphanRemoval=true)
     * @OrderBy({"number"="ASC"})
     */
    public $phonenumbers;

    /**
     * @psalm-var Collection<int, Group>
     * @ManyToMany(targetEntity="Doctrine\Tests\ORM\Tools\Export\Group", cascade={"all"}, fetch="EXTRA_LAZY")
     * @JoinTable(name="cms_users_groups",
     *    joinColumns={@JoinColumn(name="user_id", referencedColumnName="id", nullable=false, unique=false)},
     *    inverseJoinColumns={@JoinColumn(name="group_id", referencedColumnName="id", columnDefinition="INT NULL")}
     * )
     */
    public $groups;

    /**
     * @PrePersist
     */
    public function doStuffOnPrePersist(): void
    {
    }

    /**
     * @PrePersist
     */
    public function doOtherStuffOnPrePersistToo(): void
    {
    }

    /**
     * @PostPersist
     */
    public function doStuffOnPostPersist(): void
    {
    }
}

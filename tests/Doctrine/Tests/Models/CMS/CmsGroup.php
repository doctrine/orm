<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use IteratorAggregate;
use Traversable;

/**
 * Description of CmsGroup
 *
 * @Entity
 * @Table(name="cms_groups")
 */
class CmsGroup implements IteratorAggregate
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;
    /** @Column(length=50) */
    public $name;
    /** @ManyToMany(targetEntity="CmsUser", mappedBy="groups") */
    public $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function setName($name): void
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function addUser(CmsUser $user): void
    {
        $this->users[] = $user;
    }

    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @return ArrayCollection|Traversable
     */
    public function getIterator()
    {
        return $this->getUsers();
    }
}

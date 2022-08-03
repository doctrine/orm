<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\CMS;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    public $id;

    /**
     * @var string
     * @Column(length=50)
     */
    public $name;

    /**
     * @psalm-var Collection<int, CmsUser>
     * @ManyToMany(targetEntity="CmsUser", mappedBy="groups")
     */
    public $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function addUser(CmsUser $user): void
    {
        $this->users[] = $user;
    }

    /**
     * @psalm-return Collection<int, CmsUser>
     */
    public function getUsers(): Collection
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

<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Quote;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="quote-group")
 */
class Group
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", name="group-id")
     */
    public $id;

    /** @ORM\Column(name="group-name") */
    public $name;

    /**
     * @ORM\ManyToOne(targetEntity=Group::class, cascade={"persist"})
     * @ORM\JoinColumn(name="parent-id", referencedColumnName="group-id")
     *
     * @var Group
     */
    public $parent;

    /** @ORM\ManyToMany(targetEntity=User::class, mappedBy="groups") */
    public $users;

    public function __construct($name = null, ?Group $parent = null)
    {
        $this->name   = $name;
        $this->parent = $parent;
    }
}

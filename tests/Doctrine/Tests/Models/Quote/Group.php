<?php

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

    /**
     * @ORM\Column(name="group-name")
     */
    public $name;

    /**
     * @var Group
     *
     * @ORM\ManyToOne(targetEntity="Group", cascade={"persist"})
     * @ORM\JoinColumn(name="parent-id", referencedColumnName="group-id")
     */
    public $parent;

    /**
     * @ORM\ManyToMany(targetEntity="User", mappedBy="groups")
     */
    public $users;

    public function __construct($name = null, Group $parent =  null)
    {
        $this->name     = $name;
        $this->parent   = $parent;
    }
}

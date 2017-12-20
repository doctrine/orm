<?php

namespace Doctrine\Tests\Models\DDC3476;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="users", options={"engine"="MyISAM", "collate"="utf8_general_ci"})
 */
class DDC3476User
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    public $id;

    /**
     * @var \Doctrine\Common\Collections\Collection|DDC3476Group[]
     *
     * @ManyToMany(targetEntity="DDC3476Group")
     * @JoinTable(name="user_groups",
     *     joinColumns={@JoinColumn(name="user_id", referencedColumnName="id")},
     *     inverseJoinColumns={@JoinColumn(name="group_id", referencedColumnName="id")}
     *     )
     */
    private $groups;

    /**
     * DDC3476User constructor.
     */
    public function __construct()
    {
        $this->groups = new ArrayCollection();
    }
}

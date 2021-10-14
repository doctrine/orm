<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Issue9124;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;

/**
 * @Entity
 * @Table(name="issue9124_groups")
 */
class Issue9124Group
{
    /**
     * @var int
     * @ORM\Id()
     * @Column(type="integer")
     * @ORM\GeneratedValue()
     */
    public $id;

    /**
     * @ORM\ManyToMany(targetEntity="Issue9124Item")
     * @var Issue9124Item[]
     */
    public $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }
}

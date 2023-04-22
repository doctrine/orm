<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH7717;

use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="gh7717_parents")
 */
class GH7717Parent
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    public ?int $id = null;

    /**
     * @ORM\ManyToMany(targetEntity="GH7717Child", cascade={"persist"})
     *
     * @var Selectable<int, GH7717Child>
     */
    public Selectable $children;
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH7717;

use Doctrine\Common\Collections\Selectable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'gh7717_parents')]
class GH7717Parent
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue]
    public int|null $id = null;

    /** @var Selectable<int, GH7717Child> */
    #[ORM\ManyToMany(targetEntity: GH7717Child::class, cascade: ['persist'])]
    public Selectable $children;
}

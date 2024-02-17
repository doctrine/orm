<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11149;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table('gh11149_regular_product')]
class RegularProduct
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    public int $id;

    #[ORM\OneToMany(
        targetEntity: RegularProductTranslation::class,
        mappedBy: 'product',
        indexBy: 'locale_code',
    )]
    public Collection $translations;

    public function __construct(int $id)
    {
        $this->id           = $id;
        $this->translations = new ArrayCollection();
    }
}

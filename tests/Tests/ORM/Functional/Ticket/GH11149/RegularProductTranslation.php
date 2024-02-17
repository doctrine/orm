<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11149;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table('gh11149_regular_product_translation')]
class RegularProductTranslation
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: RegularProduct::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false)]
    public RegularProduct $product;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Locale::class)]
    #[ORM\JoinColumn(name: 'locale_code', referencedColumnName: 'code', nullable: false)]
    public Locale $locale;

    public function __construct(RegularProduct $product, Locale $locale)
    {
        $this->product = $product;
        $this->locale  = $locale;
    }
}

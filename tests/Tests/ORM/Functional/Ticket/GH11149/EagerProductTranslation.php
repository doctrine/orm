<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11149;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table('gh11149_eager_product_translation')]
class EagerProductTranslation
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: EagerProduct::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false)]
    public EagerProduct $product;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Locale::class)]
    #[ORM\JoinColumn(name: 'locale_code', referencedColumnName: 'code', nullable: false)]
    public Locale $locale;

    public function __construct(EagerProduct $product, Locale $locale)
    {
        $this->product = $product;
        $this->locale  = $locale;
    }
}

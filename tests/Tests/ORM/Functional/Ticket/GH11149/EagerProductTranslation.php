<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11149;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table('gh11149_eager_product_translation')]
class EagerProductTranslation
{
    #[ORM\Id]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(inversedBy: 'translations')]
    #[ORM\JoinColumn(nullable: false)]
    public EagerProduct $product;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'locale_code', referencedColumnName: 'code', nullable: false)]
    public Locale $locale;

    public function __construct(int $id, EagerProduct $product, Locale $locale)
    {
        $this->id      = $id;
        $this->product = $product;
        $this->locale  = $locale;
    }
}

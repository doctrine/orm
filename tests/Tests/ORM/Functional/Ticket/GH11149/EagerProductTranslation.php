<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11149;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("gh11149_eager_product_translation")
 */
class EagerProductTranslation
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=EagerProduct::class, inversedBy="translations")
     * @ORM\JoinColumn(nullable=false)
     *
     * @var EagerProduct
     */
    public $product;

    /**
     * @ORM\ManyToOne(targetEntity=Locale::class)
     * @ORM\JoinColumn(name="locale_code", referencedColumnName="code", nullable=false)
     *
     * @var Locale
     */
    public $locale;

    public function __construct($id, EagerProduct $product, Locale $locale)
    {
        $this->id      = $id;
        $this->product = $product;
        $this->locale  = $locale;
    }
}

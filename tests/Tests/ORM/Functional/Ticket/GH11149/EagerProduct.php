<?php

declare(strict_types=1);

namespace Doctrine\Tests\ORM\Functional\Ticket\GH11149;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table("gh11149_eager_product")
 */
class EagerProduct
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    public $id;

    /**
     * @ORM\OneToMany(
     *     targetEntity=EagerProductTranslation::class,
     *     mappedBy="product",
     *     fetch="EAGER",
     *     indexBy="locale_code"
     * )
     *
     * @var Collection<string, EagerProductTranslation>
     */
    public $translations;

    public function __construct(int $id)
    {
        $this->id           = $id;
        $this->translations = new ArrayCollection();
    }
}

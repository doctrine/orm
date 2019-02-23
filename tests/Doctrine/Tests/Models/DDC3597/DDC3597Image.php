<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3597;

use Doctrine\ORM\Annotation as ORM;
use Doctrine\Tests\Models\DDC3597\Embeddable\DDC3597Dimension;

/**
 * Description of Image
 *
 * @ORM\Entity
 */
class DDC3597Image extends DDC3597Media
{
    /**
     * @ORM\Embedded(class = DDC3597Dimension::class, columnPrefix = false)
     *
     * @var DDC3597Dimension
     */
    private $dimension;

    /**
     * @param string $distributionHash
     */
    public function __construct($distributionHash)
    {
        parent::__construct($distributionHash);
        $this->dimension = new DDC3597Dimension();
    }

    /**
     * @return DDC3597Dimension
     */
    public function getDimension()
    {
        return $this->dimension;
    }
}

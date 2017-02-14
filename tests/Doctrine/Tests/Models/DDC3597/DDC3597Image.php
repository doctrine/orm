<?php

namespace Doctrine\Tests\Models\DDC3597;

use Doctrine\Tests\Models\DDC3597\Embeddable\DDC3597Dimension;
use Doctrine\ORM\Annotation as ORM;

/**
 * Description of Image
 *
 * @author Volker von Hoesslin <volker.von.hoesslin@empora.com>
 * @ORM\Entity
 */
class DDC3597Image extends DDC3597Media
{
    /**
     * @var DDC3597Dimension
     * @ORM\Embedded(class = "Doctrine\Tests\Models\DDC3597\Embeddable\DDC3597Dimension", columnPrefix = false)
     */
    private $dimension;

    /**
     * @param string $distributionHash
     */
    public function __construct($distributionHash) {
        parent::__construct($distributionHash);
        $this->dimension = new DDC3597Dimension();
    }

    /**
     * @return DDC3597Dimension
     */
    public function getDimension() {
        return $this->dimension;
    }
}

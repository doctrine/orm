<?php

namespace Doctrine\Tests\Models\DDC3597;

use Doctrine\Tests\Models\DDC3597\Embeddable\DDC3597Dimension;

/**
 * Description of Image
 *
 * @author Volker von Hoesslin <volker.von.hoesslin@empora.com>
 * @Entity
 */
class DDC3597Image extends DDC3597Media {

    /**
     * @var DDC3597Dimension
     * @Embedded(class = "Doctrine\Tests\Models\DDC3597\Embeddable\DDC3597Dimension", columnPrefix = false)
     */
    private $dimension;

    /**
     * @param string $distributionHash
     */
    function __construct($distributionHash) {
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

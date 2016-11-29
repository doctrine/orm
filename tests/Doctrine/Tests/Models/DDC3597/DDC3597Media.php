<?php

namespace Doctrine\Tests\Models\DDC3597;

/**
 * Description of Media
 * 
 * @author Volker von Hoesslin <volker.von.hoesslin@empora.com>
 * @Entity
 */
abstract class DDC3597Media extends DDC3597Root {

    const CLASSNAME = __CLASS__;

    /**
     * @var string
     *
     * @Column
     */
    private $distributionHash;

    /**
     * @var integer
     *
     * @Column
     */
    private $size = 0;

    /**
     * @var string
     * @Column
     */
    private $format;

    function __construct($distributionHash) {
        $this->distributionHash = $distributionHash;
    }

    /**
     * @return string
     */
    public function getDistributionHash() {
        return $this->distributionHash;
    }

    /**
     * @return int
     */
    public function getSize() {
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize($size) {
        $this->size = $size;
    }

    /**
     * @return string
     */
    public function getFormat() {
        return $this->format;
    }

    /**
     * @param string $format
     */
    public function setFormat($format) {
        $this->format = $format;
    }



}
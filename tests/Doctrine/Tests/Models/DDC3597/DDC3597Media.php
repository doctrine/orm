<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3597;

use Doctrine\ORM\Annotation as ORM;

/**
 * Description of Media
 *
 * @ORM\Entity
 */
abstract class DDC3597Media extends DDC3597Root
{
    /**
     * @ORM\Column
     *
     * @var string
     */
    private $distributionHash;

    /**
     * @ORM\Column
     *
     * @var int
     */
    private $size = 0;

    /**
     * @ORM\Column
     *
     * @var string
     */
    private $format;

    public function __construct($distributionHash)
    {
        $this->distributionHash = $distributionHash;
    }

    /**
     * @return string
     */
    public function getDistributionHash()
    {
        return $this->distributionHash;
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize($size)
    {
        $this->size = $size;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }
}

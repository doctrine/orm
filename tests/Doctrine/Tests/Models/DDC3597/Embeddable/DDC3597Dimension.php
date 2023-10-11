<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3597\Embeddable;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Embeddable;

/**
 * Description of DDC3597Dimension
 */
#[Embeddable]
class DDC3597Dimension
{
    #[Column(type: 'integer', name: 'width')]
    private int $width;

    #[Column(type: 'integer', name: 'height')]
    private int $height;

    public function __construct($width = 0, $height = 0)
    {
        $this->setWidth($width);
        $this->setHeight($height);
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function setWidth(int $width): void
    {
        $this->width = (int) $width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function setHeight(int $height): void
    {
        $this->height = (int) $height;
    }
}

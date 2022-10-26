<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DDC3597;

use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;

/**
 * Description of Media
 */
#[Entity]
abstract class DDC3597Media extends DDC3597Root
{
    #[Column]
    private int $size = 0;

    #[Column]
    private string|null $format = null;

    public function __construct(
        #[Column]
        private string $distributionHash,
    ) {
    }

    public function getDistributionHash(): string
    {
        return $this->distributionHash;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): void
    {
        $this->format = $format;
    }
}

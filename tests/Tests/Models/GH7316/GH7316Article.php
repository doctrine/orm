<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH7316;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class GH7316Article
{
    /** @phpstan-var Collection<int, mixed> */
    private $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }
}

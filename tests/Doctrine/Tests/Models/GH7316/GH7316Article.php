<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\GH7316;

use Doctrine\Common\Collections\ArrayCollection;

class GH7316Article
{
    private $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }
}

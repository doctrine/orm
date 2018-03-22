<?php

namespace Doctrine\Tests\Models\GH7141;

use Doctrine\Common\Collections\ArrayCollection;

class GH7141Article
{
    private $tags;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
    }
}

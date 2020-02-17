<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

class CacheMetadata
{
    /** @var string */
    private $usage;

    /** @var string */
    private $region;

    public function __construct(string $usage, string $region)
    {
        $this->usage  = $usage;
        $this->region = $region;
    }

    public function getUsage() : string
    {
        return $this->usage;
    }

    public function getRegion() : string
    {
        return $this->region;
    }
}

<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Project;

class ProjectInvalidMapping
{
    /** @var string */
    private $id;

    /** @var string */
    private $name;

    public function __construct(string $id, string $name)
    {
        $this->id   = $id;
        $this->name = $name;
    }
}

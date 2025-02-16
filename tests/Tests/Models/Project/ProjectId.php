<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Project;

class ProjectId
{
    /** @var string */
    private $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }
}

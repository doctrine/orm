<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\Project;

final class ProjectName
{
    /** @var string */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

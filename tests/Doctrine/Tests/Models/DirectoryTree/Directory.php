<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DirectoryTree;

/**
 * @Entity
 */
class Directory extends AbstractContentItem
{
    /** @Column(type="string") */
    protected $path;

    public function setPath($path): void
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }
}

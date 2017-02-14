<?php

namespace Doctrine\Tests\Models\DirectoryTree;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 */
class Directory extends AbstractContentItem
{
    /**
     * @ORM\Column(type="string")
     */
    protected $path;

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getPath()
    {
        return $this->path;
    }
}

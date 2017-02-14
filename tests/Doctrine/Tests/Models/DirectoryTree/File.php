<?php

namespace Doctrine\Tests\Models\DirectoryTree;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="file")
 */
class File extends AbstractContentItem
{
    /** @ORM\Column(type="string") */
    protected $extension = "html";

    public function getExtension()
    {
        return $this->extension;
    }

    public function setExtension($ext)
    {
        $this->extension = $ext;
    }
}

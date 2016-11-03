<?php

namespace Doctrine\Tests\Models\DirectoryTree;

/**
 * @Entity
 * @Table(name="`file`")
 */
class File extends AbstractContentItem
{
    const CLASSNAME = __CLASS__;

    /** @Column(type="string") */
    protected $extension = "html";

    public function __construct(Directory $parent = null)
    {
        parent::__construct($parent);
    }

    public function getExtension()
    {
        return $this->extension;
    }

    public function setExtension($ext)
    {
        $this->extension = $ext;
    }
}

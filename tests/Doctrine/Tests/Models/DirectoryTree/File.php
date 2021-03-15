<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DirectoryTree;

/**
 * @Entity
 * @Table(name="`file`")
 */
class File extends AbstractContentItem
{
    /**
     * @var string
     * @Column(type="string")
     */
    protected $extension = 'html';

    public function __construct(?Directory $parent = null)
    {
        parent::__construct($parent);
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $ext): void
    {
        $this->extension = $ext;
    }
}

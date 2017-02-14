<?php

namespace Doctrine\Tests\Models\DirectoryTree;

use Doctrine\ORM\Annotation as ORM;

/**
 * @ORM\MappedSuperClass
 */
abstract class AbstractContentItem
{
    /**
     * @ORM\Id @ORM\Column(type="integer") @ORM\GeneratedValue
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Directory")
     */
    protected $parentDirectory;

    /** @ORM\Column(type="string") */
    protected $name;

    /**
     * This field is transient and private on purpose
     *
     * @var bool
     */
    private $nodeIsLoaded = false;

    /**
     * This field is transient on purpose
     *
     * @var mixed
     */
    public static $fileSystem;

    public function __construct(Directory $parentDir = null)
    {
        $this->parentDirectory = $parentDir;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getParent()
    {
        return $this->parentDirectory;
    }

    /**
     * @return bool
     */
    public function getNodeIsLoaded()
    {
        return $this->nodeIsLoaded;
    }

    /**
     * @param bool $nodeIsLoaded
     */
    public function setNodeIsLoaded($nodeIsLoaded)
    {
        $this->nodeIsLoaded = (bool) $nodeIsLoaded;
    }
}

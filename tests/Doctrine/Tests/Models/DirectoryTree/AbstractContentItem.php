<?php

declare(strict_types=1);

namespace Doctrine\Tests\Models\DirectoryTree;

/**
 * @MappedSuperclass
 */
abstract class AbstractContentItem
{
    /**
     * @var int
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    private $id;

    /**
     * @var Directory
     * @ManyToOne(targetEntity="Directory")
     */
    protected $parentDirectory;

    /**
     * @var string
     * @column(type="string")
     */
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

    public function __construct(?Directory $parentDir = null)
    {
        $this->parentDirectory = $parentDir;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParent(): Directory
    {
        return $this->parentDirectory;
    }

    public function getNodeIsLoaded(): bool
    {
        return $this->nodeIsLoaded;
    }

    public function setNodeIsLoaded(bool $nodeIsLoaded): void
    {
        $this->nodeIsLoaded = (bool) $nodeIsLoaded;
    }
}

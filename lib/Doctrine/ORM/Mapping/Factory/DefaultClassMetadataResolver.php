<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Factory;

class DefaultClassMetadataResolver implements ClassMetadataResolver
{
    /**
     * Marker for ClassMetadata class names.
     *
     * @var string
     */
    public const MARKER = '__CG__';

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $directory;

    /**
     * DefaultClassMetadataResolver constructor.
     */
    public function __construct(string $namespace, string $directory)
    {
        $this->namespace = ltrim($namespace, '\\');
        $this->directory = rtrim($this->directory, DIRECTORY_SEPARATOR);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveMetadataClassName(string $className) : string
    {
        return sprintf('%s\%s\%s', $this->namespace, self::MARKER, ltrim($className, '\\'));
    }

    /**
     * {@inheritdoc}
     */
    public function resolveMetadataClassPath(string $className) : string
    {
        return sprintf('%s/%s/%s.php', $this->directory, self::MARKER, str_replace('\\', '.', $className));
    }
}

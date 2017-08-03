<?php


declare(strict_types=1);

namespace Doctrine\ORM\Proxy\Factory;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Proxy\Proxy;

class DefaultProxyResolver implements ProxyResolver
{
    /**
     * Marker for Proxy class names.
     *
     * @var string
     */
    const MARKER = '__CG__';

    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $directory;

    /**
     * DefaultProxyResolver constructor.
     *
     * @param string $namespace
     * @param string $directory
     */
    public function __construct(string $namespace, string $directory)
    {
        $this->namespace = ltrim($namespace, '\\');
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);
    }

    /**
     * {@inheritdoc}
     */
    public function resolveProxyClassName(string $className) : string
    {
        return sprintf('%s\%s\%s', $this->namespace, self::MARKER, ltrim($className, '\\'));
    }

    /**
     * {@inheritdoc}
     */
    public function resolveProxyClassPath(string $className) : string
    {
        return sprintf('%s/%s/%s.php', $this->directory, self::MARKER, str_replace('\\', '.', $className));
    }
}

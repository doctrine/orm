<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Cache;

use Composer\InstalledVersions;
use Doctrine\Common\Cache\Psr6\CacheItem;
use Doctrine\Common\Cache\Psr6\TypedCacheItem;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

use function array_map;
use function dirname;
use function file_exists;
use function file_put_contents;
use function filemtime;
use function get_class;
use function gettype;
use function in_array;
use function is_dir;
use function is_object;
use function mkdir;
use function opcache_invalidate;
use function serialize;
use function sprintf;
use function str_contains;
use function str_replace;
use function var_export;

class PhpMetadataCache implements CacheItemPoolInterface
{
    /** @var string */
    private $cacheDir;

    /** @var bool */
    private $debug;

    /** @var class-string */
    private $itemClass;

    public function __construct(string $cacheDir, bool $debug = false)
    {
        $this->cacheDir  = $cacheDir;
        $this->debug     = $debug;
        $this->itemClass = (PHP_VERSION_ID >= 80000)
            ? TypedCacheItem::class
            : CacheItem::class;
    }

    public function save(CacheItemInterface $item): bool
    {
        $fileName = $this->wiggle($item->getKey());

        $this->assertValidFilePath($fileName);

        if (! is_dir($this->cacheDir) && is_dir(dirname($this->cacheDir))) {
            @mkdir($this->cacheDir, 0755);
        }

        $metadata = $item->get();

        if (! ($metadata instanceof ClassMetadataInfo)) {
            throw new \InvalidArgumentException(sprintf(
                'PhpMetadataCache only works for Doctrine\ORM\Mapping\ClassMetadataInfo instances, %s given.',
                is_object($metadata) ? get_class($metadata) : gettype($metadata)
            ));
        }

        $class = new \ReflectionClass(ClassMetadataInfo::class);

        $namingStrategy = serialize($metadata->getNamingStrategy());

        $content = "<?php\n\n\$classMetadata = new \Doctrine\ORM\Mapping\ClassMetadata('" . $metadata->name . "', unserialize('" . $namingStrategy . "'));\n";

        $skipProperties      = ['reflClass', 'reflFields', 'namingStrategy'];
        $serializeProperties = ['idGenerator', 'instantiator'];

        foreach ($class->getProperties() as $property) {
            $key = $property->getName();
            $property->setAccessible(true);
            $value = $property->getValue($metadata);

            if (in_array($key, $serializeProperties)) {
                $content .= "\$classMetadata->$key = unserialize('" . serialize($value) . "');\n";
            } elseif (in_array($key, $skipProperties)) {
                continue;
            } else {
                $content .= "\$classMetadata->$key = " . var_export($value, true) . ";\n";
            }
        }

        $content .= "return array(\$classMetadata, '" . InstalledVersions::getVersion('doctrine/orm') . "');";

        file_put_contents($this->cacheDir . '/' . $fileName . '.php', $content);
        if (extension_loaded('opcache')) {
            opcache_invalidate($this->cacheDir . '/' . $fileName . '.php');
        }

        return true;
    }

    /** @return ?ClassMetadataInfo */
    private function fetch(string $key)
    {
        $fileName = $this->wiggle($key);

        $this->assertValidFilePath($fileName);

        $file = $this->cacheDir . '/' . $fileName . '.php';

        if (! file_exists($file)) {
            return null;
        }

        [$data, $version] = require $file;

        if ($version !== InstalledVersions::getVersion('doctrine/orm')) {
            return null;
        }

        if ($this->debug) {
            $reflClass = new \ReflectionClass($data->name);

            if (filemtime($file) < filemtime($reflClass->getFilename())) {
                return null;
            }
        }

        return $data;
    }

    public function getItem(string $key): CacheItemInterface
    {
        $value = $this->fetch($key);

        if ($value instanceof ClassMetadataInfo) {
            return new $this->itemClass($key, $value, true);
        }

        return new $this->itemClass($key, null, false);
    }

    /** @param string[] $keys */
    public function getItems(array $keys = []): iterable
    {
        return array_map(function (string $key) {
            return $this->getItem($key);
        }, $keys);
    }

    public function hasItem(string $key): bool
    {
        $data = $this->fetch($key);

        return $data !== null;
    }

    public function clear(): bool
    {
        return true;
    }

    public function deleteItem(string $key): bool
    {
        return true;
    }

    /** @param string[] $keys */
    public function deleteItems(array $keys): bool
    {
        return true;
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return true;
    }

    public function commit(): bool
    {
        return true;
    }

    private function wiggle(string $id): string
    {
        return str_replace(['\\', '$'], ['.', ''], $id);
    }

    private function assertValidFilePath(string $id): void
    {
        if (str_contains($id, '..')) {
            throw new \RuntimeException('Invalid file path given, contains double dots.');
        }
    }
}

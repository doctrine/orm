<?php

declare(strict_types=1);

namespace Doctrine\ORM\Configuration;

use Doctrine\ORM\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Mapping\Factory\AbstractClassMetadataFactory;
use Doctrine\ORM\Mapping\Factory\ClassMetadataResolver;
use Doctrine\ORM\Mapping\Factory\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\Factory\NamingStrategy;
use const DIRECTORY_SEPARATOR;
use function ltrim;
use function rtrim;

/**
 * Configuration container for class metadata options of Doctrine.
 */
class MetadataConfiguration
{
    /** @var string */
    private $namespace;

    /** @var string */
    private $directory;

    /** @var ClassMetadataResolver */
    private $resolver;

    /** @var MappingDriver */
    private $mappingDriver;

    /** @var NamingStrategy */
    private $namingStrategy;

    /** @var int */
    private $autoGenerate = AbstractClassMetadataFactory::AUTOGENERATE_ALWAYS;

    public function getNamespace() : string
    {
        return $this->namespace;
    }

    public function setNamespace(string $namespace)
    {
        $this->namespace = ltrim($namespace, '\\');
    }

    public function getDirectory() : string
    {
        return $this->directory;
    }

    public function setDirectory(string $directory)
    {
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);
    }

    public function getResolver() : ClassMetadataResolver
    {
        return $this->resolver;
    }

    public function setResolver(ClassMetadataResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    public function getMappingDriver() : MappingDriver
    {
        return $this->mappingDriver;
    }

    public function setMappingDriver(MappingDriver $mappingDriver)
    {
        $this->mappingDriver = $mappingDriver;
    }

    public function getNamingStrategy() : NamingStrategy
    {
        if (! $this->namingStrategy) {
            $this->namingStrategy = new DefaultNamingStrategy();
        }

        return $this->namingStrategy;
    }

    public function setNamingStrategy(NamingStrategy $namingStrategy)
    {
        $this->namingStrategy = $namingStrategy;
    }

    public function getAutoGenerate() : int
    {
        return $this->autoGenerate;
    }

    public function setAutoGenerate(int $autoGenerate)
    {
        $this->autoGenerate = $autoGenerate;
    }
}

<?php


declare(strict_types=1);

namespace Doctrine\ORM\Configuration;

use Doctrine\ORM\Mapping\Driver\MappingDriver;
use Doctrine\ORM\Mapping\Factory\AbstractClassMetadataFactory;
use Doctrine\ORM\Mapping\Factory\ClassMetadataResolver;
use Doctrine\ORM\Mapping\Factory\DefaultNamingStrategy;
use Doctrine\ORM\Mapping\Factory\NamingStrategy;

/**
 * Configuration container for class metadata options of Doctrine.
 *
 * @package Doctrine\ORM\Configuration
 * @since 3.0
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 */
class MetadataConfiguration
{
    /**
     * @var string
     */
    private $namespace;

    /**
     * @var string
     */
    private $directory;

    /**
     * @var ClassMetadataResolver
     */
    private $resolver;

    /**
     * @var MappingDriver
     */
    private $mappingDriver;

    /**
     * @var NamingStrategy
     */
    private $namingStrategy;

    /**
     * @var int
     */
    private $autoGenerate = AbstractClassMetadataFactory::AUTOGENERATE_ALWAYS;

    /**
     * @return string
     */
    public function getNamespace() : string
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     */
    public function setNamespace(string $namespace)
    {
        $this->namespace = ltrim($namespace, '\\');
    }

    /**
     * @return string
     */
    public function getDirectory() : string
    {
        return $this->directory;
    }

    /**
     * @param string $directory
     */
    public function setDirectory(string $directory)
    {
        $this->directory = rtrim($directory, DIRECTORY_SEPARATOR);
    }

    /**
     * @return ClassMetadataResolver
     */
    public function getResolver() : ClassMetadataResolver
    {
        return $this->resolver;
    }

    /**
     * @param ClassMetadataResolver $resolver
     */
    public function setResolver(ClassMetadataResolver $resolver)
    {
        $this->resolver = $resolver;
    }

    /**
     * @return MappingDriver
     */
    public function getMappingDriver() : MappingDriver
    {
        return $this->mappingDriver;
    }

    /**
     * @param MappingDriver $mappingDriver
     */
    public function setMappingDriver(MappingDriver $mappingDriver)
    {
        $this->mappingDriver = $mappingDriver;
    }

    /**
     * @return NamingStrategy
     */
    public function getNamingStrategy() : NamingStrategy
    {
        if (! $this->namingStrategy) {
            $this->namingStrategy = new DefaultNamingStrategy();
        }

        return $this->namingStrategy;
    }

    /**
     * @param NamingStrategy $namingStrategy
     */
    public function setNamingStrategy(NamingStrategy $namingStrategy)
    {
        $this->namingStrategy = $namingStrategy;
    }

    /**
     * @todo guilhermeblanco Get rid of this method and associated constants. Use the generator strategy instead.
     *
     * @return int
     */
    public function getAutoGenerate() : int
    {
        return $this->autoGenerate;
    }

    /**
     * @param int $autoGenerate
     */
    public function setAutoGenerate(int $autoGenerate)
    {
        $this->autoGenerate = $autoGenerate;
    }
}

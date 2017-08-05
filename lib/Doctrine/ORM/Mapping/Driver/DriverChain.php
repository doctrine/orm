<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;

/**
 * The DriverChain allows you to add multiple other mapping drivers for
 * certain namespaces.
 *
 * @since  2.2
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 */
class DriverChain implements MappingDriver
{
    /**
     * The default driver.
     *
     * @var MappingDriver|null
     */
    private $defaultDriver;

    /**
     * @var array
     */
    private $drivers = [];

    /**
     * Gets the default driver.
     *
     * @return MappingDriver|null
     */
    public function getDefaultDriver()
    {
        return $this->defaultDriver;
    }

    /**
     * Set the default driver.
     *
     * @param MappingDriver $driver
     *
     * @return void
     */
    public function setDefaultDriver(MappingDriver $driver)
    {
        $this->defaultDriver = $driver;
    }

    /**
     * Adds a nested driver.
     *
     * @param MappingDriver $nestedDriver
     * @param string        $namespace
     *
     * @return void
     */
    public function addDriver(MappingDriver $nestedDriver, $namespace)
    {
        $this->drivers[$namespace] = $nestedDriver;
    }

    /**
     * Gets the array of nested drivers.
     *
     * @return array $drivers
     */
    public function getDrivers()
    {
        return $this->drivers;
    }

    /**
     * {@inheritDoc}
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata)
    {
        /* @var $driver MappingDriver */
        foreach ($this->drivers as $namespace => $driver) {
            if (strpos($className, $namespace) === 0) {
                $driver->loadMetadataForClass($className, $metadata);
                return;
            }
        }

        if (null !== $this->defaultDriver) {
            $this->defaultDriver->loadMetadataForClass($className, $metadata);
            return;
        }

        throw MappingException::classNotFoundInNamespaces($className, array_keys($this->drivers));
    }

    /**
     * {@inheritDoc}
     */
    public function getAllClassNames()
    {
        $classNames = [];
        $driverClasses = [];

        /* @var $driver MappingDriver */
        foreach ($this->drivers AS $namespace => $driver) {
            $oid = spl_object_hash($driver);

            if (!isset($driverClasses[$oid])) {
                $driverClasses[$oid] = $driver->getAllClassNames();
            }

            foreach ($driverClasses[$oid] AS $className) {
                if (strpos($className, $namespace) === 0) {
                    $classNames[$className] = true;
                }
            }
        }

        if (null !== $this->defaultDriver) {
            foreach ($this->defaultDriver->getAllClassNames() as $className) {
                $classNames[$className] = true;
            }
        }

        return array_keys($classNames);
    }

    /**
     * {@inheritDoc}
     */
    public function isTransient($className)
    {
        /* @var $driver MappingDriver */
        foreach ($this->drivers AS $namespace => $driver) {
            if (strpos($className, $namespace) === 0) {
                return $driver->isTransient($className);
            }
        }

        if ($this->defaultDriver !== null) {
            return $this->defaultDriver->isTransient($className);
        }

        return true;
    }
}

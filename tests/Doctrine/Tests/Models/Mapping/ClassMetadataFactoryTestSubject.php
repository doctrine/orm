<?php

namespace Doctrine\Tests\Models\Mapping;

use InvalidArgumentException;
use Doctrine\ORM\Mapping\ClassMetadataFactory;

/**
 * Test subject class with overridden factory method for mocking purposes
 */
class ClassMetadataFactoryTestSubject extends ClassMetadataFactory
{
    private $mockMetadata     = array();
    private $requestedClasses = array();

    /** @override */
    protected function newClassMetadataInstance($className)
    {
        $this->requestedClasses[] = $className;
        if (!isset($this->mockMetadata[$className])) {
            throw new InvalidArgumentException("No mock metadata found for class $className.");
        }
        return $this->mockMetadata[$className];
    }

    public function setMetadataForClass($className, $metadata)
    {
        $this->mockMetadata[$className] = $metadata;
    }

    public function getRequestedClasses()
    {
        return $this->requestedClasses;
    }
}

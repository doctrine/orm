<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\ChangeDetector;

use Doctrine\ORM\Mapping\ClassMetadata;
use InvalidArgumentException;

use function class_exists;
use function gettype;

class ChangeDetectorRegistry
{
    /** @var ChangeDetector[] */
    private $changeDetectors = [];

    /**
     * @param mixed $originalValue
     *
     * @return mixed
     */
    public function copyOriginalValue(ClassMetadata $class, string $propertyName, &$originalValue)
    {
        return $this->getChangeDetectorFromMetadata($class, $propertyName)->copyOriginalValue($originalValue);
    }

   /**
    * Whether or not the two values must be considered as different and trigger an UPDATE query
    *
    * @param mixed $value
    * @param mixed $originalValue
    */
    public function isChanged(ClassMetadata $class, string $propertyName, $value, $originalValue): bool
    {
        return $this->getChangeDetectorFromMetadata($class, $propertyName)->isChanged($value, $originalValue);
    }

    public function getChangeDetectorFromMetadata(ClassMetadata $class, string $propertyName): ChangeDetector
    {
        if (isset($class->fieldMappings[$propertyName]['changeDetector'])) {
            return $this->getChangeDetector($class->fieldMappings[$propertyName]['changeDetector']);
        }

        return $this->getChangeDetector(ByReferenceChangeDetector::class);
    }

    public function getChangeDetector(string $key): ChangeDetector
    {
        if (! isset($this->changeDetectors[$key])) {
            if (class_exists($key)) {
                $this->changeDetectors[$key] = new $key();
                if (! $this->changeDetectors[$key] instanceof ChangeDetector) {
                    throw new InvalidArgumentException('Expected ChangeDetector, got ' . gettype($this->changeDetectors[$key]));
                }
            }
        }

        return $this->changeDetectors[$key];
    }
}

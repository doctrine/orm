<?php

declare(strict_types=1);

namespace Doctrine\ORM\Tools\ChangeDetector;

use Doctrine\ORM\Mapping\ClassMetadata;
use Exception;
use InvalidArgumentException;
use Throwable;

use function class_exists;
use function get_class;
use function sprintf;

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
            try {
                return $this->getChangeDetector($class->fieldMappings[$propertyName]['changeDetector']);
            } catch (Throwable $e) {
                throw new Exception(sprintf("Failed to determine the change detector for class '%s' and property '%s'", $class->name, $propertyName), 0, $e);
            }
        }

        return $this->getChangeDetector(ByReferenceChangeDetector::class);
    }

    public function getChangeDetector(string $key): ChangeDetector
    {
        if (! isset($this->changeDetectors[$key])) {
            if (class_exists($key)) {
                $this->changeDetectors[$key] = new $key();
                if (! $this->changeDetectors[$key] instanceof ChangeDetector) {
                    throw new InvalidArgumentException('Expected ChangeDetector, got ' . get_class($this->changeDetectors[$key]));
                }
            } else {
                throw new InvalidArgumentException('Invalid ChangeDetector provided, it should be the name of a class implementing ChangeDetector interface ');
            }
        }

        return $this->changeDetectors[$key];
    }
}

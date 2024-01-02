<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\ORM\Mapping\ClassMetadata;
use ReflectionProperty;

/** @internal */
trait ReflectionBasedDriver
{
    /** @var bool */
    private $reportFieldsWhereDeclared = false;

    /**
     * Helps to deal with the case that reflection may report properties inherited from parent classes.
     * When we know about the fields already (inheritance has been anticipated in ClassMetadataFactory),
     * the driver must skip them.
     *
     * The declaring classes may mismatch when there are private properties: The same property name may be
     * reported multiple times, but since it is private, it is in fact multiple (different) properties in
     * different classes. In that case, report the property as an individual field. (ClassMetadataFactory will
     * probably fail in that case, though.)
     */
    private function isRepeatedPropertyDeclaration(ReflectionProperty $property, ClassMetadata $metadata): bool
    {
        if (! $this->reportFieldsWhereDeclared) {
            return $metadata->isMappedSuperclass && ! $property->isPrivate()
                || $metadata->isInheritedField($property->name)
                || $metadata->isInheritedAssociation($property->name)
                || $metadata->isInheritedEmbeddedClass($property->name);
        }

        $declaringClass = $property->class;

        if (
            isset($metadata->fieldMappings[$property->name]['declared'])
            && $metadata->fieldMappings[$property->name]['declared'] === $declaringClass
        ) {
            return true;
        }

        if (
            isset($metadata->associationMappings[$property->name]['declared'])
            && $metadata->associationMappings[$property->name]['declared'] === $declaringClass
        ) {
            return true;
        }

        return isset($metadata->embeddedClasses[$property->name]['declared'])
            && $metadata->embeddedClasses[$property->name]['declared'] === $declaringClass;
    }
}

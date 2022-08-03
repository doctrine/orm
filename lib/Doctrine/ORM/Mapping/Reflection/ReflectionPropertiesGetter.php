<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Mapping\Reflection;

use Doctrine\Persistence\Mapping\ReflectionService;
use ReflectionClass;
use ReflectionProperty;

use function array_combine;
use function array_filter;
use function array_map;
use function array_merge;
use function call_user_func_array;

/**
 * Utility class to retrieve all reflection instance properties of a given class, including
 * private inherited properties and transient properties.
 *
 * @private This API is for internal use only
 */
final class ReflectionPropertiesGetter
{
    /** @var ReflectionProperty[][] indexed by class name and property internal name */
    private $properties = [];

    /** @var ReflectionService */
    private $reflectionService;

    public function __construct(ReflectionService $reflectionService)
    {
        $this->reflectionService = $reflectionService;
    }

    /**
     * @param string $className
     * @psalm-param class-string $className
     *
     * @return ReflectionProperty[] indexed by property internal name
     */
    public function getProperties($className)
    {
        if (isset($this->properties[$className])) {
            return $this->properties[$className];
        }

        return $this->properties[$className] = call_user_func_array(
            'array_merge',
            // first merge because `array_merge` expects >= 1 params
            array_merge(
                [[]],
                array_map(
                    [$this, 'getClassProperties'],
                    $this->getHierarchyClasses($className)
                )
            )
        );
    }

    /**
     * @param string $className
     * @psalm-param class-string $className
     *
     * @return ReflectionClass[]
     * @psalm-return list<ReflectionClass>
     */
    private function getHierarchyClasses($className): array
    {
        $classes         = [];
        $parentClassName = $className;

        while ($parentClassName && $currentClass = $this->reflectionService->getClass($parentClassName)) {
            $classes[]       = $currentClass;
            $parentClassName = null;

            $parentClass = $currentClass->getParentClass();
            if ($parentClass) {
                $parentClassName = $parentClass->getName();
            }
        }

        return $classes;
    }

    //  phpcs:disable SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedMethod

    /**
     * @return ReflectionProperty[]
     * @psalm-return array<string, ReflectionProperty>
     */
    private function getClassProperties(ReflectionClass $reflectionClass): array
    {
        //  phpcs:enable SlevomatCodingStandard.Classes.UnusedPrivateElements.UnusedMethod
        $properties = $reflectionClass->getProperties();

        return array_filter(
            array_filter(array_map(
                [$this, 'getAccessibleProperty'],
                array_combine(
                    array_map([$this, 'getLogicalName'], $properties),
                    $properties
                )
            )),
            [$this, 'isInstanceProperty']
        );
    }

    /**
     * @return bool
     */
    private function isInstanceProperty(ReflectionProperty $reflectionProperty)
    {
        return ! $reflectionProperty->isStatic();
    }

    /**
     * @return ReflectionProperty|null
     */
    private function getAccessibleProperty(ReflectionProperty $property)
    {
        return $this->reflectionService->getAccessibleProperty(
            $property->getDeclaringClass()->getName(),
            $property->getName()
        );
    }

    /**
     * @return string
     */
    private function getLogicalName(ReflectionProperty $property)
    {
        $propertyName = $property->getName();

        if ($property->isPublic()) {
            return $propertyName;
        }

        if ($property->isProtected()) {
            return "\0*\0" . $propertyName;
        }

        return "\0" . $property->getDeclaringClass()->getName() . "\0" . $propertyName;
    }
}

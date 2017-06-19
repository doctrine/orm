<?php

declare(strict_types = 1);

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

namespace Doctrine\ORM\Reflection;

use Doctrine\Common\Persistence\Mapping\MappingException;

/**
 * PHP Runtime Reflection Service.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class RuntimeReflectionService implements ReflectionService
{
    /**
     * {@inheritdoc}
     */
    public function getParentClasses(string $className) : array
    {
        if (! class_exists($className)) {
            throw MappingException::nonExistingClass($className);
        }

        return class_parents($className);
    }

    /**
     * {@inheritdoc}
     */
    public function getClassShortName(string $className) : string
    {
        $reflectionClass = new \ReflectionClass($className);

        return $reflectionClass->getShortName();
    }

    /**
     * {@inheritdoc}
     */
    public function getClassNamespace(string $className) : string
    {
        $reflectionClass = new \ReflectionClass($className);

        return $reflectionClass->getNamespaceName();
    }

    /**
     * {@inheritdoc}
     */
    public function getClass(string $className) : ?\ReflectionClass
    {
        return new \ReflectionClass($className);
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessibleProperty(string $className, string $propertyName) : ?\ReflectionProperty
    {
        $reflectionProperty = new \ReflectionProperty($className, $propertyName);

        if ($reflectionProperty->isPublic()) {
            $reflectionProperty = new RuntimePublicReflectionProperty($className, $propertyName);
        }

        $reflectionProperty->setAccessible(true);

        return $reflectionProperty;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPublicMethod(string $className, string $methodName) : bool
    {
        try {
            $reflectionMethod = new \ReflectionMethod($className, $methodName);
        } catch (\ReflectionException $e) {
            return false;
        }

        return $reflectionMethod->isPublic();
    }
}

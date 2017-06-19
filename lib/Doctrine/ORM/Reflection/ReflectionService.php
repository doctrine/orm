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

/**
 * Very simple reflection service abstraction.
 *
 * This is required inside metadata layers that may require either
 * static or runtime reflection.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
interface ReflectionService
{
    /**
     * Returns an array of the parent classes (not interfaces) for the given class.
     *
     * @param string $className
     *
     * @throws \InvalidArgumentException If provided argument is not a valid class name.
     *
     * @return array
     */
    public function getParentClasses(string $className) : array;

    /**
     * Returns the shortname of a class.
     *
     * @param string $className
     *
     * @return string
     */
    public function getClassShortName(string $className) : string;

    /**
     * @param string $className
     *
     * @return string
     */
    public function getClassNamespace(string $className) : string;

    /**
     * Returns a reflection class instance or null.
     *
     * @param string $className
     *
     * @return \ReflectionClass|null
     */
    public function getClass(string $className) : ?\ReflectionClass;

    /**
     * Returns an accessible property (setAccessible(true)) or null.
     *
     * @param string $className
     * @param string $propertyName
     *
     * @return \ReflectionProperty|null
     */
    public function getAccessibleProperty(string $className, string $propertyName) : ?\ReflectionProperty;

    /**
     * Checks if the class have a public method with the given name.
     *
     * @param mixed $className
     * @param mixed $methodName
     *
     * @return bool
     */
    public function hasPublicMethod(string $className, string $methodName) : bool;
}

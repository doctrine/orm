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

namespace Doctrine\ORM\Proxy;

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\Common\Util\ClassUtils;

/**
 * This factory is used to create proxy objects for entities at runtime.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 * @since 2.0
 */
class ProxyFactory
{
    /** The EntityManager this factory is bound to. */
    private $_em;
    /** Whether to automatically (re)generate proxy classes. */
    private $_autoGenerate;
    /** The namespace that contains all proxy classes. */
    private $_proxyNamespace;
    /** The directory that contains all proxy classes. */
    private $_proxyDir;

    /**
     * Used to match very simple id methods that don't need
     * to be proxied since the identifier is known.
     *
     * @var string
     */
    const PATTERN_MATCH_ID_METHOD = '((public\s)?(function\s{1,}%s\s?\(\)\s{1,})\s{0,}{\s{0,}return\s{0,}\$this->%s;\s{0,}})i';

    /**
     * Initializes a new instance of the <tt>ProxyFactory</tt> class that is
     * connected to the given <tt>EntityManager</tt>.
     *
     * @param EntityManager $em The EntityManager the new factory works for.
     * @param string $proxyDir The directory to use for the proxy classes. It must exist.
     * @param string $proxyNs The namespace to use for the proxy classes.
     * @param boolean $autoGenerate Whether to automatically generate proxy classes.
     */
    public function __construct(EntityManager $em, $proxyDir, $proxyNs, $autoGenerate = false)
    {
        if ( ! $proxyDir) {
            throw ProxyException::proxyDirectoryRequired();
        }
        if ( ! $proxyNs) {
            throw ProxyException::proxyNamespaceRequired();
        }
        $this->_em = $em;
        $this->_proxyDir = $proxyDir;
        $this->_autoGenerate = $autoGenerate;
        $this->_proxyNamespace = $proxyNs;
    }

    /**
     * Gets a reference proxy instance for the entity of the given type and identified by
     * the given identifier.
     *
     * @param string $className
     * @param mixed $identifier
     * @return object
     */
    public function getProxy($className, $identifier)
    {
        $fqn = ClassUtils::generateProxyClassName($className, $this->_proxyNamespace);

        if (! class_exists($fqn, false)) {
            $fileName = $this->getProxyFileName($className);
            if ($this->_autoGenerate) {
                $this->_generateProxyClass($this->_em->getClassMetadata($className), $fileName, self::$_proxyClassTemplate);
            }
            require $fileName;
        }

        $entityPersister = $this->_em->getUnitOfWork()->getEntityPersister($className);

        return new $fqn($entityPersister, $identifier);
    }

    /**
     * Generate the Proxy file name
     *
     * @param string $className
     * @param string $baseDir Optional base directory for proxy file name generation.
     *                        If not specified, the directory configured on the Configuration of the
     *                        EntityManager will be used by this factory.
     * @return string
     */
    private function getProxyFileName($className, $baseDir = null)
    {
        $proxyDir = $baseDir ?: $this->_proxyDir;

        return $proxyDir . DIRECTORY_SEPARATOR . '__CG__' . str_replace('\\', '', $className) . '.php';
    }

    /**
     * Generates proxy classes for all given classes.
     *
     * @param array $classes The classes (ClassMetadata instances) for which to generate proxies.
     * @param string $toDir The target directory of the proxy classes. If not specified, the
     *                      directory configured on the Configuration of the EntityManager used
     *                      by this factory is used.
     * @return int Number of generated proxies.
     */
    public function generateProxyClasses(array $classes, $toDir = null)
    {
        $proxyDir = $toDir ?: $this->_proxyDir;
        $proxyDir = rtrim($proxyDir, DIRECTORY_SEPARATOR);
        $num = 0;

        foreach ($classes as $class) {
            /* @var $class ClassMetadata */
            if ($class->isMappedSuperclass || $class->reflClass->isAbstract()) {
                continue;
            }

            $proxyFileName = $this->getProxyFileName($class->name, $proxyDir);

            $this->_generateProxyClass($class, $proxyFileName, self::$_proxyClassTemplate);
            $num++;
        }

        return $num;
    }

    /**
     * Generates a proxy class file.
     *
     * @param ClassMetadata $class Metadata for the original class
     * @param string $fileName Filename (full path) for the generated class
     * @param string $file The proxy class template data
     */
    private function _generateProxyClass(ClassMetadata $class, $fileName, $file)
    {
        $methods = $this->_generateMethods($class);
        $sleepImpl = $this->_generateSleep($class);
        $cloneImpl = $class->reflClass->hasMethod('__clone') ? 'parent::__clone();' : ''; // hasMethod() checks case-insensitive

        $placeholders = array(
            '<namespace>',
            '<proxyClassName>', '<className>',
            '<methods>', '<sleepImpl>', '<cloneImpl>'
        );

        $className = ltrim($class->name, '\\');
        $proxyClassName = ClassUtils::generateProxyClassName($class->name, $this->_proxyNamespace);
        $parts = explode('\\', strrev($proxyClassName), 2);
        $proxyClassNamespace = strrev($parts[1]);
        $proxyClassName = strrev($parts[0]);

        $replacements = array(
            $proxyClassNamespace,
            $proxyClassName,
            $className,
            $methods,
            $sleepImpl,
            $cloneImpl
        );

        $file = str_replace($placeholders, $replacements, $file);

        $parentDirectory = dirname($fileName);

        if ( ! is_dir($parentDirectory)) {
            if (false === @mkdir($parentDirectory, 0775, true)) {
                throw ProxyException::proxyDirectoryNotWritable();
            }
        } else if ( ! is_writable($parentDirectory)) {
            throw ProxyException::proxyDirectoryNotWritable();
        }

        $tmpFileName = $fileName . '.' . uniqid("", true);
        file_put_contents($tmpFileName, $file);
        rename($tmpFileName, $fileName);
    }

    /**
     * Generates the methods of a proxy class.
     *
     * @param ClassMetadata $class
     * @return string The code of the generated methods.
     */
    private function _generateMethods(ClassMetadata $class)
    {
        $methods = '';

        $methodNames = array();
        foreach ($class->reflClass->getMethods() as $method) {
            /* @var $method ReflectionMethod */
            if ($method->isConstructor() || in_array(strtolower($method->getName()), array("__sleep", "__clone")) || isset($methodNames[$method->getName()])) {
                continue;
            }
            $methodNames[$method->getName()] = true;

            if ($method->isPublic() && ! $method->isFinal() && ! $method->isStatic()) {
                $methods .= "\n" . '    public function ';
                if ($method->returnsReference()) {
                    $methods .= '&';
                }
                $methods .= $method->getName() . '(';
                $firstParam = true;
                $parameterString = $argumentString = '';

                foreach ($method->getParameters() as $param) {
                    if ($firstParam) {
                        $firstParam = false;
                    } else {
                        $parameterString .= ', ';
                        $argumentString  .= ', ';
                    }

                    // We need to pick the type hint class too
                    if (($paramClass = $param->getClass()) !== null) {
                        $parameterString .= '\\' . $paramClass->getName() . ' ';
                    } else if ($param->isArray()) {
                        $parameterString .= 'array ';
                    }

                    if ($param->isPassedByReference()) {
                        $parameterString .= '&';
                    }

                    $parameterString .= '$' . $param->getName();
                    $argumentString  .= '$' . $param->getName();

                    if ($param->isDefaultValueAvailable()) {
                        $parameterString .= ' = ' . var_export($param->getDefaultValue(), true);
                    }
                }

                $methods .= $parameterString . ')';
                $methods .= "\n" . '    {' . "\n";
                if ($this->isShortIdentifierGetter($method, $class)) {
                    $identifier = lcfirst(substr($method->getName(), 3));

                    $cast = in_array($class->fieldMappings[$identifier]['type'], array('integer', 'smallint')) ? '(int) ' : '';

                    $methods .= '        if ($this->__isInitialized__ === false) {' . "\n";
                    $methods .= '            return ' . $cast . '$this->_identifier["' . $identifier . '"];' . "\n";
                    $methods .= '        }' . "\n";
                }
                $methods .= '        $this->__load();' . "\n";
                $methods .= '        return parent::' . $method->getName() . '(' . $argumentString . ');';
                $methods .= "\n" . '    }' . "\n";
            }
        }

        return $methods;
    }

    /**
     * Check if the method is a short identifier getter.
     *
     * What does this mean? For proxy objects the identifier is already known,
     * however accessing the getter for this identifier usually triggers the
     * lazy loading, leading to a query that may not be necessary if only the
     * ID is interesting for the userland code (for example in views that
     * generate links to the entity, but do not display anything else).
     *
     * @param ReflectionMethod $method
     * @param ClassMetadata $class
     * @return bool
     */
    private function isShortIdentifierGetter($method, ClassMetadata $class)
    {
        $identifier = lcfirst(substr($method->getName(), 3));
        $cheapCheck = (
            $method->getNumberOfParameters() == 0 &&
            substr($method->getName(), 0, 3) == "get" &&
            in_array($identifier, $class->identifier, true) &&
            $class->hasField($identifier) &&
            (($method->getEndLine() - $method->getStartLine()) <= 4)
            && in_array($class->fieldMappings[$identifier]['type'], array('integer', 'bigint', 'smallint', 'string'))
        );

        if ($cheapCheck) {
            $code = file($method->getDeclaringClass()->getFileName());
            $code = trim(implode(" ", array_slice($code, $method->getStartLine() - 1, $method->getEndLine() - $method->getStartLine() + 1)));

            $pattern = sprintf(self::PATTERN_MATCH_ID_METHOD, $method->getName(), $identifier);

            if (preg_match($pattern, $code)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Generates the code for the __sleep method for a proxy class.
     *
     * @param $class
     * @return string
     */
    private function _generateSleep(ClassMetadata $class)
    {
        $sleepImpl = '';

        if ($class->reflClass->hasMethod('__sleep')) {
            $sleepImpl .= "return array_merge(array('__isInitialized__'), parent::__sleep());";
        } else {
            $sleepImpl .= "return array('__isInitialized__', ";
            $first = true;

            foreach ($class->getReflectionProperties() as $name => $prop) {
                if ($first) {
                    $first = false;
                } else {
                    $sleepImpl .= ', ';
                }

                $sleepImpl .= "'" . $name . "'";
            }

            $sleepImpl .= ');';
        }

        return $sleepImpl;
    }

    /** Proxy class code template */
    private static $_proxyClassTemplate =
'<?php

namespace <namespace>;

/**
 * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
 */
class <proxyClassName> extends \<className> implements \Doctrine\ORM\Proxy\Proxy
{
    private $_entityPersister;
    private $_identifier;
    public $__isInitialized__ = false;
    public function __construct($entityPersister, $identifier)
    {
        $this->_entityPersister = $entityPersister;
        $this->_identifier = $identifier;
    }
    /** @private */
    public function __load()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;

            if (method_exists($this, "__wakeup")) {
                // call this after __isInitialized__to avoid infinite recursion
                // but before loading to emulate what ClassMetadata::newInstance()
                // provides.
                $this->__wakeup();
            }

            if ($this->_entityPersister->load($this->_identifier, $this) === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            unset($this->_entityPersister, $this->_identifier);
        }
    }

    /** @private */
    public function __isInitialized()
    {
        return $this->__isInitialized__;
    }

    <methods>

    public function __sleep()
    {
        <sleepImpl>
    }

    public function __clone()
    {
        if (!$this->__isInitialized__ && $this->_entityPersister) {
            $this->__isInitialized__ = true;
            $class = $this->_entityPersister->getClassMetadata();
            $original = $this->_entityPersister->load($this->_identifier);
            if ($original === null) {
                throw new \Doctrine\ORM\EntityNotFoundException();
            }
            foreach ($class->reflFields as $field => $reflProperty) {
                $reflProperty->setValue($this, $reflProperty->getValue($original));
            }
            unset($this->_entityPersister, $this->_identifier);
        }
        <cloneImpl>
    }
}';
}

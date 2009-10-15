<?php
/*
 *  $Id$
 *
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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Proxy;

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping\ClassMetadata,
    Doctrine\ORM\Mapping\AssociationMapping,
    Doctrine\Common\DoctrineException;

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
            throw DoctrineException::proxyDirectoryRequired();
        }
        if ( ! $proxyNs) {
            throw DoctrineException::proxyNamespaceRequired();
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
    public function getReferenceProxy($className, $identifier)
    {
        $proxyClassName = str_replace('\\', '', $className) . 'RProxy';
        $fqn = $this->_proxyNamespace . '\\' . $proxyClassName;
        
        if ($this->_autoGenerate && ! class_exists($fqn, false)) {
            $fileName = $this->_proxyDir . DIRECTORY_SEPARATOR . $proxyClassName . '.php';
            $this->_generateProxyClass($this->_em->getClassMetadata($className), $proxyClassName, $fileName, self::$_proxyClassTemplate);
            require $fileName;
        }
        
        if ( ! $this->_em->getMetadataFactory()->hasMetadataFor($fqn)) {
            $this->_em->getMetadataFactory()->setMetadataFor($fqn, $this->_em->getClassMetadata($className));
        }
        
        $entityPersister = $this->_em->getUnitOfWork()->getEntityPersister($className);
        
        return new $fqn($entityPersister, $identifier);
    }
    
    /**
     * Gets an association proxy instance.
     * 
     * @param object $owner
     * @param AssociationMapping $assoc
     * @param array $joinColumnValues
     * @return object
     */
    public function getAssociationProxy($owner, AssociationMapping $assoc, array $joinColumnValues)
    {
        $proxyClassName = str_replace('\\', '', $assoc->targetEntityName) . 'AProxy';
        $fqn = $this->_proxyNamespace . '\\' . $proxyClassName;
        
        if ($this->_autoGenerate && ! class_exists($fqn, false)) {
            $fileName = $this->_proxyDir . DIRECTORY_SEPARATOR . $proxyClassName . '.php';
            $this->_generateProxyClass($this->_em->getClassMetadata($assoc->targetEntityName), $proxyClassName, $fileName, self::$_assocProxyClassTemplate);
            require $fileName;
        }
        
        if ( ! $this->_em->getMetadataFactory()->hasMetadataFor($fqn)) {
            $this->_em->getMetadataFactory()->setMetadataFor($fqn, $this->_em->getClassMetadata($assoc->targetEntityName));
        }
        
        return new $fqn($this->_em, $assoc, $owner, $joinColumnValues);
    }
    
    /**
     * Generates proxy classes for all given classes.
     * 
     * @param array $classes The classes (ClassMetadata instances) for which to generate proxies.
     * @param string $toDir The target directory of the proxy classes. If not specified, the
     *                      directory configured on the Configuration of the EntityManager used
     *                      by this factory is used.
     */
    public function generateProxyClasses(array $classes, $toDir = null)
    {
        $proxyDir = $toDir ?: $this->_proxyDir;
        $proxyDir = rtrim($proxyDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        foreach ($classes as $class) {
            $AproxyClassName = str_replace('\\', '', $class->name) . 'AProxy';
            $RproxyClassName = str_replace('\\', '', $class->name) . 'RProxy';
            $AproxyFileName = $proxyDir . $AproxyClassName . '.php';
            $RproxyFileName = $proxyDir . $RproxyClassName . '.php';
            $this->_generateProxyClass($class, $RproxyClassName, $RproxyFileName, self::$_proxyClassTemplate);
            $this->_generateProxyClass($class, $AproxyClassName, $AproxyFileName, self::$_assocProxyClassTemplate);
        }
    }
    
    /**
     * Generates a (reference or association) proxy class.
     * 
     * @param $class
     * @param $originalClassName
     * @param $proxyClassName
     * @param $file The path of the file to write to.
     * @return void
     */
    private function _generateProxyClass($class, $proxyClassName, $fileName, $file)
    {
        $methods = $this->_generateMethods($class);
        $sleepImpl = $this->_generateSleep($class);
        $constructorInv = $class->reflClass->hasMethod('__construct') ? 'parent::__construct();' : '';

        $placeholders = array(
            '<namespace>',
            '<proxyClassName>', '<className>',
            '<methods>', '<sleepImpl>',
            '<constructorInvocation>'
        );
        $replacements = array(
            $this->_proxyNamespace,
            $proxyClassName, $class->name,
            $methods, $sleepImpl,
            $constructorInv
        );
        
        $file = str_replace($placeholders, $replacements, $file);

        file_put_contents($fileName, $file);
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
        
        foreach ($class->reflClass->getMethods() as $method) {
            if ($method->isConstructor()) {
                continue;
            }
            
            if ($method->isPublic() && ! $method->isFinal() && ! $method->isStatic()) {
                $methods .= PHP_EOL . '        public function ' . $method->getName() . '(';
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
                
                $methods .= $parameterString . ') {' . PHP_EOL;
                $methods .= '            $this->_load();' . PHP_EOL;
                $methods .= '            return parent::' . $method->getName() . '(' . $argumentString . ');';
                $methods .= PHP_EOL . '        }' . PHP_EOL;
            }
        }
        
        return $methods;
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
            $sleepImpl .= 'return parent::__sleep();';
        } else {
            $sleepImpl .= 'return array(';
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
    
    /** Reference Proxy class code template */
    private static $_proxyClassTemplate =
'<?php
namespace <namespace> {
    /**
     * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
     */
    class <proxyClassName> extends \<className> implements \Doctrine\ORM\Proxy\Proxy {
        private $_entityPersister;
        private $_identifier;
        private $_loaded = false;
        public function __construct($entityPersister, $identifier) {
            $this->_entityPersister = $entityPersister;
            $this->_identifier = $identifier;
            <constructorInvocation>
        }
        private function _load() {
            if ( ! $this->_loaded) {
                $this->_entityPersister->load($this->_identifier, $this);
                unset($this->_entityPersister);
                unset($this->_identifier);
                $this->_loaded = true;
            }
        }
        public function __isInitialized__() { return $this->_loaded; }

        <methods>

        public function __sleep() {
            if (!$this->_loaded) {
                throw new \RuntimeException("Not fully loaded proxy can not be serialized.");
            }
            <sleepImpl>
        }
    }
}';

    /** Association Proxy class code template */
    private static $_assocProxyClassTemplate =
'<?php
namespace <namespace> {
    /**
     * THIS CLASS WAS GENERATED BY THE DOCTRINE ORM. DO NOT EDIT THIS FILE.
     */
    class <proxyClassName> extends \<className> implements \Doctrine\ORM\Proxy\Proxy {
        private $_em;
        private $_assoc;
        private $_owner;
        private $_joinColumnValues;
        private $_loaded = false;
        public function __construct($em, $assoc, $owner, array $joinColumnValues) {
            $this->_em = $em;
            $this->_assoc = $assoc;
            $this->_owner = $owner;
            $this->_joinColumnValues = $joinColumnValues;
            <constructorInvocation>
        }
        private function _load() {
            if ( ! $this->_loaded) {
                $this->_assoc->load($this->_owner, $this, $this->_em, $this->_joinColumnValues);
                unset($this->_em);
                unset($this->_owner);
                unset($this->_assoc);
                unset($this->_joinColumnValues);
                $this->_loaded = true;
            }
        }
        public function __isInitialized__() { return $this->_loaded; }

        <methods>

        public function __sleep() {
            if (!$this->_loaded) {
                throw new \RuntimeException("Not fully loaded proxy can not be serialized.");
            }
            <sleepImpl>
        }
    }
}';
}

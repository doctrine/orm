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
    Doctrine\ORM\Mapping\ClassMetadata;

/**
 * The ProxyClassGenerator is used to generate proxy objects for entities at runtime.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @author Giorgio Sironi <piccoloprincipeazzurro@gmail.com>
 * @since 2.0
 */
class ProxyClassGenerator
{
	/** The namespace for the generated proxy classes. */
    private static $_ns = 'Doctrine\Generated\Proxies\\';
    private $_cacheDir;
    private $_em;

    /**
	 * Generates and stores proxy class files in the given cache directory.
	 *
	 * @param EntityManager $em
	 * @param string $cacheDir The directory where generated proxy classes will be saved.
	 *                         If not set, sys_get_temp_dir() is used.
     */
    public function __construct(EntityManager $em, $cacheDir = null)
    {
        $this->_em = $em;
        
        if ($cacheDir === null) {
            $cacheDir = sys_get_temp_dir();
        }
        
        $this->_cacheDir = rtrim($cacheDir, '/') . '/';
    }

    /**
     * Generates a reference proxy class.
     * This is a proxy for an object which we have the id for retrieval.
     *
     * @param string $originalClassName
     * @return string name of the proxy class
     */
    public function generateReferenceProxyClass($originalClassName)
    {
        $proxyClassName = str_replace('\\', '_', $originalClassName) . 'RProxy';

        return $this->_generateClass($originalClassName, $proxyClassName, self::$_proxyClassTemplate);
    }

    /**
     * Generates an association proxy class.
     * This is a proxy class for an object which we have the association where
     * it is involved, but no primary key to retrieve it.
     *
     * @param string $originalClassName
     * @return string the proxy class name
     */
    public function generateAssociationProxyClass($originalClassName)
    {
        $proxyClassName = str_replace('\\', '_', $originalClassName) . 'AProxy';

        return $this->_generateClass($originalClassName, $proxyClassName, self::$_assocProxyClassTemplate);
    }

    private function _generateClass($originalClassName, $proxyClassName, $file)
    {
        $proxyFullyQualifiedClassName = self::$_ns . $proxyClassName;
        
        if ($this->_em->getMetadataFactory()->hasMetadataFor($proxyFullyQualifiedClassName)) {
            return $proxyFullyQualifiedClassName;
        }
        
        $class = $this->_em->getClassMetadata($originalClassName);
        $this->_em->getMetadataFactory()->setMetadataFor($proxyFullyQualifiedClassName, $class);
        
        if (class_exists($proxyFullyQualifiedClassName, false)) {
            return $proxyFullyQualifiedClassName;
        }

        $fileName = $this->_cacheDir . $proxyClassName . '.g.php';
            
        if (file_exists($fileName)) {
            require $fileName;
            return $proxyFullyQualifiedClassName;
        }

        $methods = $this->_generateMethods($class);
        $sleepImpl = $this->_generateSleep($class);
        $constructorInv = $class->reflClass->hasMethod('__construct') ? 'parent::__construct();' : '';

        $placeholders = array(
            '<proxyClassName>', '<className>',
            '<methods>', '<sleepImpl>',
            '<constructorInvocation>'
        );
        $replacements = array(
            $proxyClassName, $originalClassName,
            $methods, $sleepImpl,
            $constructorInv
        );
        
        $file = str_replace($placeholders, $replacements, $file);
        
        file_put_contents($fileName, $file);
        
        require $fileName;
        
        return $proxyFullyQualifiedClassName;
    }

    private function _generateMethods(ClassMetadata $class)
    {
        $methods = '';
        
        foreach ($class->reflClass->getMethods() as $method) {
            if ($method->isConstructor()) {
                continue;
            }
            
            if ($method->isPublic() && ! $method->isFinal() && ! $method->isStatic()) {
                $methods .= PHP_EOL . 'public function ' . $method->getName() . '(';
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
                    }
                    
                    $parameterString .= '$' . $param->getName();
                    $argumentString  .= '$' . $param->getName();
                }
                
                $methods .= $parameterString . ') {' . PHP_EOL;
                $methods .= '$this->_load();' . PHP_EOL;
                $methods .= 'return parent::' . $method->getName() . '(' . $argumentString . ');';
                $methods .= '}' . PHP_EOL;
            }
        }
        
        return $methods;
    }

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


    /** Proxy class code template */
    private static $_proxyClassTemplate =
'<?php
/** This class was generated by the Doctrine ORM. DO NOT EDIT THIS FILE. */
namespace Doctrine\Generated\Proxies {
    class <proxyClassName> extends \<className> {
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

        <methods>

        public function __sleep() {
            if (!$this->_loaded) {
                throw new \RuntimeException("Not fully loaded proxy can not be serialized.");
            }
            <sleepImpl>
        }
    }
}';

    private static $_assocProxyClassTemplate =
'<?php
/** This class was generated by the Doctrine ORM. DO NOT EDIT THIS FILE. */
namespace Doctrine\Generated\Proxies {
    class <proxyClassName> extends \<className> {
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

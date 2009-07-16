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
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;

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
	 * @param string $cacheDir
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
     * @param string $className
     * @param string $proxyClassName
     * @param string $fileName
     */
    public function generateReferenceProxyClass($className)
    {
        $class = $this->_em->getClassMetadata($className);
        $proxyClassName = str_replace('\\', '_', $className) . 'RProxy';
        if (!class_exists($proxyClassName, false)) {
            $this->_em->getMetadataFactory()->setMetadataFor(self::$_ns . $proxyClassName, $class);
            $fileName = $this->_cacheDir . $proxyClassName . '.g.php';
            if (file_exists($fileName)) {
                require $fileName;
                $proxyClassName = '\\' . self::$_ns . $proxyClassName;
                return $proxyClassName;
            }
        }

        $file = self::$_proxyClassTemplate;
        $methods = $this->_generateMethods($class);
        $sleepImpl = $this->_generateSleep($class);

        $placeholders = array(
            '<proxyClassName>', '<className>',
            '<methods>', '<sleepImpl>'
        );
        $replacements = array(
            $proxyClassName, $className, $methods, $sleepImpl
        );
        
        $file = str_replace($placeholders, $replacements, $file);
        
        file_put_contents($fileName, $file);
        require $fileName;
        $proxyClassName = '\\' . self::$_ns . $proxyClassName;
        return $proxyClassName;
    }

    protected function _generateMethods(ClassMetadata $class)
    {
        $methods = '';
        foreach ($class->reflClass->getMethods() as $method) {
            if ($method->getName() == '__construct') {
                continue;
            }
            if ($method->isPublic() && ! $method->isFinal()) {
                $methods .= PHP_EOL . 'public function ' . $method->getName() . '(';
                $firstParam = true;
                $parameterString = '';
                foreach ($method->getParameters() as $param) {
                    if ($firstParam) {
                        $firstParam = false;
                    } else {
                        $parameterString .= ', ';
                    }
                    $parameterString .= '$' . $param->getName();
                }
                $methods .= $parameterString . ') {' . PHP_EOL;
                $methods .= '$this->_load();' . PHP_EOL;
                $methods .= 'return parent::' . $method->getName() . '(' . $parameterString . ');';
                $methods .= '}' . PHP_EOL;
            }
        }
        return $methods;
    }

    public function _generateSleep(ClassMetadata $class)
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

    /**
     * Generates a proxy class.
     * This is a proxy class for an object which we have the association where
     * it is involved, but no primary key to retrieve it.
     *
     * @param string $className
     * @param string $proxyClassName
     */
    public function generateAssociationProxyClass($className, $proxyClassName)
    {
        $class = $this->_em->getClassMetadata($className);
        $file = self::$_assocProxyClassTemplate;

        $methods = '';
        foreach ($class->reflClass->getMethods() as $method) {
            if ($method->isPublic() && ! $method->isFinal()) {
                $methods .= PHP_EOL . 'public function ' . $method->getName() . '(';
                $firstParam = true;
                $parameterString = '';
                foreach ($method->getParameters() as $param) {
                    if ($firstParam) {
                        $firstParam = false;
                    } else {
                        $parameterString .= ', ';
                    }
                    $parameterString .= '$' . $param->getName();
                }
                $methods .= $parameterString . ') {' . PHP_EOL;
                $methods .= '$this->_load();' . PHP_EOL;
                $methods .= 'return parent::' . $method->getName() . '(' . $parameterString . ');';
                $methods .= '}' . PHP_EOL;
            }
        }

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

        $placeholders = array(
            '<proxyClassName>', '<className>',
            '<methods>', '<sleepImpl>'
        );
        $replacements = array(
            $proxyClassName, $className, $methods, $sleepImpl
        );

        $file = str_replace($placeholders, $replacements, $file);

        file_put_contents($fileName, $file);
        return $fileName;
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
                throw new RuntimeException("Not fully loaded proxy can not be serialized.");
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
        private $_loaded = false;
        public function __construct($em, $assoc, $owner) {
            $this->_em = $em;
            $this->_assoc = $assoc;
            $this->_owner = $owner;
        }
        private function _load() {
            if ( ! $this->_loaded) {
                $this->_assoc->load($this->_owner, $this, $this->_em);
                unset($this->_em);
                unset($this->_owner);
                unset($this->_assoc);
                $this->_loaded = true;
            }
        }

        <methods>

        public function __sleep() {
            if (!$this->_loaded) {
                throw new RuntimeException("Not fully loaded proxy can not be serialized.");
            }
            <sleepImpl>
        }
    }
}';
}

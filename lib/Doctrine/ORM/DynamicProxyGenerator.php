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

namespace Doctrine\ORM;

/**
 * The DynamicProxyGenerator is used to generate proxy objects for entities.
 * For that purpose he generates proxy class files on the fly as needed.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class DynamicProxyGenerator
{
    private static $_ns = 'Doctrine\Generated\Proxies\\';
    private $_cacheDir = '/Users/robo/dev/php/tmp/gen/';
    private $_em;

    public function __construct(EntityManager $em, $cacheDir = null)
    {
        $this->_em = $em;
        if ($cacheDir === null) {
            $cacheDir = sys_get_temp_dir();
        }
        $this->_cacheDir = $cacheDir;
    }

    /**
     * Gets a reference proxy instance.
     *
     * @param string $className
     * @param mixed $identifier
     * @return object
     */
    public function getReferenceProxy($className, $identifier)
    {
        $class = $this->_em->getClassMetadata($className);
        $proxyClassName = str_replace('\\', '_', $className) . 'RProxy';
        if ( ! class_exists($proxyClassName, false)) {
            $this->_em->getMetadataFactory()->setMetadataFor(self::$_ns . $proxyClassName, $class);
            $fileName = $this->_cacheDir . $proxyClassName . '.g.php';
            if ( ! file_exists($fileName)) {
                $this->_generateReferenceProxyClass($className, $identifier, $proxyClassName, $fileName);
            }
            require $fileName;
        }
        $proxyClassName = '\\' . self::$_ns . $proxyClassName;
        return new $proxyClassName($this->_em, $class, $identifier);
    }

    /**
     * Gets an association proxy instance.
     */
    public function getAssociationProxy($owner, \Doctrine\ORM\Mapping\AssociationMapping $assoc)
    {
        $proxyClassName = str_replace('\\', '_', $assoc->getTargetEntityName()) . 'AProxy';
        if ( ! class_exists($proxyClassName, false)) {
            $this->_em->getMetadataFactory()->setMetadataFor(self::$_ns . $proxyClassName, $this->_em->getClassMetadata($assoc->getTargetEntityName()));
            $fileName = $this->_cacheDir . $proxyClassName . '.g.php';
            if ( ! file_exists($fileName)) {
                $this->_generateAssociationProxyClass($assoc->getTargetEntityName(), $proxyClassName, $fileName);
            }
            require $fileName;
        }
        $proxyClassName = '\\' . self::$_ns . $proxyClassName;
        return new $proxyClassName($this->_em, $assoc, $owner);
    }

    /**
     * Generates a proxy class.
     *
     * @param string $className
     * @param mixed $id
     * @param string $proxyClassName
     * @param string $fileName
     */
    private function _generateReferenceProxyClass($className, $id, $proxyClassName, $fileName)
    {
        $class = $this->_em->getClassMetadata($className);
        $file = self::$_proxyClassTemplate;

        $methods = '';
        foreach ($class->getReflectionClass()->getMethods() as $method) {
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
        if ($class->getReflectionClass()->hasMethod('__sleep')) {
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
    }

    /**
     * Generates a proxy class.
     *
     * @param string $className
     * @param mixed $id
     * @param string $proxyClassName
     * @param string $fileName
     */
    private function _generateAssociationProxyClass($className, $proxyClassName, $fileName)
    {
        $class = $this->_em->getClassMetadata($className);
        $file = self::$_assocProxyClassTemplate;

        $methods = '';
        foreach ($class->getReflectionClass()->getMethods() as $method) {
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
        if ($class->getReflectionClass()->hasMethod('__sleep')) {
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
    }

    /** Proxy class code template */
    private static $_proxyClassTemplate =
'<?php
/** This class was generated by the Doctrine ORM. DO NOT EDIT THIS FILE. */
namespace Doctrine\Generated\Proxies {
    class <proxyClassName> extends \<className> {
        private $_em;
        private $_class;
        private $_loaded = false;
        public function __construct($em, $class, $identifier) {
            $this->_em = $em;
            $this->_class = $class;
            $this->_class->setIdentifierValues($this, $identifier);
        }
        private function _load() {
            if ( ! $this->_loaded) {
                $this->_em->getUnitOfWork()->getEntityPersister($this->_class->getClassName())->load($this->_identifier, $this);
                unset($this->_em);
                unset($this->_class);
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

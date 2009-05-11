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
 */
class DynamicProxyGenerator
{
    private $_cacheDir = '/Users/robo/dev/php/tmp/gen/';
    private $_em;

    public function __construct(EntityManager $em, $cacheDir = null)
    {
        $this->_em = $em;
        if ($cacheDir === null) {
            $cacheDir = sys_get_tmp_dir();
        }
        $this->_cacheDir = $cacheDir;
    }

    /**
     * 
     *
     * @param <type> $className
     * @param <type> $identifier
     * @return <type>
     */
    public function getProxy($className, $identifier)
    {
        $proxyClassName = str_replace('\\', '_', $className) . 'Proxy';
        if ( ! class_exists($proxyClassName, false)) {
            $fileName = $this->_cacheDir . $proxyClassName . '.g.php';
            if ( ! file_exists($fileName)) {
                $this->_generateProxyClass($className, $identifier, $proxyClassName, $fileName);
            }
            require $fileName;
        }
        $proxyClassName = '\Doctrine\Generated\Proxies\\' . $proxyClassName;
        return new $proxyClassName($this->_em, $this->_em->getClassMetadata($className), $identifier);
    }

    /**
     * Generates a proxy class.
     *
     * @param <type> $className
     * @param <type> $id
     * @param <type> $proxyClassName
     * @param <type> $fileName 
     */
    private function _generateProxyClass($className, $id, $proxyClassName, $fileName)
    {
        $class = $this->_em->getClassMetadata($className);
        $file = self::$_proxyClassTemplate;

        if (is_array($id) && count($id) > 1) {
            // it's a composite key. keys = field names, values = values.
            $values = array_values($id);
            $keys = array_keys($id);
        } else {
            $values = is_array($id) ? array_values($id) : array($id);
            $keys = $class->getIdentifierFieldNames();
        }
        $paramIndex = 1;
        $identifierCondition = 'prx.' . $keys[0] . ' = ?' . $paramIndex++;
        for ($i=1, $c=count($keys); $i < $c; ++$i) {
            $identifierCondition .= ' AND prx.' . $keys[$i] . ' = ?' . $paramIndex++;
        }

        $parameters = 'array(';
        $first = true;
        foreach ($values as $value) {
            if ($first) {
                $first = false;
            } else {
                $parameters = ', ';
            }
            $parameters .= "'" . $value . "'";
        }
        $parameters .= ')';

        $hydrationSetters = '';
        foreach ($class->getReflectionProperties() as $name => $prop) {
            if ( ! $class->hasAssociation($name)) {
                $hydrationSetters .= '$this->_class->setValue($this, \'' . $name . '\', $scalar[0][\'prx_' . $name . '\']);' . PHP_EOL;
            }
        }

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
            '<proxyClassName>', '<className>', '<identifierCondition>',
            '<parameters>', '<hydrationSetters>', '<methods>', '<sleepImpl>'
        );
        $replacements = array(
            $proxyClassName, $className, $identifierCondition, $parameters,
            $hydrationSetters, $methods, $sleepImpl
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
                $scalar = $this->_em->createQuery(\'select prx from <className> prx where <identifierCondition>\')->execute(<parameters>, \Doctrine\ORM\Query::HYDRATE_SCALAR);
                <hydrationSetters>
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
}

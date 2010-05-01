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
 * and is licensed under the LGPL. For more information, see
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\Common\Annotations;

use \ReflectionClass, 
    \ReflectionMethod, 
    \ReflectionProperty,
    Doctrine\Common\Cache\Cache;

/**
 * A reader for docblock annotations.
 * 
 * @since   2.0
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class AnnotationReader
{
    /**
     * Cache salt
     *
     * @var string
     * @static
     */
    private static $CACHE_SALT = '@<Annot>';
    
    /**
     * Annotations Parser
     *
     * @var Doctrine\Common\Annotations\Parser
     */
    private $_parser;
    
    /**
     * Cache mechanism to store processed Annotations
     *
     * @var Doctrine\Common\Cache\Cache
     */
    private $_cache;
    
    /**
     * Constructor. Initializes a new AnnotationReader that uses the given Cache provider.
     * 
     * @param Cache $cache The cache provider to use. If none is provided, ArrayCache is used.
     */
    public function __construct(Cache $cache = null)
    {
        $this->_parser = new Parser;
        $this->_cache = $cache ?: new \Doctrine\Common\Cache\ArrayCache;
    }

    /**
     * Sets the default namespace that the AnnotationReader should assume for annotations
     * with not fully qualified names.
     * 
     * @param string $defaultNamespace
     */
    public function setDefaultAnnotationNamespace($defaultNamespace)
    {
        $this->_parser->setDefaultAnnotationNamespace($defaultNamespace);
    }

    /**
     * Sets an alias for an annotation namespace.
     * 
     * @param $namespace
     * @param $alias
     */
    public function setAnnotationNamespaceAlias($namespace, $alias)
    {
        $this->_parser->setAnnotationNamespaceAlias($namespace, $alias);
    }

    /**
     * Gets the annotations applied to a class.
     * 
     * @param string|ReflectionClass $class The name or ReflectionClass of the class from which
     * the class annotations should be read.
     * @return array An array of Annotations.
     */
    public function getClassAnnotations(ReflectionClass $class)
    {
        $cacheKey = $class->getName() . self::$CACHE_SALT;

        // Attempt to grab data from cache
        if (($data = $this->_cache->fetch($cacheKey)) !== false) {
            return $data;
        }
        
        $annotations = $this->_parser->parse($class->getDocComment(), 'class ' . $class->getName());
        $this->_cache->save($cacheKey, $annotations, null);
        
        return $annotations;
    }
    
    /**
     * Gets a class annotation.
     * 
     * @param $class
     * @param string $annotation The name of the annotation.
     * @return The Annotation or NULL, if the requested annotation does not exist.
     */
    public function getClassAnnotation(ReflectionClass $class, $annotation)
    {
        $annotations = $this->getClassAnnotations($class);

        return isset($annotations[$annotation]) ? $annotations[$annotation] : null;
    }
    
    /**
     * Gets the annotations applied to a property.
     * 
     * @param string|ReflectionClass $class The name or ReflectionClass of the class that owns the property.
     * @param string|ReflectionProperty $property The name or ReflectionProperty of the property
     * from which the annotations should be read.
     * @return array An array of Annotations.
     */
    public function getPropertyAnnotations(ReflectionProperty $property)
    {
        $cacheKey = $property->getDeclaringClass()->getName() . '$' . $property->getName() . self::$CACHE_SALT;

        // Attempt to grab data from cache
        if (($data = $this->_cache->fetch($cacheKey)) !== false) {
            return $data;
        }
        
        $context = 'property ' . $property->getDeclaringClass()->getName() . "::\$" . $property->getName();
        $annotations = $this->_parser->parse($property->getDocComment(), $context);
        $this->_cache->save($cacheKey, $annotations, null);
        
        return $annotations;
    }
    
    /**
     * Gets a property annotation.
     * 
     * @param ReflectionProperty $property
     * @param string $annotation The name of the annotation.
     * @return The Annotation or NULL, if the requested annotation does not exist.
     */
    public function getPropertyAnnotation(ReflectionProperty $property, $annotation)
    {
        $annotations = $this->getPropertyAnnotations($property);

        return isset($annotations[$annotation]) ? $annotations[$annotation] : null;
    }
    
    /**
     * Gets the annotations applied to a method.
     * 
     * @param string|ReflectionClass $class The name or ReflectionClass of the class that owns the method.
     * @param string|ReflectionMethod $property The name or ReflectionMethod of the method from which
     * the annotations should be read.
     * @return array An array of Annotations.
     */
    public function getMethodAnnotations(ReflectionMethod $method)
    {
        $cacheKey = $method->getDeclaringClass()->getName() . '#' . $method->getName() . self::$CACHE_SALT;

        // Attempt to grab data from cache
        if (($data = $this->_cache->fetch($cacheKey)) !== false) {
            return $data;
        } 

        $context = 'method ' . $method->getDeclaringClass()->getName() . '::' . $method->getName() . '()';
        $annotations = $this->_parser->parse($method->getDocComment(), $context);
        $this->_cache->save($cacheKey, $annotations, null);
        
        return $annotations;
    }
    
    /**
     * Gets a method annotation.
     * 
     * @param ReflectionMethod $method
     * @param string $annotation The name of the annotation.
     * @return The Annotation or NULL, if the requested annotation does not exist.
     */
    public function getMethodAnnotation(ReflectionMethod $method, $annotation)
    {
        $annotations = $this->getMethodAnnotations($method);
        
        return isset($annotations[$annotation]) ? $annotations[$annotation] : null;
    }
}
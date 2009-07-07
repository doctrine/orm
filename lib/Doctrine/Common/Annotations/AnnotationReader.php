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

namespace Doctrine\Common\Annotations;

use \ReflectionClass, \ReflectionMethod, \ReflectionProperty;
use Doctrine\Common\Cache\Cache;

/**
 * A reader for docblock annotations.
 * 
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class AnnotationReader
{
    private static $CACHE_SALT = "@<Annot>";
    private $_parser;
    private $_cache;
    private $_annotations = array();
    
    /**
     * Initiaizes a new AnnotationReader that uses the given Cache provider to cache annotations.
     * 
     * @param Cache $cache The cache provider to use.
     */
    public function __construct(Cache $cache)
    {
        $this->_parser = new Parser;
        $this->_cache = $cache;
    }
    
    /**
     * 
     * @param $defaultNamespace
     * @return unknown_type
     */
    public function setDefaultAnnotationNamespace($defaultNamespace)
    {
        $this->_parser->setDefaultAnnotationNamespace($defaultNamespace);
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
        $className = $class->getName();
        
        if (isset($this->_annotations[$className])) {
            return $this->_annotations[$className];
        } else if ($this->_cache->contains($className . self::$CACHE_SALT)) {
            $this->_annotations[$className] = $this->_cacheDriver->get($className . self::$CACHE_SALT);
            return $this->_annotations[$className];
        }
        
        $this->_annotations[$className] = $this->_parser->parse($class->getDocComment());
        
        return $this->_annotations[$className];
    }
    
    /**
     * 
     * @param $class
     * @param $annotation
     * @return unknown_type
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
        $propertyName = $property->getDeclaringClass()->getName() . '$' . $property->getName();
        
        if (isset($this->_annotations[$propertyName])) {
            return $this->_annotations[$propertyName];
        } else if ($this->_cache->contains($propertyName . self::$CACHE_SALT)) {
            $this->_annotations[$propertyName] = $this->_cacheDriver->get($propertyName . self::$CACHE_SALT);
            return $this->_annotations[$propertyName];
        }
        
        $this->_annotations[$propertyName] = $this->_parser->parse($property->getDocComment());
        
        return $this->_annotations[$propertyName];
    }
    
    /**
     * 
     * @param $property
     * @param $annotation
     * @return unknown_type
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
        $methodName = $method->getDeclaringClass()->getName() . '#' . $method->getName();
        
        if (isset($this->_annotations[$methodName])) {
            return $this->_annotations[$methodName];
        } else if ($this->_cache->contains($methodName . self::$CACHE_SALT)) {
            $this->_annotations[$methodName] = $this->_cacheDriver->get($methodName . self::$CACHE_SALT);
            return $this->_annotations[$methodName];
        }
        
        $this->_annotations[$methodName] = $this->_parser->parse($method->getDocComment());
        
        return $this->_annotations[$methodName];
    }
    
    /**
     * 
     * @param $method
     * @param $annotation
     * @return unknown_type
     */
    public function getMethodAnnotation(ReflectionMethod $method, $annotation)
    {
        $annotations = $this->getMethodAnnotations($method);
        return isset($annotations[$annotation]) ? $annotations[$annotation] : null;
    }
}
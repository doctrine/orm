<?php

namespace Doctrine\Common\Annotations;

use \ReflectionClass, \ReflectionMethod, \ReflectionProperty;
use Doctrine\Common\Cache\Cache;

class AnnotationReader
{
    private static $CACHE_SALT = "@<Annot>";
    private $_parser;
    private $_cache;
    private $_annotations = array();
    
    public function __construct(Cache $cache)
    {
        $this->_parser = new Parser;
        $this->_cache = $cache;
    }
    
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
    public function getClassAnnotations($class)
    {
        if (is_string($class)) {
            $className = $class;
        } else {
            $className = $class->getName();
        }
        
        if (isset($this->_annotations[$className])) {
            return $this->_annotations[$className];
        } else if ($this->_cache->contains($className . self::$CACHE_SALT)) {
            $this->_annotations[$className] = $this->_cacheDriver->get($className . self::$CACHE_SALT);
            return $this->_annotations[$className];
        }
        
        if (is_string($class)) {
            $class = new ReflectionClass($className);
        }
        
        $this->_annotations[$className] = $this->_parser->parse($class->getDocComment());
        
        return $this->_annotations[$className];
    }
    
    public function getClassAnnotation($class, $annotation)
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
    public function getPropertyAnnotations($class, $property)
    {
        $className = is_string($class) ? $class : $class->getName();
        if (is_string($property)) {
            $propertyName = $className . '$' . $property;
        } else {
            $propertyName = $className . '$' . $property->getName();
        }
        
        if (isset($this->_annotations[$propertyName])) {
            return $this->_annotations[$propertyName];
        } else if ($this->_cache->contains($propertyName . self::$CACHE_SALT)) {
            $this->_annotations[$propertyName] = $this->_cacheDriver->get($propertyName . self::$CACHE_SALT);
            return $this->_annotations[$propertyName];
        }
        
        if (is_string($property)) {
            $property = new ReflectionProperty($className, $property);
        }
        
        $this->_annotations[$propertyName] = $this->_parser->parse($property->getDocComment());
        
        return $this->_annotations[$propertyName];
    }
    
    public function getPropertyAnnotation($class, $property, $annotation)
    {
        $annotations = $this->getPropertyAnnotations($class, $property);
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
    public function getMethodAnnotations($class, $method)
    {
        $className = is_string($class) ? $class : $class->getName();
        if (is_string($method)) {
            $methodName = $className . '#' . $method;
        } else {
            $methodName = $className . '#' . $method->getName();
        }
        
        if (isset($this->_annotations[$methodName])) {
            return $this->_annotations[$methodName];
        } else if ($this->_cache->contains($methodName . self::$CACHE_SALT)) {
            $this->_annotations[$methodName] = $this->_cacheDriver->get($methodName . self::$CACHE_SALT);
            return $this->_annotations[$methodName];
        }
        
        if (is_string($method)) {
            $method = new ReflectionMethod($className, $method);
        }
        
        $this->_annotations[$methodName] = $this->_parser->parse($method->getDocComment());
        
        return $this->_annotations[$methodName];
    }
    
    public function getMethodAnnotation($class, $method, $annotation)
    {
        $annotations = $this->getMethodAnnotations($class, $method);
        return isset($annotations[$annotation]) ? $annotations[$annotation] : null;
    }
}
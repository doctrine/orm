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

namespace Doctrine\ORM;

use Doctrine\Common\Cache\Cache,
    Doctrine\Common\Cache\ArrayCache,
    Doctrine\Common\Annotations\AnnotationRegistry,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\ORM\Mapping\Driver\Driver,
    Doctrine\ORM\Mapping\Driver\AnnotationDriver;

/**
 * Configuration container for all configuration options of Doctrine.
 * It combines all configuration options from DBAL & ORM.
 *
 * @since 2.0
 * @internal When adding a new configuration option just write a getter/setter pair.
 * @author  Benjamin Eberlei <kontakt@beberlei.de>
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class Configuration extends \Doctrine\DBAL\Configuration
{
    /**
     * Sets the directory where Doctrine generates any necessary proxy class files.
     *
     * @param string $dir
     */
    public function setProxyDir($dir)
    {
        $this->_attributes['proxyDir'] = $dir;
    }

    /**
     * Gets the directory where Doctrine generates any necessary proxy class files.
     *
     * @return string
     */
    public function getProxyDir()
    {
        return isset($this->_attributes['proxyDir']) ?
                $this->_attributes['proxyDir'] : null;
    }

    /**
     * Gets a boolean flag that indicates whether proxy classes should always be regenerated
     * during each script execution.
     *
     * @return boolean
     */
    public function getAutoGenerateProxyClasses()
    {
        return isset($this->_attributes['autoGenerateProxyClasses']) ?
                $this->_attributes['autoGenerateProxyClasses'] : true;
    }

    /**
     * Sets a boolean flag that indicates whether proxy classes should always be regenerated
     * during each script execution.
     *
     * @param boolean $bool
     */
    public function setAutoGenerateProxyClasses($bool)
    {
        $this->_attributes['autoGenerateProxyClasses'] = $bool;
    }

    /**
     * Gets the namespace where proxy classes reside.
     *
     * @return string
     */
    public function getProxyNamespace()
    {
        return isset($this->_attributes['proxyNamespace']) ?
                $this->_attributes['proxyNamespace'] : null;
    }

    /**
     * Sets the namespace where proxy classes reside.
     *
     * @param string $ns
     */
    public function setProxyNamespace($ns)
    {
        $this->_attributes['proxyNamespace'] = $ns;
    }

    /**
     * Sets the cache driver implementation that is used for metadata caching.
     *
     * @param Driver $driverImpl
     * @todo Force parameter to be a Closure to ensure lazy evaluation
     *       (as soon as a metadata cache is in effect, the driver never needs to initialize).
     */
    public function setMetadataDriverImpl(Driver $driverImpl)
    {
        $this->_attributes['metadataDriverImpl'] = $driverImpl;
    }

    /**
     * Add a new default annotation driver with a correctly configured annotation reader.
     *
     * @param array $paths
     * @return Mapping\Driver\AnnotationDriver
     */
    public function newDefaultAnnotationDriver($paths = array())
    {
        if (version_compare(\Doctrine\Common\Version::VERSION, '2.2.0-DEV', '>=')) {
            // Register the ORM Annotations in the AnnotationRegistry
            AnnotationRegistry::registerFile(__DIR__ . '/Mapping/Driver/DoctrineAnnotations.php');

            $reader = new \Doctrine\Common\Annotations\SimpleAnnotationReader();
            $reader->addNamespace('Doctrine\ORM\Mapping');
            $reader = new \Doctrine\Common\Annotations\CachedReader($reader, new ArrayCache());
        } else if (version_compare(\Doctrine\Common\Version::VERSION, '2.1.0-DEV', '>=')) {
            // Register the ORM Annotations in the AnnotationRegistry
            AnnotationRegistry::registerFile(__DIR__ . '/Mapping/Driver/DoctrineAnnotations.php');

            $reader = new AnnotationReader();
            $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
            $reader->setIgnoreNotImportedAnnotations(true);
            $reader->setEnableParsePhpImports(false);
            $reader = new \Doctrine\Common\Annotations\CachedReader(
                new \Doctrine\Common\Annotations\IndexedReader($reader), new ArrayCache()
            );
        } else {
            $reader = new AnnotationReader();
            $reader->setDefaultAnnotationNamespace('Doctrine\ORM\Mapping\\');
        }
        return new AnnotationDriver($reader, (array)$paths);
    }

    /**
     * Adds a namespace under a certain alias.
     *
     * @param string $alias
     * @param string $namespace
     */
    public function addEntityNamespace($alias, $namespace)
    {
        $this->_attributes['entityNamespaces'][$alias] = $namespace;
    }

    /**
     * Resolves a registered namespace alias to the full namespace.
     *
     * @param string $entityNamespaceAlias
     * @return string
     * @throws MappingException
     */
    public function getEntityNamespace($entityNamespaceAlias)
    {
        if ( ! isset($this->_attributes['entityNamespaces'][$entityNamespaceAlias])) {
            throw ORMException::unknownEntityNamespace($entityNamespaceAlias);
        }

        return trim($this->_attributes['entityNamespaces'][$entityNamespaceAlias], '\\');
    }

    /**
     * Set the entity alias map
     *
     * @param array $entityAliasMap
     * @return void
     */
    public function setEntityNamespaces(array $entityNamespaces)
    {
        $this->_attributes['entityNamespaces'] = $entityNamespaces;
    }

    /**
     * Retrieves the list of registered entity namespace aliases.
     *
     * @return array
     */
    public function getEntityNamespaces()
    {
        return $this->_attributes['entityNamespaces'];
    }

    /**
     * Gets the cache driver implementation that is used for the mapping metadata.
     *
     * @throws ORMException
     * @return Mapping\Driver\Driver
     */
    public function getMetadataDriverImpl()
    {
        return isset($this->_attributes['metadataDriverImpl']) ?
                $this->_attributes['metadataDriverImpl'] : null;
    }

    /**
     * Gets the cache driver implementation that is used for the query cache (SQL cache).
     *
     * @return \Doctrine\Common\Cache\Cache
     */
    public function getQueryCacheImpl()
    {
        return isset($this->_attributes['queryCacheImpl']) ?
                $this->_attributes['queryCacheImpl'] : null;
    }

    /**
     * Sets the cache driver implementation that is used for the query cache (SQL cache).
     *
     * @param \Doctrine\Common\Cache\Cache $cacheImpl
     */
    public function setQueryCacheImpl(Cache $cacheImpl)
    {
        $this->_attributes['queryCacheImpl'] = $cacheImpl;
    }

    /**
     * Gets the cache driver implementation that is used for the hydration cache (SQL cache).
     *
     * @return \Doctrine\Common\Cache\Cache
     */
    public function getHydrationCacheImpl()
    {
        return isset($this->_attributes['hydrationCacheImpl'])
            ? $this->_attributes['hydrationCacheImpl']
            : null;
    }

    /**
     * Sets the cache driver implementation that is used for the hydration cache (SQL cache).
     *
     * @param \Doctrine\Common\Cache\Cache $cacheImpl
     */
    public function setHydrationCacheImpl(Cache $cacheImpl)
    {
        $this->_attributes['hydrationCacheImpl'] = $cacheImpl;
    }

    /**
     * Gets the cache driver implementation that is used for metadata caching.
     *
     * @return \Doctrine\Common\Cache\Cache
     */
    public function getMetadataCacheImpl()
    {
        return isset($this->_attributes['metadataCacheImpl']) ?
                $this->_attributes['metadataCacheImpl'] : null;
    }

    /**
     * Sets the cache driver implementation that is used for metadata caching.
     *
     * @param \Doctrine\Common\Cache\Cache $cacheImpl
     */
    public function setMetadataCacheImpl(Cache $cacheImpl)
    {
        $this->_attributes['metadataCacheImpl'] = $cacheImpl;
    }

    /**
     * Adds a named DQL query to the configuration.
     *
     * @param string $name The name of the query.
     * @param string $dql The DQL query string.
     */
    public function addNamedQuery($name, $dql)
    {
        $this->_attributes['namedQueries'][$name] = $dql;
    }

    /**
     * Gets a previously registered named DQL query.
     *
     * @param string $name The name of the query.
     * @return string The DQL query.
     */
    public function getNamedQuery($name)
    {
        if ( ! isset($this->_attributes['namedQueries'][$name])) {
            throw ORMException::namedQueryNotFound($name);
        }
        return $this->_attributes['namedQueries'][$name];
    }

    /**
     * Adds a named native query to the configuration.
     *
     * @param string $name The name of the query.
     * @param string $sql The native SQL query string.
     * @param ResultSetMapping $rsm The ResultSetMapping used for the results of the SQL query.
     */
    public function addNamedNativeQuery($name, $sql, Query\ResultSetMapping $rsm)
    {
        $this->_attributes['namedNativeQueries'][$name] = array($sql, $rsm);
    }

    /**
     * Gets the components of a previously registered named native query.
     *
     * @param string $name The name of the query.
     * @return array A tuple with the first element being the SQL string and the second
     *          element being the ResultSetMapping.
     */
    public function getNamedNativeQuery($name)
    {
        if ( ! isset($this->_attributes['namedNativeQueries'][$name])) {
            throw ORMException::namedNativeQueryNotFound($name);
        }
        return $this->_attributes['namedNativeQueries'][$name];
    }

    /**
     * Ensures that this Configuration instance contains settings that are
     * suitable for a production environment.
     *
     * @throws ORMException If a configuration setting has a value that is not
     *                      suitable for a production environment.
     */
    public function ensureProductionSettings()
    {
        if ( !$this->getQueryCacheImpl()) {
            throw ORMException::queryCacheNotConfigured();
        }
        if ( !$this->getMetadataCacheImpl()) {
            throw ORMException::metadataCacheNotConfigured();
        }
        if ($this->getAutoGenerateProxyClasses()) {
            throw ORMException::proxyClassesAlwaysRegenerating();
        }
    }

    /**
     * Registers a custom DQL function that produces a string value.
     * Such a function can then be used in any DQL statement in any place where string
     * functions are allowed.
     *
     * DQL function names are case-insensitive.
     *
     * @param string $name
     * @param string $className
     */
    public function addCustomStringFunction($name, $className)
    {
        $this->_attributes['customStringFunctions'][strtolower($name)] = $className;
    }

    /**
     * Gets the implementation class name of a registered custom string DQL function.
     *
     * @param string $name
     * @return string
     */
    public function getCustomStringFunction($name)
    {
        $name = strtolower($name);
        return isset($this->_attributes['customStringFunctions'][$name]) ?
                $this->_attributes['customStringFunctions'][$name] : null;
    }

    /**
     * Sets a map of custom DQL string functions.
     *
     * Keys must be function names and values the FQCN of the implementing class.
     * The function names will be case-insensitive in DQL.
     *
     * Any previously added string functions are discarded.
     *
     * @param array $functions The map of custom DQL string functions.
     */
    public function setCustomStringFunctions(array $functions)
    {
        $this->_attributes['customStringFunctions'] = array_change_key_case($functions);
    }

    /**
     * Registers a custom DQL function that produces a numeric value.
     * Such a function can then be used in any DQL statement in any place where numeric
     * functions are allowed.
     *
     * DQL function names are case-insensitive.
     *
     * @param string $name
     * @param string $className
     */
    public function addCustomNumericFunction($name, $className)
    {
        $this->_attributes['customNumericFunctions'][strtolower($name)] = $className;
    }

    /**
     * Gets the implementation class name of a registered custom numeric DQL function.
     *
     * @param string $name
     * @return string
     */
    public function getCustomNumericFunction($name)
    {
        $name = strtolower($name);
        return isset($this->_attributes['customNumericFunctions'][$name]) ?
                $this->_attributes['customNumericFunctions'][$name] : null;
    }

    /**
     * Sets a map of custom DQL numeric functions.
     *
     * Keys must be function names and values the FQCN of the implementing class.
     * The function names will be case-insensitive in DQL.
     *
     * Any previously added numeric functions are discarded.
     *
     * @param array $functions The map of custom DQL numeric functions.
     */
    public function setCustomNumericFunctions(array $functions)
    {
        $this->_attributes['customNumericFunctions'] = array_change_key_case($functions);
    }

    /**
     * Registers a custom DQL function that produces a date/time value.
     * Such a function can then be used in any DQL statement in any place where date/time
     * functions are allowed.
     *
     * DQL function names are case-insensitive.
     *
     * @param string $name
     * @param string $className
     */
    public function addCustomDatetimeFunction($name, $className)
    {
        $this->_attributes['customDatetimeFunctions'][strtolower($name)] = $className;
    }

    /**
     * Gets the implementation class name of a registered custom date/time DQL function.
     *
     * @param string $name
     * @return string
     */
    public function getCustomDatetimeFunction($name)
    {
        $name = strtolower($name);
        return isset($this->_attributes['customDatetimeFunctions'][$name]) ?
                $this->_attributes['customDatetimeFunctions'][$name] : null;
    }

    /**
     * Sets a map of custom DQL date/time functions.
     *
     * Keys must be function names and values the FQCN of the implementing class.
     * The function names will be case-insensitive in DQL.
     *
     * Any previously added date/time functions are discarded.
     *
     * @param array $functions The map of custom DQL date/time functions.
     */
    public function setCustomDatetimeFunctions(array $functions)
    {
        $this->_attributes['customDatetimeFunctions'] = array_change_key_case($functions);
    }

    /**
     * Get the hydrator class for the given hydration mode name.
     *
     * @param string $modeName The hydration mode name.
     * @return string $hydrator The hydrator class name.
     */
    public function getCustomHydrationMode($modeName)
    {
        return isset($this->_attributes['customHydrationModes'][$modeName]) ?
            $this->_attributes['customHydrationModes'][$modeName] : null;
    }

    /**
     * Add a custom hydration mode.
     *
     * @param string $modeName The hydration mode name.
     * @param string $hydrator The hydrator class name.
     */
    public function addCustomHydrationMode($modeName, $hydrator)
    {
        $this->_attributes['customHydrationModes'][$modeName] = $hydrator;
    }

    /**
     * Set a class metadata factory.
     *
     * @param string $cmf
     */
    public function setClassMetadataFactoryName($cmfName)
    {
        $this->_attributes['classMetadataFactoryName'] = $cmfName;
    }

    /**
     * @return string
     */
    public function getClassMetadataFactoryName()
    {
        if (!isset($this->_attributes['classMetadataFactoryName'])) {
            $this->_attributes['classMetadataFactoryName'] = 'Doctrine\ORM\Mapping\ClassMetadataFactory';
        }
        return $this->_attributes['classMetadataFactoryName'];
    }

    /**
     * Add a filter to the list of possible filters.
     *
     * @param string $name The name of the filter.
     * @param string $className The class name of the filter.
     */
    public function addFilter($name, $className)
    {
        $this->_attributes['filters'][$name] = $className;
    }

    /**
     * Gets the class name for a given filter name.
     *
     * @param string $name The name of the filter.
     *
     * @return string The class name of the filter, or null of it is not
     *  defined.
     */
    public function getFilterClassName($name)
    {
        return isset($this->_attributes['filters'][$name]) ?
                $this->_attributes['filters'][$name] : null;
    }

    /**
     * Set default repository class.
     *
     * @since 2.2
     * @param string $className
     * @throws ORMException If not is a \Doctrine\ORM\EntityRepository
     */
    public function setDefaultRepositoryClassName($className)
    {
        if ($className != "Doctrine\ORM\EntityRepository" &&
           !is_subclass_of($className, 'Doctrine\ORM\EntityRepository')){
            throw ORMException::invalidEntityRepository($className);
        }
        $this->_attributes['defaultRepositoryClassName'] = $className;
    }

    /**
     * Get default repository class.
     *
     * @since 2.2
     * @return string
     */
    public function getDefaultRepositoryClassName()
    {
        return isset($this->_attributes['defaultRepositoryClassName']) ?
                $this->_attributes['defaultRepositoryClassName'] : 'Doctrine\ORM\EntityRepository';
    }
}

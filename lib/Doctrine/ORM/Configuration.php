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

use Doctrine\ORM\Mapping\Driver\AnnotationDriver;

/**
 * Configuration container for all configuration options of Doctrine.
 * It combines all configuration options from DBAL & ORM.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 * @internal When adding a new configuration option just write a getter/setter
 * pair and add the option to the _attributes array with a proper default value.
 */
class Configuration extends \Doctrine\DBAL\Configuration
{    
    /**
     * Creates a new configuration that can be used for Doctrine.
     */
    public function __construct()
    {
        parent::__construct();
        $this->_attributes = array_merge($this->_attributes, array(
            'resultCacheImpl' => null,
            'queryCacheImpl' => null,
            'metadataCacheImpl' => null,
            'metadataDriverImpl' => new AnnotationDriver(),
            'dqlClassAliasMap' => array(),
            'cacheDir' => null,
            'allowPartialObjects' => true,
            'useCExtension' => false
            ));
    }

    /**
     * Gets a boolean flag that specifies whether partial objects are allowed.
     *
     * If partial objects are allowed, Doctrine will never use proxies or lazy loading
     * and you always only get what you explicitly query for.
     *
     * @return boolean Whether partial objects are allowed.
     */
    public function getAllowPartialObjects()
    {
        return $this->_attributes['allowPartialObjects'];
    }

    /**
     * Sets a boolean flag that specifies whether partial objects are allowed.
     *
     * If partial objects are allowed, Doctrine will never use proxies or lazy loading
     * and you always only get what you explicitly query for.
     *
     * @param boolean $allowed Whether partial objects are allowed.
     */
    public function setAllowPartialObjects($allowed)
    {
        $this->_attributes['allowPartialObjects'] = $allowed;
    }

    /**
     * Sets the directory where Doctrine writes any necessary cache files.
     *
     * @param string $dir
     */
    public function setCacheDir($dir)
    {
        $this->_attributes['cacheDir'] = $dir;
    }

    /**
     * Gets the directory where Doctrine writes any necessary cache files.
     *
     * @return string
     */
    public function getCacheDir()
    {
        return $this->_attributes['cacheDir'];
    }

    public function getDqlClassAliasMap()
    {
        return $this->_attributes['dqlClassAliasMap'];
    }

    public function setDqlClassAliasMap(array $map)
    {
        $this->_attributes['dqlClassAliasMap'] = $map;
    }

    /**
     * Sets the cache driver implementation that is used for metadata caching.
     *
     * @param object $driverImpl
     */
    public function setMetadataDriverImpl($driverImpl)
    {
        $this->_attributes['metadataDriverImpl'] = $driverImpl;
    }

    /**
     * Gets the cache driver implementation that is used for the mapping metadata.
     *
     * @return object
     */
    public function getMetadataDriverImpl()
    {
        return $this->_attributes['metadataDriverImpl'];
    }

    /**
     * Gets the cache driver implementation that is used for query result caching.
     *
     * @return object
     */
    public function getResultCacheImpl()
    {
        return $this->_attributes['resultCacheImpl'];
    }

    /**
     * Sets the cache driver implementation that is used for query result caching.
     *
     * @param object $cacheImpl
     */
    public function setResultCacheImpl($cacheImpl)
    {
        $this->_attributes['resultCacheImpl'] = $cacheImpl;
    }

    /**
     * Gets the cache driver implementation that is used for the query cache (SQL cache).
     *
     * @return object
     */
    public function getQueryCacheImpl()
    {
        return $this->_attributes['queryCacheImpl'];
    }

    /**
     * Sets the cache driver implementation that is used for the query cache (SQL cache).
     *
     * @param object $cacheImpl
     */
    public function setQueryCacheImpl($cacheImpl)
    {
        $this->_attributes['queryCacheImpl'] = $cacheImpl;
    }

    /**
     * Gets the cache driver implementation that is used for metadata caching.
     *
     * @return object
     */
    public function getMetadataCacheImpl()
    {
        return $this->_attributes['metadataCacheImpl'];
    }

    /**
     * Sets the cache driver implementation that is used for metadata caching.
     *
     * @param object $cacheImpl
     */
    public function setMetadataCacheImpl($cacheImpl)
    {
        $this->_attributes['metadataCacheImpl'] = $cacheImpl;
    }

    public function getUseCExtension()
    {
        return $this->_attributes['useCExtension'];
    }

    public function setUseCExtension($boolean)
    {
        $this->_attributes['useCExtension'] = $boolean;
    }
}
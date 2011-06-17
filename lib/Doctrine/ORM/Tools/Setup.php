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

namespace Doctrine\ORM\Tools;

use Doctrine\Common\ClassLoader;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Configuration;
use Doctrine\ORM\Mapping\Driver\XmlDriver;
use Doctrine\ORM\Mapping\Driver\YamlDriver;

/**
 * Convenience class for setting up Doctrine from different installations and configurations.
 * 
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class Setup
{
    /**
     * Use this method to register all autoloaders for a setup where Doctrine is checked out from
     * its github repository at {@link http://github.com/doctrine/doctrine2}
     * 
     * @param string $gitCheckoutRootPath 
     * @return void
     */
    static public function registerAutoloadGit($gitCheckoutRootPath)
    {
        if (!class_exists('Doctrine\Common\ClassLoader', false)) {
            require_once $gitCheckoutRootPath . "/lib/vendor/doctrine-common/lib/Doctrine/Common/ClassLoader.php";
        }
        
        $loader = new ClassLoader("Doctrine\Common", $gitCheckoutRootPath . "/lib/vendor/doctrine-common/lib");
        $loader->register();
        
        $loader = new ClassLoader("Doctrine\DBAL", $gitCheckoutRootPath . "/lib/vendor/doctrine-dbal/lib");
        $loader->register();
        
        $loader = new ClassLoader("Doctrine\ORM", $gitCheckoutRootPath . "/lib");
        $loader->register();
        
        $loader = new ClassLoader("Symfony\Component", $gitCheckoutRootPath . "/lib/vendor");
        $loader->register();
    }
    
    /**
     * Use this method to register all autoloaders for a setup where Doctrine is installed
     * though {@link http://pear.doctrine-project.org}.
     * 
     * @return void
     */
    static public function registerAutoloadPEAR()
    {
        if (!class_exists('Doctrine\Common\ClassLoader', false)) {
            require_once "Doctrine/Common/ClassLoader.php";
        }
        
        $loader = new ClassLoader("Doctrine");
        $loader->register();
        
        $parts = explode(PATH_SEPARATOR, get_include_path());
        
        foreach ($parts AS $includePath) {
            if ($includePath != "." && file_exists($includePath . "/Doctrine")) {
                $loader = new ClassLoader("Symfony\Component", $includePath . "/Doctrine");
                $loader->register();
                return;
            }
        }
    }
    
    /**
     * Use this method to register all autoloads for a downloaded Doctrine library.
     * Pick the directory the library was uncompressed into.
     * 
     * @param string $directory 
     */
    static public function registerAutoloadDirectory($directory)
    {
        if (!class_exists('Doctrine\Common\ClassLoader', false)) {
            require_once $directory . "/Doctrine/Common/ClassLoader.php";
        }
        
        $loader = new ClassLoader("Doctrine");
        $loader->register();
        
        $loader = new ClassLoader("Symfony\Component", $directory . "/Doctrine");
        $loader->register();
    }
    
    /**
     * Create a configuration with an annotation metadata driver.
     * 
     * @param array $paths
     * @param boolean $isDevMode
     * @param string $proxyDir
     * @param Cache $cache
     * @return Configuration
     */
    static public function createAnnotationMetadataConfiguration(array $paths, $isDevMode = false, $proxyDir = null, Cache $cache = null)
    {
        $config = self::createConfiguration($isDevMode, $cache, $proxyDir);
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver($paths));
        return $config;
    }
    
    /**
     * Create a configuration with an annotation metadata driver.
     * 
     * @param array $paths
     * @param boolean $isDevMode
     * @param string $proxyDir
     * @param Cache $cache
     * @return Configuration
     */
    static public function createXMLMetadataConfiguration(array $paths, $isDevMode = false, $proxyDir = null, Cache $cache = null)
    {
        $config = self::createConfiguration($isDevMode, $cache, $proxyDir);
        $config->setMetadataDriverImpl(new XmlDriver($paths));
        return $config;
    }
    
    /**
     * Create a configuration with an annotation metadata driver.
     * 
     * @param array $paths
     * @param boolean $isDevMode
     * @param string $proxyDir
     * @param Cache $cache
     * @return Configuration
     */
    static public function createYAMLMetadataConfiguration(array $paths, $isDevMode = false, $proxyDir = null, Cache $cache = null)
    {
        $config = self::createConfiguration($isDevMode, $cache, $proxyDir);
        $config->setMetadataDriverImpl(new YamlDriver($paths));
        return $config;
    }
    
    /**
     * Create a configuration without a metadata driver.
     * 
     * @param bool $isDevMode
     * @param string $proxyDir
     * @param Cache $cache
     * @return Configuration 
     */
    static public function createConfiguration($isDevMode = false, $proxyDir = null, Cache $cache = null)
    {
        if ($isDevMode === false && $cache === null) {
            if (extension_loaded('apc')) {
                $cache = new \Doctrine\Common\Cache\ApcCache;
            } else if (extension_loaded('xcache')) {
                $cache = new \Doctrine\Common\Cache\XcacheCache;
            } else if (extension_loaded('memcache')) {
                $memcache = new \Memcache();
                $memcache->connect('127.0.0.1');
                $cache = new \Doctrine\Common\Cache\MemcacheCache();
                $cache->setMemcache($memcache);
            } else {
                $cache = new ArrayCache;
            }
            $cache->setNamespace("dc2_"); // to avoid collisions
        } else if ($cache === null) {
            $cache = new ArrayCache;
        }
        
        $config = new Configuration();
        $config->setMetadataCacheImpl($cache);
        $config->setQueryCacheImpl($cache);
        $config->setResultCacheImpl($cache);
        $config->setProxyDir( $proxyDir ?: sys_get_temp_dir() );
        $config->setProxyNamespace('DoctrineProxies');
        $config->setAutoGenerateProxyClasses($isDevMode);
        
        return $config;
    }
}
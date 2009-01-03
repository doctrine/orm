<?php
/**
 * A class loader used to load class files on demand.
 *
 * Usage recommendation:
 * 1) Use only 1 class loader instance.
 * 2) Set the base paths to your class libraries (including Doctrine's) through
 *    $classLoader->setBasePath($prefix, $basePath);
 *    Example:
 *      $classLoader->setBasePath('Doctrine', '/usr/local/phplibs/doctrine/lib');
 *    Then, when trying to load the class Doctrine\ORM\EntityManager, for example
 *    the classloader will look for /usr/local/phplibs/doctrine/lib/Doctrine/ORM/EntityManager.php
 *
 * 3) DO NOT setCheckFileExists(true). Doing so is expensive in terms of performance.
 * 4) Use an opcode-cache (i.e. APC) (STRONGLY RECOMMENDED).
 * 
 * @since 2.0
 * @author romanb <roman@code-factory.org>
 */
class Doctrine_Common_ClassLoader
{    
    private $_namespaceSeparator = '_';
    private $_fileExtension = '.php';
    private $_checkFileExists = false;
    private $_basePaths = array();
    
    public function __construct()
    {
    }
    
    public function setCheckFileExists($bool)
    {
        $this->_checkFileExists = $bool;
    }
    
    public function setClassFileExtension($extension)
    {
        $this->_fileExtension = $extension;
    }
    
    public function setNamespaceSeparator($separator)
    {
        $this->_namespaceSeparator = $separator;
    }
    
    /**
     * Sets a static base path for classes with a certain prefix that is prepended
     * to the path derived from the class itself.
     *
     * @param string $basePath
     */
    public function setBasePath($classPrefix, $basePath)
    {
        $this->_basePaths[$classPrefix] = $basePath;
    }
    
    /**
     * Loads the given class or interface.
     *
     * @param string $classname The name of the class to load.
     * @return boolean TRUE if the class has been successfully loaded, FALSE otherwise.
     */
    public function loadClass($className)
    {
        if (class_exists($className, false) || interface_exists($className, false)) {
            return false;
        }

        $prefix = substr($className, 0, strpos($className, $this->_namespaceSeparator));
        $class = '';
        if (isset($this->_basePaths[$prefix])) {
            $class .= $this->_basePaths[$prefix] . DIRECTORY_SEPARATOR;
        }
        $class .= str_replace($this->_namespaceSeparator, DIRECTORY_SEPARATOR, $className)
                . $this->_fileExtension;

        if ($this->_checkFileExists) {
            if (!$fh = @fopen($class, 'r', true)) {
                return false;
            }
            @fclose($fh);
        }

        require $class;
        
        return true;
    }
    
    /**
     * Registers this class loader using spl_autoload_register().
     * 
     * @return void
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }
}


?>
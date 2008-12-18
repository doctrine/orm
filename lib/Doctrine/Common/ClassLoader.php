<?php

/**
 * A class loader used to load class files on demand.
 *
 * Usage recommendation:
 * 1) Use only 1 class loader instance.
 * 2) Prepend the base paths to your class libraries (including Doctrine's) to your include path.
 * 3) DO NOT setCheckFileExists(true). Doing so is expensive in terms of performance.
 * 
 * @since 2.0
 * @author romanb <roman@code-factory.org>
 */
class Doctrine_Common_ClassLoader
{    
    private $_namespaceSeparator = '_';
    private $_fileExtension = '.php';
    private $_checkFileExists = false;
    private $_basePath;
    
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
     * Sets a static base path that is prepended to the path derived from the class itself.
     *
     * @param string $basePath
     */
    public function setBasePath($basePath)
    {
        $this->_basePath = $basePath;
    }
    
    /**
     * Loads the given class or interface.
     *
     * @param string $classname The  name of the class to load.
     * @return boolean TRUE if the class has been successfully loaded, FALSE otherwise.
     */
    public function loadClass($className)
    {
        if (class_exists($className, false) || interface_exists($className, false)) {
            return false;
        }

        $class = '';
        if ($this->_basePath) {
            $class .= $this->_basePath . DIRECTORY_SEPARATOR;
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
<?php

namespace Doctrine\Common;

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
 * @author Roman S. Borschel <roman@code-factory.org>
 */
class ClassLoader
{
    private
        $_namespaceSeparator = '\\',
        $_fileExtension = '.php',
        $_checkFileExists = false,
        $_basePaths = array();

    /**
     * Constructor registers the autoloader automatically
     */
    public function __construct()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * Set check file exists
     *
     * @param boolean $bool
     * @return void
     */
    public function setCheckFileExists($bool)
    {
        $this->_checkFileExists = $bool;
    }

    /**
     * Set class file extension
     *
     * @param string $extension 
     * @return void
     */
    public function setClassFileExtension($extension)
    {
        $this->_fileExtension = $extension;
    }

    /**
     * Set namespace separator
     *
     * @param string $separator 
     * @return void
     */
    public function setNamespaceSeparator($separator)
    {
        $this->_namespaceSeparator = $separator;
    }

    /**
     * Sets a static base path for classes with a certain prefix that is prepended
     * to the path derived from the class itself.
     *
     * @param string $classPrefix
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

        $class .= str_replace(array($this->_namespaceSeparator, '_'), DIRECTORY_SEPARATOR, $className)
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
}
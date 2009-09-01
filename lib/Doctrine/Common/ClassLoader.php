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
 
namespace Doctrine\Common;

/**
 * A class loader used to load class files on demand.
 *
 * IMPORTANT:
 * 
 * This class loader is NOT meant to be put into a "chain" of autoloaders.
 * It is not meant to load only Doctrine class file. It is a one-classloader-for-all-classes
 * solution. It may not be useable with frameworks that do not follow basic pear/zend
 * conventions where the namespace+class name reflects the physical location of the source
 * file. This is not a bug. This class loader is, however, compatible with the
 * old namespace separator '_' (underscore), so any classes using that convention
 * instead of the 5.3 builtin namespaces can be loaded as well.
 * 
 * The only way to put this classloader into an autoloader chain is to use
 * setCheckFileExists(true) which is not recommended.
 *
 * Here is the recommended usage:
 * 1) Use only 1 class loader instance.
 * 2) Reduce the include_path to only the path to the PEAR packages.
 * 2) Set the base paths to any non-pear class libraries through
 *    $classLoader->setBasePath($prefix, $basePath);
 * 3) DO NOT setCheckFileExists(true). Doing so is expensive in terms of performance.
 * 4) Use an opcode-cache (i.e. APC) (STRONGLY RECOMMENDED).
 * 
 * The "prefix" is the part of a class name before the very first namespace separator
 * character. If the class is named "Foo_Bar_Baz" then the prefix is "Foo".
 * 
 * If no base path is configured for a certain class prefix, the classloader relies on
 * the include_path. That's why classes of any pear packages can be loaded without
 * registering their base paths. However, since a long include_path has a negative effect
 * on performance it is recommended to have only the path to the pear packages in the
 * include_path.
 * 
 * For any other class libraries you always have the choice between registering the base
 * path on the classloader or putting the base path into the include_path. The former
 * should be preferred but the latter is fine also.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision: 3938 $
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class ClassLoader
{
    /**
     * @var string Namespace separator
     */
    private $_namespaceSeparator = '\\';
    
    /**
     * @var string File extension used for classes
     */
    private $_fileExtension = '.php';
    
    /**
     * @var boolean Flag to inspect if file exists in codebase before include it
     */
    private $_checkFileExists = false;
    
    /**
     * @var array Hashmap of base paths that Autoloader will look into
     */
    private $_basePaths = array();

    /**
     * Constructor registers the autoloader automatically
     *
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
     * Sets the namespace separator to use.
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
        
        $prefix = '';
        $separator = $this->_namespaceSeparator;
        if (($pos = strpos($className, $this->_namespaceSeparator)) !== false) {
            $prefix = substr($className, 0, strpos($className, $this->_namespaceSeparator));
        } else if (($pos = strpos($className, '_')) !== false) {
            // Support for '_' namespace separator for compatibility with Zend/PEAR/...
            $prefix = substr($className, 0, strpos($className, '_'));
            $separator = '_';
        }
        
        // If we have a custom path for namespace, use it
        $class = ((isset($this->_basePaths[$prefix])) ? $this->_basePaths[$prefix] . DIRECTORY_SEPARATOR : '')
               . str_replace($separator, DIRECTORY_SEPARATOR, $className) . $this->_fileExtension;

        // Assure file exists in codebase before require if flag is active
        if ($this->_checkFileExists) {
            if (($fh = @fopen($class, 'r', true)) === false) {
                return false;
            }
            
            @fclose($fh);
        }

        require $class;

        return true;
    }
}
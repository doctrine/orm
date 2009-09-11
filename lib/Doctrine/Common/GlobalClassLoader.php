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
 * A <tt>GlobalClassLoader</tt> is an autoloader for class files that can be
 * installed on the SPL autoload stack. A GlobalClassLoader must be the only
 * autoloader on the stack and be used for all classes.
 *
 * The <tt>GlobalClassLoader</tt> assumes the PHP 5.3 namespace separator but
 * is also compatible with the underscore "_" namespace separator.
 *
 * A recommended class loading setup for optimal performance looks as follows:
 * 
 * 1) Use a GlobalClassLoader.
 * 2) Reduce the include_path to only the path to the PEAR packages.
 * 2) Register the namespaces of any other (non-pear) class library with their
 *    absolute base paths, like this: $gcl->registerNamespace('Zend', '/path/to/zf-lib');
 * 
 * If no base path is configured for a certain namespace, the GlobalClassLoader relies on
 * the include_path.
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision: 3938 $
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class GlobalClassLoader
{
    /**
     * @var string File extension used for classes
     */
    private $_defaultFileExtension = '.php';
    
    /**
     * @var array The custom file extensions of class libraries.
     */
    private $_fileExtensions = array();
    
    /**
     * @var array Hashmap of base paths to class libraries.
     */
    private $_basePaths = array();
    
    /**
     * Installs this class loader on the SPL autoload stack as the only class loader.
     * 
     * @throws DoctrineException If the SPL autoload stack already contains other autoloaders. 
     */
    public function register()
    {
        if (spl_autoload_functions() !== false) {
            throw new DoctrineException("Autoload stack is not empty. GlobalClassLoader does not work "
                    . "in an autoload stack.");
        }
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * Sets the default file extension of class files.
     *
     * @param string $extension 
     * @return void
     */
    public function setDefaultFileExtension($extension)
    {
        $this->_fileExtension = $extension;
    }

    /**
     * Sets a static base path for classes with a certain prefix that is prepended
     * to the path derived from the class itself.
     *
     * @param string $classPrefix The prefix (root namespace) of the class library.
     * @param string $basePath The base path to the class library.
     * @param string $fileExtension The custom file extension used by the class files in the namespace.
     */
    public function registerNamespace($namespace, $basePath, $fileExtension = null)
    {
        $this->_basePaths[$namespace] = $basePath;
        if ($fileExtension !== null) {
            $this->_fileExtensions[$namespace] = $fileExtension;
        }
    }

    /**
     * Loads the given class or interface.
     *
     * @param string $classname The name of the class to load.
     * @return boolean TRUE if the class has been successfully loaded, FALSE otherwise.
     */
    public function loadClass($className)
    {
        $prefix = '';
        $separator = '\\';
        
        if (($pos = strpos($className, $separator)) !== false) {
            $prefix = substr($className, 0, strpos($className, $separator));
        } else if (($pos = strpos($className, '_')) !== false) {
            // Support for '_' namespace separator for compatibility with Zend/PEAR/...
            $prefix = substr($className, 0, strpos($className, '_'));
            $separator = '_';
        }
        
        // Build the class file name
        $class = ((isset($this->_basePaths[$prefix])) ?
                $this->_basePaths[$prefix] . DIRECTORY_SEPARATOR : '')
               . str_replace($separator, DIRECTORY_SEPARATOR, $className)
               . (isset($this->_fileExtensions[$prefix]) ?
               $this->_fileExtensions[$prefix] : $this->_defaultFileExtension);

        require $class;
    }
}
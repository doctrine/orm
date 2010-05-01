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

namespace Doctrine\Common;

/**
 * A <tt>ClassLoader</tt> is an autoloader for class files that can be
 * installed on the SPL autoload stack. It is a class loader that loads only classes
 * of a specific namespace or all namespaces and is suitable for working together
 * with other autoloaders in the SPL autoload stack.
 * 
 * If no include path is configured through {@link setIncludePath}, a ClassLoader
 * relies on the PHP include_path.
 * 
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class ClassLoader
{
    private $_fileExtension = '.php';
    private $_namespace;
    private $_includePath;
    private $_namespaceSeparator = '\\';
    
    /**
     * Creates a new <tt>ClassLoader</tt> that loads classes of the
     * specified namespace.
     * 
     * @param string $ns The namespace to use.
     */
    public function __construct($ns = null, $includePath = null)
    {
        $this->_namespace = $ns;
        $this->_includePath = $includePath;
    }
    
    /**
     * Sets the namespace separator used by classes in the namespace of this class loader.
     * 
     * @param string $sep The separator to use.
     */
    public function setNamespaceSeparator($sep)
    {
        $this->_namespaceSeparator = $sep;
    }
    
    /**
     * Gets the namespace separator used by classes in the namespace of this class loader.
     * 
     * @return string
     */
    public function getNamespaceSeparator()
    {
        return $this->_namespaceSeparator;
    }
    
    /**
     * Sets the base include path for all class files in the namespace of this class loader.
     * 
     * @param string $includePath
     */
    public function setIncludePath($includePath)
    {
        $this->_includePath = $includePath;
    }
    
    /**
     * Gets the base include path for all class files in the namespace of this class loader.
     * 
     * @return string
     */
    public function getIncludePath()
    {
        return $this->_includePath;
    }
    
    /**
     * Sets the file extension of class files in the namespace of this class loader.
     * 
     * @param string $fileExtension
     */
    public function setFileExtension($fileExtension)
    {
        $this->_fileExtension = $fileExtension;
    }
    
    /**
     * Gets the file extension of class files in the namespace of this class loader.
     * 
     * @return string
     */
    public function getFileExtension()
    {
        return $this->_fileExtension;
    }
    
    /**
     * Installs this class loader on the SPL autoload stack.
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }
    
    /**
     * Uninstalls this class loader on the SPL autoload stack.
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }
    
    /**
     * Loads the given class or interface.
     *
     * @param string $classname The name of the class to load.
     * @return boolean TRUE if the class has been successfully loaded, FALSE otherwise.
     */
    public function loadClass($className)
    {
        if ($this->_namespace !== null && strpos($className, $this->_namespace.$this->_namespaceSeparator) !== 0) {
            return false;
        }

        require ($this->_includePath !== null ? $this->_includePath . DIRECTORY_SEPARATOR : '')
               . str_replace($this->_namespaceSeparator, DIRECTORY_SEPARATOR, $className)
               . $this->_fileExtension;
        
        return true;
    }
}
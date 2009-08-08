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
}
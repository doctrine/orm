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

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\ORM\Mapping\MappingException;

/**
 * Base driver for file-based metadata drivers.
 * 
 * A file driver operates in a mode where it loads the mapping files of individual
 * classes on demand. This requires the user to adhere to the convention of 1 mapping
 * file per class and the file names of the mapping files must correspond to the full
 * class name, including namespace, with the namespace delimiters '\', replaced by dots '.'.
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       2.0
 * @version     $Revision$
 * @author		Benjamin Eberlei <kontakt@beberlei.de>
 * @author		Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 * @author      Roman Borschel <roman@code-factory.org>
 */
abstract class AbstractFileDriver extends AbstractDriver implements Driver
{
    /**
     * @var string Middle part file extension.
     */
    protected $_middleFileExtension = 'dcm';
    
    /**
     * Get the element of schema meta data for the class from the mapping file.
     * This will lazily load the mapping file if it is not loaded yet
     *
     * @return array $element  The element of schema meta data
     */
    public function getElement($className)
    {
        $result = $this->_loadMappingFile($this->_findMappingFile($className));
        
        return $result[$className];
    }

    /**
     * Whether the class with the specified name should have its metadata loaded.
     * This is only the case if it is either mapped as an Entity or a
     * MappedSuperclass.
     *
     * @param string $className
     * @return boolean
     */
    public function isTransient($className)
    {
        try {
            $fileName = $this->_findMappingFile($className);
            
            return false;
        } catch (\Exception $e) {
            return true;
        }
    }
    
    /**
     * Gets the names of all mapped classes known to this driver.
     * 
     * @return array The names of all mapped classes known to this driver.
     */
    public function getAllClassNames()
    {
        $classes = array();
    
        if ($this->_paths) {
            foreach ((array) $this->_paths as $path) {
                if ( ! is_dir($path)) {
                    throw MappingException::driverRequiresConfiguredDirectoryPath();
                }
            
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );
        
                foreach ($iterator as $file) {
                    $info = pathinfo($file->getPathName());
                    
                    if ( ! isset($info['extension']) || $info['extension'] != $this->_fileExtension) {
                        continue;
                    }
                    
                    // NOTE: All files found here means classes are not transient!
                    $classes[] = str_replace('.', '\\', $file->getBasename('.' . $this->_getFileSuffix()));
                }
            }
        }
        
        return $classes;
    }

    /**
     * Finds the mapping file for the class with the given name by searching
     * through the configured paths.
     *
     * @param $className
     * @return string The (absolute) file name.
     * @throws MappingException
     */
    protected function _findMappingFile($className)
    {
        $fileName = str_replace('\\', '.', $className) . '.' . $this->_getFileSuffix();
        
        // Check whether file exists
        foreach ((array) $this->_paths as $path) {
            if (file_exists($path . DIRECTORY_SEPARATOR . $fileName)) {
                return $path . DIRECTORY_SEPARATOR . $fileName;
            }
        }

        throw MappingException::mappingFileNotFound($className);
    }
    
    /**
     * Retrieves the mapping file name suffix.
     *
     * @return string File name suffix.
     */
    protected function _getFileSuffix()
    {
        return ($this->_middleFileExtension != '' ? $this->_middleFileExtension . '.' : '')
             . $this->_fileExtension;
    }

    /**
     * Loads a mapping file with the given name and returns a map
     * from class/entity names to their corresponding elements.
     * 
     * @param string $file The mapping file to load.
     * @return array
     */
    abstract protected function _loadMappingFile($file);
}
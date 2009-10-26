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
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.com
 * @since       2.0
 * @version     $Revision: 1393 $
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
abstract class AbstractFileDriver implements Driver
{
    /**
     * The FILE_PER_CLASS mode is an operating mode of the FileDriver where it loads
     * the mapping files of individual classes on demand. This requires the user to
     * adhere to the convention of 1 mapping file per class and the file names of
     * the mapping files must correspond to the full class name, including namespace,
     * with the namespace delimiters '\', replaced by dots '.'.
     * 
     * Example:
     * Class: My\Project\Model\User
     * Mapping file: My.Project.Model.User.dcm.xml
     * 
     * @var integer
     */
    const FILE_PER_CLASS = 1;
    
    /**
     * The PRELOAD mode is an operating mode of the FileDriver where it loads
     * all mapping files in advance. This is the default behavior. It does not
     * require a naming convention or the convention of 1 class per mapping file.
     * 
     * @var integer
     */
    const PRELOAD = 2;
    
    /**
     * The paths where to look for mapping files.
     *
     * @var array
     */
    protected $_paths;

    /**
     * The operating mode. Either FILE_PER_CLASS or PRELOAD.
     *
     * @var integer
     */
    protected $_mode;

    /**
     * The file extension of mapping documents.
     *
     * @var string
     */
    protected $_fileExtension;

    /**
     * Any preloaded elements.
     *
     * @var array
     */
    protected $_elements = array();

    /**
     * Initializes a new FileDriver that looks in the given path(s) for mapping
     * documents and operates in the specified operating mode.
     * 
     * @param string|array $paths One or multiple paths where mapping documents can be found.
     * @param integer $mode The operating mode. Either PRELOAD or FILE_PER_CLASS (default).
     */
    public function __construct($paths, $mode = self::FILE_PER_CLASS)
    {
        $this->_paths = $paths;
        $this->_mode = $mode;
    }

    /**
     * Get the file extension used to look for mapping files under
     *
     * @return void
     */
    public function getFileExtension()
    {
        return $this->_fileExtension;
    }

    /**
     * Set the file extension used to look for mapping files under
     *
     * @param string $fileExtension The file extension to set
     * @return void
     */
    public function setFileExtension($fileExtension)
    {
        $this->_fileExtension = $fileExtension;
    }

    /**
     * Get the element of schema meta data for the class from the mapping file.
     * This will lazily load the mapping file if it is not loaded yet
     *
     * @return array $element  The element of schema meta data
     */
    public function getElement($className)
    {
        if (isset($this->_elements[$className])) {
            $element = $this->_elements[$className];
            unset($this->_elements[$className]);
            return $element;
        } else {
            $result = $this->_loadMappingFile($this->_findMappingFile($className));
            return $result[$className];
        }
    }

    /**
     * Gets any preloaded elements.
     * 
     * @return array
     */
    public function getPreloadedElements()
    {
        return $this->_elements;
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
        $isTransient = true;
        if ($this->_mode == self::FILE_PER_CLASS) {
            // check whether file exists
            foreach ((array)$this->_paths as $path) {
                if (file_exists($path . DIRECTORY_SEPARATOR . str_replace('\\', '.', $className) . $this->_fileExtension)) {
                    $isTransient = false;
                    break;
                }
            }
        } else {
            $isTransient = isset($this->_elements[$className]);
        }

        return $isTransient;
    }

    /**
     * Preloads all mapping information found in any documents within the
     * configured paths and returns a list of class names that have been preloaded.
     * 
     * @return array The list of class names that have been preloaded.
     */
    public function preload($force = false)
    {
        if ($this->_mode != self::PRELOAD && ! $force) {
            return array();
        }

        foreach ((array)$this->_paths as $path) {
            if (is_dir($path)) {
                $files = glob($path . '/*');
                foreach ($files as $file) {
                    $this->_elements = array_merge($this->_elements, $this->_loadMappingFile($file));
                }
            } else if (is_file($path)) {
                $this->_elements = array_merge($this->_elements, $this->_loadMappingFile($path));
            }
        }

        return array_keys($this->_elements);
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
        $fileName = null;
        foreach ((array)$this->_paths as $path) {
            $fileName = $path . DIRECTORY_SEPARATOR . str_replace('\\', '.', $className) . $this->_fileExtension;
            if (file_exists($fileName)) {
                break;
            }
        }

        if ($fileName === null) {
            throw MappingException::mappingFileNotFound($className);
        }

        return $fileName;
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
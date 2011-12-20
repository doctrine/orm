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

namespace Doctrine\ORM\Mapping\Driver;

use Doctrine\ORM\Mapping\MappingException;

/**
 * YamlDriver that additionally looks for mapping information in a global file.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @license MIT
 */
class SimplifiedYamlDriver extends YamlDriver
{
    protected $_prefixes = array();
    protected $_globalBasename;
    protected $_classCache;
    protected $_fileExtension = '.orm.yml';

    public function __construct($prefixes)
    {
        $this->addNamespacePrefixes($prefixes);
    }

    public function setGlobalBasename($file)
    {
        $this->_globalBasename = $file;
    }

    public function getGlobalBasename()
    {
        return $this->_globalBasename;
    }

    public function addNamespacePrefixes($prefixes)
    {
        $this->_prefixes = array_merge($this->_prefixes, $prefixes);
        $this->addPaths(array_flip($prefixes));
    }

    public function addNamespacePrefix($prefix, $path)
    {
        $this->_prefixes[$path] = $prefix;
    }

    public function getNamespacePrefixes()
    {
        return $this->_prefixes;
    }

    public function isTransient($className)
    {
        if (null === $this->_classCache) {
            $this->initialize();
        }

        // The mapping is defined in the global mapping file
        if (isset($this->_classCache[$className])) {
            return false;
        }

        try {
            $this->_findMappingFile($className);

            return false;
        } catch (MappingException $e) {
            return true;
        }
    }

    public function getAllClassNames()
    {
        if (null === $this->_classCache) {
            $this->initialize();
        }

        $classes = array();

        if ($this->_paths) {
            foreach ((array) $this->_paths as $path) {
                if (!is_dir($path)) {
                    throw MappingException::fileMappingDriversRequireConfiguredDirectoryPath($path);
                }

                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($path),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($iterator as $file) {
                    $fileName = $file->getBasename($this->_fileExtension);

                    if ($fileName == $file->getBasename() || $fileName == $this->_globalBasename) {
                        continue;
                    }

                    // NOTE: All files found here means classes are not transient!
                    if (isset($this->_prefixes[$path])) {
                        $classes[] = $this->_prefixes[$path].'\\'.str_replace('.', '\\', $fileName);
                    } else {
                        $classes[] = str_replace('.', '\\', $fileName);
                    }
                }
            }
        }

        return array_merge($classes, array_keys($this->_classCache));
    }

    public function getElement($className)
    {
        if (null === $this->_classCache) {
            $this->initialize();
        }

        if (!isset($this->_classCache[$className])) {
            $this->_classCache[$className] = parent::getElement($className);
        }

        return $this->_classCache[$className];
    }

    protected function initialize()
    {
        $this->_classCache = array();
        if (null !== $this->_globalBasename) {
            foreach ($this->_paths as $path) {
                if (is_file($file = $path.'/'.$this->_globalBasename.$this->_fileExtension)) {
                    $this->_classCache = array_merge($this->_classCache, $this->_loadMappingFile($file));
                }
            }
        }
    }

    protected function _findMappingFile($className)
    {
        $defaultFileName = str_replace('\\', '.', $className).$this->_fileExtension;
        foreach ($this->_paths as $path) {
            if (!isset($this->_prefixes[$path])) {
                if (is_file($path.DIRECTORY_SEPARATOR.$defaultFileName)) {
                    return $path.DIRECTORY_SEPARATOR.$defaultFileName;
                }

                continue;
            }

            $prefix = $this->_prefixes[$path];

            if (0 !== strpos($className, $prefix.'\\')) {
                continue;
            }

            $filename = $path.'/'.strtr(substr($className, strlen($prefix)+1), '\\', '.').$this->_fileExtension;
            if (is_file($filename)) {
                return $filename;
            }

            throw MappingException::mappingFileNotFound($className, $filename);
        }

        throw MappingException::mappingFileNotFound($className, substr($className, strrpos($className, '\\') + 1).$this->_fileExtension);
    }
}

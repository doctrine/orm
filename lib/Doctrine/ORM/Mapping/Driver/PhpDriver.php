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

use Doctrine\Common\DoctrineException,
    Doctrine\Common\Cache\ArrayCache,
    Doctrine\Common\Annotations\AnnotationReader,
    Doctrine\DBAL\Schema\AbstractSchemaManager,
    Doctrine\ORM\Mapping\ClassMetadataInfo,
    Doctrine\ORM\Mapping\MappingException,
    Doctrine\Common\Util\Inflector;

/**
 * The PhpDriver includes php files which just populate ClassMetadataInfo
 * instances with plain php code
 *
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link    www.doctrine-project.org
 * @since   2.0
 * @version $Revision$
 * @author  Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author  Jonathan Wage <jonwage@gmail.com>
 * @author  Roman Borschel <roman@code-factory.org>
 */
class PhpDriver implements Driver
{
    /** The directory path to look in for php files */
    private $_directory;

    /** The array of class names found and the path to the file */
    private $_classPaths = array();

    public function __construct($directory)
    {
        $this->_directory = $directory;
    }

    public function loadMetadataForClass($className, ClassMetadataInfo $metadata)
    {
        $path = $this->_classPaths[$className];
        include $path;
    }

    public function isTransient($className)
    {
        return true;
    }

    /**
     * Preloads all mapping information found in any documents within the
     * configured paths and returns a list of class names that have been preloaded.
     * 
     * @return array The list of class names that have been preloaded.
     */
    public function preload()
    {
        if ( ! is_dir($this->_directory)) {
            throw MappingException::phpDriverRequiresConfiguredDirectoryPath();
        }
        $iter = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->_directory),
                                              \RecursiveIteratorIterator::LEAVES_ONLY);

        $classes = array();
        foreach ($iter as $item) {
            $info = pathinfo($item->getPathName());
            if (! isset($info['extension']) || $info['extension'] != 'php') {
                continue;
            }
            $className = $info['filename'];
            $classes[] = $className;
            $this->_classPaths[$className] = $item->getPathName();
        }

        return $classes;
    }
}
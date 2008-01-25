<?php
/*
 *  $Id: Diff.php 1080 2007-02-10 18:17:08Z jwage $
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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Migration_Diff
 *
 * @package     Doctrine
 * @subpackage  Migration
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Doctrine_Migration_Diff
{
    protected $_from,
              $_to,
              $_changes = array(),
              $_migrationsPath;

    public function __construct($from = null, $to = null)
    {
        $this->_from = $from;
        $this->_to = $to;
    }

    protected function getUniqueId()
    {
        return md5($this->_from.$this->_to);
    }

    public function setMigrationsPath($migrationsPath)
    {
        $this->_migrationsPath = $migrationsPath;
    }

    public function generate()
    {
        $from = $this->_generateModels('From', $this->_from);
        $to = $this->_generateModels('To', $this->_to);

        $differences = $this->_diff($from, $to);

        print_r($differences);
    }

    protected function _diff($from, $to)
    {
        $fromTmpPath = sys_get_temp_dir() . $this->getUniqueId() . '_from';
        $toTmpPath = sys_get_temp_dir() . $this->getUniqueId() . '_to';

        if ( ! file_exists($fromTmpPath)) {
            $fromModels = Doctrine::loadModels($from);
            $fromInfo = $this->_buildModelInformation($fromModels);

            file_put_contents($fromTmpPath, serialize($fromInfo));
        } else {
            if ( ! file_exists($toTmpPath)) {
                $toModels = Doctrine::loadModels($to);
                $toInfo = $this->_buildModelInformation($toModels);

                file_put_contents($toTmpPath, serialize($toInfo));
            } else {
                $fromInfo = unserialize(file_get_contents($fromTmpPath));
                $toInfo = unserialize(file_get_contents($toTmpPath));

                $this->_buildChanges($fromInfo, $toInfo);

                // clean up
                unlink($fromTmpPath);
                unlink($toTmpPath);
                Doctrine_Lib::removeDirectories(sys_get_temp_dir() . 'from_doctrine_tmp_dirs');
                Doctrine_Lib::removeDirectories(sys_get_temp_dir() . 'to_doctrine_tmp_dirs');
            }
        }
    }
    
    protected function _buildChanges($from, $to)
    {
        foreach ($to as $key => $model) {
            $columns = $model['columns'];
            
            foreach ($columns as $columnKey => $column) {
                //if (isset($to[$key]['columns'][$columnKey]))
            }
        }
    }

    protected function _buildModelInformation(array $models)
    {
        $info = array();

        foreach ($models as $key => $model) {
            $info[$model] = Doctrine::getTable($model)->getExportableFormat();
        }

        return $info;
    }

    protected function _generateModels($prefix, $item)
    {
        $path = sys_get_temp_dir() . $prefix . '_doctrine_tmp_dirs';

        if ( is_dir($item)) {
            $files = glob($item . DIRECTORY_SEPARATOR . '*.*');

            if (isset($files[0])) {
                $pathInfo = pathinfo($files[0]);
                $extension = $pathInfo['extension'];
            }

            if ($extension === 'yml') {
                Doctrine::generateModelsFromYaml($item, $path);

                return $path;
            } else if ($extension === 'php') {

                Doctrine_Lib::copyDirectory($item, $path);

                return $path;
            } else {
                throw new Doctrine_Migration_Exception('No php or yml files found at path: "' . $item . '"');
            }
        } else {
            try {
                $connection = Doctrine_Manager::getInstance()->getConnection($item);

                Doctrine::generateModelsFromDb($path, array($item));

                return $path;
            } catch (Exception $e) {
                throw new Doctrine_Migration_Exception('Could not generate models from connection: ' . $e->getMessage());
            }
        }
    }
}
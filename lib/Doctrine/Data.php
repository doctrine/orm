<?php
/*
 *  $Id: Data.php 2552 2007-09-19 19:33:00Z Jonathan.Wage $
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
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine_Data
 * 
 * Base Doctrine_Data class for dumping and loading data to and from fixtures files.
 * Support formats are based on what formats are available in Doctrine_Parser such as yaml, xml, json, etc.
 *
 * @package     Doctrine
 * @subpackage  Data
 * @author      Jonathan H. Wage <jwage@mac.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 2552 $
 */
class Doctrine_Data
{
    /**
     * formats
     *
     * array of formats data can be in
     *
     * @var string
     */
    protected $_formats = array('csv', 'yml', 'xml');

    /**
     * format
     * 
     * the default and current format we are working with
     *
     * @var string
     */
    protected $_format = 'yml';

    /**
     * directory
     *
     * array of directory/yml paths or single directory/yml file
     *
     * @var string
     */
    protected $_directory = null;

    /**
     * models
     *
     * specified array of models to use
     *
     * @var string
     */
    protected $_models = array();

    /**
     * _exportIndividualFiles
     *
     * whether or not to export data to individual files instead of 1
     *
     * @var string
     */
    protected $_exportIndividualFiles = false;

    /**
     * setFormat
     *
     * Set the current format we are working with
     * 
     * @param string $format 
     * @return void
     */
    public function setFormat($format)
    {
        $this->_format = $format;
    }

    /**
     * getFormat
     *
     * Get the current format we are working with
     * 
     * @return void
     */
    public function getFormat()
    {
        return $this->_format;
    }

    /**
     * getFormats 
     *
     * Get array of available formats
     * 
     * @return void
     */
    public function getFormats()
    {
        return $this->_formats;
    }

    /**
     * setDirectory
     *
     * Set the array/string of directories or yml file paths
     * 
     * @return void
     */
    public function setDirectory($directory)
    {
        $this->_directory = $directory;
    }

    /**
     * getDirectory
     *
     * Get directory for dumping/loading data from and to
     * 
     * @return void
     */
    public function getDirectory()
    {
        return $this->_directory;
    }

    /**
     * setModels
     *
     * Set the array of specified models to work with
     * 
     * @param string $models 
     * @return void
     */
    public function setModels($models)
    {
        $this->_models = $models;
    }

    /**
     * getModels
     *
     * Get the array of specified models to work with
     *
     * @return void
     */
    public function getModels()
    {
        return $this->_models;
    }

    /**
     * _exportIndividualFiles 
     *
     * Set/Get whether or not to export individual files
     * 
     * @return bool $_exportIndividualFiles
     */
    public function exportIndividualFiles($bool = null)
    {
        if ($bool !== null) {
            $this->_exportIndividualFiles = $bool;
        }
        
        return $this->_exportIndividualFiles;
    }

    /**
     * exportData
     *
     * Interface for exporting data to fixtures files from Doctrine models
     *
     * @param string $directory 
     * @param string $format 
     * @param string $models 
     * @param string $_exportIndividualFiles 
     * @return void
     */
    public function exportData($directory, $format = 'yml', $models = array(), $_exportIndividualFiles = false)
    {
        $export = new Doctrine_Data_Export($directory);
        $export->setFormat($format);
        $export->setModels($models);
        $export->exportIndividualFiles($_exportIndividualFiles);
        
        return $export->doExport();
    }

    /**
     * importData
     *
     * Interface for importing data from fixture files to Doctrine models
     *
     * @param string $directory 
     * @param string $format 
     * @param string $models 
     * @return void
     */
    public function importData($directory, $format = 'yml', $models = array())
    {
        $import = new Doctrine_Data_Import($directory);
        $import->setFormat($format);
        $import->setModels($models);
        
        return $import->doImport();
    }

    /**
     * importDummyData
     *
     * Interface for importing dummy data to models
     * 
     * @param string $num 
     * @param string $models 
     * @return void
     */
    public function importDummyData($num = 3, $models = array())
    {
        $import = new Doctrine_Data_Import();
        $import->setModels($models);
        
        return $import->doImportDummyData($num);
    }

    /**
     * isRelation
     *
     * Check if a fieldName on a Doctrine_Record is a relation, if it is we return that relationData
     * 
     * @param string $Doctrine_Record 
     * @param string $fieldName 
     * @return void
     */
    public function isRelation(Doctrine_Record $record, $fieldName)
    {
        $relations = $record->getTable()->getRelations();
        
        foreach ($relations as $relation) {
            $relationData = $relation->toArray();
            
            if ($relationData['local'] === $fieldName) {
                return $relationData;
            }
            
        }
        
        return false;
    }

    /**
     * purge
     * 
     * Purge all data for loaded models or for the passed array of Doctrine_Records
     *
     * @param string $models 
     * @return void
     */
    public function purge($models = array())
    {
        $models = Doctrine::filterInvalidModels($models);

        foreach ($models as $model)
        {
            $model = new $model();

            $model->getTable()->createQuery()->delete($model)->execute();
        }
    }
}
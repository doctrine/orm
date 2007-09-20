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
 * Base Doctrine_Data class
 *
 * @package     Doctrine
 * @author      Jonathan H. Wage <jwage@mac.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
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
    public $formats = array('csv', 'yml', 'xml');
    /**
     * format
     * 
     * the default and current format we are working with
     *
     * @var string
     */
    public $format = 'yml';
    /**
     * directory
     *
     * array of directory/yml paths or single directory/yml file
     *
     * @var string
     */
    public $directory = null;
    /**
     * models
     *
     * specified array of models to use
     *
     * @var string
     */
    public $models = array();
    /**
     * exportIndividualFiles
     *
     * whether or not to export data to individual files instead of 1
     *
     * @var string
     */
    public $exportIndividualFiles = false;
    /**
     * setFormat
     *
     * Set the current format we are working with
     * 
     * @param string $format 
     * @return void
     * @author Jonathan H. Wage
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }
    /**
     * getFormat
     *
     * Get the current format we are working with
     * 
     * @return void
     * @author Jonathan H. Wage
     */
    public function getFormat()
    {
        return $this->format;
    }
    /**
     * getFormats 
     *
     * Get array of available formats
     * 
     * @author Jonathan H. Wage
     */
    public function getFormats()
    {
        return $this->formats;
    }
    /**
     * setDirectory
     *
     * Set the array/string of directories or yml file paths
     * 
     * @author Jonathan H. Wage
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
    }
    /**
     * getDirectory
     *
     * Get directory to work with
     * 
     * @return void
     * @author Jonathan H. Wage
     */
    public function getDirectory()
    {
        return $this->directory;
    }
    /**
     * setModels
     *
     * Set the array of specified models to work with
     * 
     * @param string $models 
     * @return void
     * @author Jonathan H. Wage
     */
    public function setModels($models)
    {
        $this->models = $models;
    }
    /**
     * getModels
     *
     * Get the array of specified models to work with
     *
     * @return void
     * @author Jonathan H. Wage
     */
    public function getModels()
    {
        return $this->models;
    }
    /**
     * exportIndividualFiles 
     *
     * Set/Get whether or not to export individual files
     * 
     * @author Jonathan H. Wage
     */
    public function exportIndividualFiles($bool = null)
    {
        if ($bool !== null) {
            $this->exportIndividualFiles = $bool;
        }
        
        return $this->exportIndividualFiles;
    }
    /**
     * exportData
     *
     * Interface for exporting data to fixtures files from Doctrine models
     *
     * @param string $directory 
     * @param string $format 
     * @param string $models 
     * @param string $exportIndividualFiles 
     * @return void
     * @author Jonathan H. Wage
     */
    public function exportData($directory, $format = 'yml', $models = array(), $exportIndividualFiles = false)
    {
        $export = new Doctrine_Data_Export($directory);
        $export->setFormat($format);
        $export->setModels($models);
        $export->exportIndividualFiles($exportIndividualFiles);
        
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
     * @author Jonathan H. Wage
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
     * @author Jonathan H. Wage
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
     * @author Jonathan H. Wage
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
}
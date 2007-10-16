<?php
/*
 *  $Id: Process.php 1080 2007-02-10 18:17:08Z jwage $
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
 * Doctrine_Migration_Process
 *
 * @package     Doctrine
 * @subpackage  Migration
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1080 $
 * @author      Jonathan H. Wage <jwage@mac.com>
 */
class Doctrine_Migration_Process
{
    public function getConnection($tableName)
    {
        return Doctrine::getConnectionByTableName($tableName);
    }
    
    public function processCreatedTables($tables)
    {
        foreach ($tables as $table) {
            $conn = $this->getConnection($table['tableName']);
            
            $conn->export->createTable($table['tableName'], $table['fields'], $table['options']);
        }
    }
    
    public function processDroppedTables($tables)
    {
        foreach ($tables as $table) {
            $conn = $this->getConnection($table['tableName']);
            
            $conn->export->dropTable($table['tableName']);
        }
    }
    
    public function processRenamedTables($tables)
    {
        foreach ($tables as $table) {
            $conn = $this->getConnection($table['newTableName']);
            
            $conn->export->alterTable($table['oldTableName'], array('name' => $table['newTableName']));
        }
    }
    
    public function processAddedColumns($columns)
    {
        foreach ($columns as $column) {
            $conn = $this->getConnection($column['tableName']);
            
            $options = array();
            $options = $column['options'];
            $options['type'] = $column['type'];
            
            $conn->export->alterTable($column['tableName'], array('add' => array($column['columnName'] => $options)));
        }
    }
    
    public function processRenamedColumns($columns)
    {
        foreach ($columns as $column) {
            $conn = $this->getConnection($column['tableName']);
            
            $conn->export->alterTable($column['tableName'], array('rename' => array($column['oldColumnName'] => array('name' => $column['newColumnName']))));
        }
    }
    
    public function processChangedColumns($columns)
    {
        foreach ($columns as $column) {
            $conn = $this->getConnection($column['tableName']);
            
            $options = array();
            $options = $column['options'];
            $options['type'] = $column['type'];
            
            $conn->export->alterTable($column['tableName'], array('change' => array($column['columnName'] => array('definition' => $options))));
        }  
    }
    
    public function processRemovedColumns($columns)
    {
        foreach ($columns as $column) {
            $conn = $this->getConnection($column['tableName']);
            
            $conn->export->alterTable($column['tableName'], array('remove' => array($column['columnName'] => array())));
        }
    }
    
    public function processAddedIndexes($indexes)
    {
        foreach ($indexes as $index) {
            $conn = $this->getConnection($index['tableName']);
            
            $conn->export->createIndex($index['tableName'], $index['indexName'], $index['definition']);
        }
    }
    
    public function processRemovedIndexes($indexes)
    {
        foreach ($indexes as $index) {
            $conn = $this->getConnection($index['tableName']);
            
            $conn->export->dropIndex($index['tableName'], $index['indexName']);
        } 
    }
    
    public function processCreatedConstraints($constraints)
    {
        foreach ($constraints as $constraint) {
            $conn = $this->getConnection($constraint['tableName']);
            $conn->export->createConstraint($constraint['tableName'], $constraint['constraintName'],
                    $constraint['definition']);
        }
    }
    
    public function processDroppedConstraints($constraints)
    {
        foreach ($constraints as $constraint) {
            $conn = $this->getConnection($constraint['tableName']);
            $conn->export->dropConstraint($constraint['tableName'], $constraint['constraintName'],
                    $constraint['primary']);
        }
    }
}
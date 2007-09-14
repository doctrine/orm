<?php
/*
 * $Id: Yml.php 1838 2007-06-26 00:58:21Z nicobn $
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
 * class Doctrine_Import_Schema_Yml
 *
 * Different methods to import a YML schema. The logic behind using two different
 * methods is simple. Some people will like the idea of producing Doctrine_Record
 * objects directly, which is totally fine. But in fast and growing application,
 * table definitions tend to be a little bit more volatile. importArr() can be used
 * to output a table definition in a PHP file. This file can then be stored 
 * independantly from the object itself.
 *
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @version     $Revision: 1838 $
 * @author      Nicolas Bérard-Nault <nicobn@gmail.com>
 * @author      Jonathan H. Wage <jonwage@gmail.com>
 */
class Doctrine_Import_Schema_Yml extends Doctrine_Import_Schema
{    
    /**
     * parseSchema
     *
     * A method to parse a Yml Schema and translate it into a property array. 
     * The function returns that property array.
     *
     * @param  string $schema   Path to the file containing the XML schema
     * @return array
     */
    public function parseSchema($schema)
    {
        $array = $this->parse($schema);
        foreach ($array as $tableName => $table) {
            $columns = array();
            
            $tableName = isset($table['tableName']) ? (string) $table['tableName']:(string) $tableName;
            $className = isset($table['className']) ? (string) $table['className']:(string) $tableName;
            
            foreach ($table['columns'] as $columnName => $field) {
                
                $colDesc = array();
                $colDesc['name'] = isset($field['name']) ? (string) $field['name']:$columnName;
                $colDesc['type'] = isset($field['type']) ? (string) $field['type']:null;
                $colDesc['ptype'] = isset($field['ptype']) ? (string) $field['ptype']:(string) $colDesc['type'];
                $colDesc['length'] = isset($field['length']) ? (int) $field['length']:null;
                $colDesc['fixed'] = isset($field['fixed']) ? (int) $field['fixed']:null;
                $colDesc['unsigned'] = isset($field['unsigned']) ? (bool) $field['unsigned']:null;
                $colDesc['primary'] = isset($field['primary']) ? (bool) (isset($field['primary']) && $field['primary']):null;
                $colDesc['default'] = isset($field['default']) ? (string) $field['default']:null;
                $colDesc['notnull'] = isset($field['notnull']) ? (bool) (isset($field['notnull']) && $field['notnull']):null;
                $colDesc['autoinc'] = isset($field['autoinc']) ? (bool) (isset($field['autoinc']) && $field['autoinc']):null;
                $colDesc['foreignClass'] = isset($field['foreignClass']) ? (string) $field['foreignClass']:null;
                $colDesc['foreignReference'] = isset($field['foreignReference']) ? (string) $field['foreignReference']:null;
                $colDesc['localName'] = isset($field['localName']) ? (string) $field['localName']:null;
                $colDesc['foreignName'] = isset($field['foreignName']) ? (string) $field['foreignName']:null;
                $colDesc['counterpart'] = isset($field['counterpart']) ? (string) $field['counterpart']:null;
                $colDesc['onDelete'] = isset($field['onDelete']) ? (string) $field['onDelete']:null;
                $colDesc['onUpdate'] = isset($field['onUpdate']) ? (string) $field['onUpdate']:null;
                
                $columns[(string) $colDesc['name']] = $colDesc;
            }

            $tables[$tableName]['tableName'] = $tableName;
            $tables[$tableName]['className'] = $className;

            $tables[$tableName]['columns'] = $columns;
        }
        
        return $tables;
    }
}
<?php
/*
 * $Id: Xml.php 1838 2007-06-26 00:58:21Z nicobn $
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
 * class Doctrine_Import_Xml
 *
 * Different methods to import a XML schema. The logic behind using two different
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
class Doctrine_Import_Schema_Xml extends Doctrine_Import_Schema
{    
    /**
     * parseSchema
     *
     * A method to parse a XML Schema and translate it into a property array. 
     * The function returns that property array.
     *
     * @param  string $schema   Path to the file containing the XML schema
     * @return array
     */
    public function parseSchema($schema)
    {        
        $xmlObj = $this->parse($schema);
        
        foreach ($xmlObj->tables as $table) {
            $columns = array();
            
            // Go through all columns... 
            foreach ($table->columns as $column) {
                $colDesc = array(
                    'name'      => (string) $column->name,
                    'type'      => (string) $column->type,
                    'ptype'     => (string) $column->type,
                    'length'    => (int) $column->length,
                    'fixed'     => (int) $column->fixed,
                    'unsigned'  => (bool) $column->unsigned,
                    'primary'   => (bool) (isset($column->primary) && $column->primary),
                    'default'   => (string) $column->default,
                    'notnull'   => (bool) (isset($column->notnull) && $column->notnull),
                    'autoinc'   => (bool) (isset($column->autoincrement) && $column->autoincrement),
                );
            
                $columns[(string) $column->name] = $colDesc;
            }
            
            $class = $table->class ? (string) $table->class:(string) $table->name;
            
            $tables[(string) $table->name]['name'] = (string) $table->name;
            $tables[(string) $table->name]['class'] = (string) $class;
            
            $tables[(string) $table->name]['columns'] = $columns;
        }
        
        return $tables;
    }
}
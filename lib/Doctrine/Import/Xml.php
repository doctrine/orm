<?php
/*
 * $Id$
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
 * class Doctrine_Import_XML
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
 * @version     $Revision$
 * @author      Nicolas Bérard-Nault <nicobn@gmail.com>
 */
class Doctrine_Import_Xml
{
    /**
     * importObj
     *
     * A method to import a XML Schema and translate it into a Doctrine_Record object
     *
     * @param string $schema       The file containing the XML schema
     * @param string $directory    The directory where the Doctrine_Record classes will
     *                             be written
     * @static
     */
    public function importObj($schema, $directory)
    {
        $Builder = new Doctrine_Import_Builder();
        $Builder->setTargetPath($directory);

        $Arr = self::importArr($schema);
       
        foreach ($arr as $name => $columns) {
            $Builder->buildRecord($name, $columns);
        }
    }

    /**
     * importArr
     *
     * A method to import a XML Schema and translate it into a property array. 
     * The function returns that property array.
     *
     * @param  string $schema   Path to the file containing the XML schema
     * @return array
     * @static
     */
    public static function importArr($schema)
    {
        if (!is_readable($schema)) {
            throw new Doctrine_Import_Exception('Could not read schema file '. $schema);
        }
        
        $xmlObj = simplexml_load_file($schema);
      
        // Go through all tables...
        foreach ($xmlObj->table as $table) {
            // Go through all columns... 
            foreach ($table->declaration->field as $field) {
                $colDesc = array(
                    'name'      => (string) $field->name,
                    'type'      => (string) $field->type,
                    'ptype'     => (string) $field->type,
                    'length'    => (int) $field->length,
                    'fixed'     => (int) $field->fixed,
                    'unsigned'  => (bool) $field->unsigned,
                    'primary'   => (bool) (isset($field->primary) && $field->primary),
                    'default'   => (string) $field->default,
                    'notnull'   => (bool) (isset($field->notnull) && $field->notnull),
                    'autoinc'   => (bool) (isset($field->autoincrement) && $field->autoincrement),
                );
            
                $columns[(string) $field->name] = $colDesc;
            }
            
            $tables[(string) $table->name] = $columns;
        }
        
        return $tables;
    }
}

?>

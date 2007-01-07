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
 * <http://www.phpdoctrine.com>.
 */
/**
 * Doctrine_Compiler
 * This class can be used for compiling the entire Doctrine framework into a single file
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Compiler
{
    /**
     * @var array $classes          an array containing all runtime classes of Doctrine framework
     */
    private static $classes = array(
                                   'Doctrine_Access',
                                   'Doctrine_Adapter_Interface',
                                   'Doctrine_Adapter_Statement',
                                   'Doctrine_Adapter',
                                   'Doctrine_Cache_Query_Sqlite',
                                   'Doctrine_Collection_Batch',
                                   'Doctrine_Collection_Exception',
                                   'Doctrine_Collection_Immediate',
                                   'Doctrine_Collection_Iterator_Expandable',
                                   'Doctrine_Collection_Iterator_Normal',
                                   'Doctrine_Collection_Iterator_Offset',
                                   'Doctrine_Collection_Iterator',
                                   'Doctrine_Collection_Lazy',
                                   'Doctrine_Collection_Offset',
                                   'Doctrine_Collection',
                                   'Doctrine_Compiler_Exception',
                                   'Doctrine_Compiler',
                                   'Doctrine_Configurable',
                                   'Doctrine_Connection_Common',
                                   'Doctrine_Connection_Db2',
                                   'Doctrine_Connection_Exception',
                                   'Doctrine_Connection_Firebird_Exception',
                                   'Doctrine_Connection_Firebird',
                                   'Doctrine_Connection_Informix_Exception',
                                   'Doctrine_Connection_Informix',
                                   'Doctrine_Connection_Mock',
                                   'Doctrine_Connection_Module',
                                   'Doctrine_Connection_Mssql_Exception',
                                   'Doctrine_Connection_Mssql',
                                   'Doctrine_Connection_Mysql_Exception',
                                   'Doctrine_Connection_Mysql',
                                   'Doctrine_Connection_Oracle_Exception',
                                   'Doctrine_Connection_Oracle',
                                   'Doctrine_Connection_Pgsql_Exception',
                                   'Doctrine_Connection_Pgsql',
                                   'Doctrine_Connection_Sqlite_Exception',
                                   'Doctrine_Connection_Sqlite',
                                   'Doctrine_Connection_UnitOfWork',
                                   'Doctrine_Connection',
                                   'Doctrine_DataDict_Exception',
                                   'Doctrine_DataDict_Firebird_Exception',
                                   'Doctrine_DataDict_Firebird',
                                   'Doctrine_DataDict_Informix_Exception',
                                   'Doctrine_DataDict_Informix',
                                   'Doctrine_DataDict_Mssql_Exception',
                                   'Doctrine_DataDict_Mssql',
                                   'Doctrine_DataDict_Mysql_Exception',
                                   'Doctrine_DataDict_Mysql',
                                   'Doctrine_DataDict_Oracle_Exception',
                                   'Doctrine_DataDict_Oracle',
                                   'Doctrine_DataDict_Pgsql_Exception',
                                   'Doctrine_DataDict_Pgsql',
                                   'Doctrine_DataDict_Sqlite_Exception',
                                   'Doctrine_DataDict_Sqlite',
                                   'Doctrine_DataDict',
                                   'Doctrine_Db_Event',
                                   'Doctrine_Db_EventListener_Chain',
                                   'Doctrine_Db_EventListener_Interface',
                                   'Doctrine_Db_EventListener',
                                   'Doctrine_Db_Exception',
                                   'Doctrine_Db_Mock',
                                   'Doctrine_Db_Profiler_Exception',
                                   'Doctrine_Db_Profiler_Query',
                                   'Doctrine_Db_Profiler',
                                   'Doctrine_Db_Statement',
                                   'Doctrine_Db',
                                   'Doctrine_EventListener_AccessorInvoker',
                                   'Doctrine_EventListener_Chain',
                                   'Doctrine_EventListener_Debugger',
                                   'Doctrine_EventListener_Empty',
                                   'Doctrine_EventListener_Interface',
                                   'Doctrine_EventListener',
                                   'Doctrine_Exception',
                                   'Doctrine_Export_Exception',
                                   'Doctrine_Export_Firebird_Exception',
                                   'Doctrine_Export_Firebird',
                                   'Doctrine_Export_Informix_Exception',
                                   'Doctrine_Export_Mssql_Exception',
                                   'Doctrine_Export_Mssql',
                                   'Doctrine_Export_Mysql_Exception',
                                   'Doctrine_Export_Mysql',
                                   'Doctrine_Export_Oracle_Exception',
                                   'Doctrine_Export_Oracle',
                                   'Doctrine_Export_Pgsql_Exception',
                                   'Doctrine_Export_Pgsql',
                                   'Doctrine_Export_Reporter',
                                   'Doctrine_Export_Sqlite_Exception',
                                   'Doctrine_Export_Sqlite',
                                   'Doctrine_Export',
                                   'Doctrine_Expression_Exception',
                                   'Doctrine_Expression_Firebird',
                                   'Doctrine_Expression_Informix',
                                   'Doctrine_Expression_Mssql',
                                   'Doctrine_Expression_Mysql',
                                   'Doctrine_Expression_Oracle',
                                   'Doctrine_Expression_Pgsql',
                                   'Doctrine_Expression_Sqlite',
                                   'Doctrine_Expression',
                                   'Doctrine_Hook_Equal',
                                   'Doctrine_Hook_Integer',
                                   'Doctrine_Hook_Parser_Complex',
                                   'Doctrine_Hook_Parser',
                                   'Doctrine_Hook_WordLike',
                                   'Doctrine_Hook',
                                   'Doctrine_Hydrate_Alias',
                                   'Doctrine_Hydrate',
                                   'Doctrine_Identifier',
                                   'Doctrine_Import_Builder_BaseClass',
                                   'Doctrine_Import_Builder_Exception',
                                   'Doctrine_Import_Builder',
                                   'Doctrine_Import_Exception',
                                   'Doctrine_Import_Firebird_Exception',
                                   'Doctrine_Import_Firebird',
                                   'Doctrine_Import_Informix_Exception',
                                   'Doctrine_Import_Informix',
                                   'Doctrine_Import_Mssql_Exception',
                                   'Doctrine_Import_Mssql',
                                   'Doctrine_Import_Mysql_Exception',
                                   'Doctrine_Import_Mysql',
                                   'Doctrine_Import_Oracle_Exception',
                                   'Doctrine_Import_Oracle',
                                   'Doctrine_Import_Pgsql_Exception',
                                   'Doctrine_Import_Pgsql',
                                   'Doctrine_Import_Reader_Db',
                                   'Doctrine_Import_Reader_Exception',
                                   'Doctrine_Import_Reader_Propel',
                                   'Doctrine_Import_Reader',
                                   'Doctrine_Import_Sqlite_Exception',
                                   'Doctrine_Import_Sqlite',
                                   'Doctrine_Import',
                                   'Doctrine_Index',
                                   'Doctrine_Lib',
                                   'Doctrine_Locking_Exception',
                                   'Doctrine_Locking_Manager_Pessimistic',
                                   'Doctrine_Manager_Exception',
                                   'Doctrine_Manager',
                                   'Doctrine_MPath',
                                   'Doctrine_Null',
                                   'Doctrine_Overloadable',
                                   'Doctrine_Query_Condition',
                                   'Doctrine_Query_Exception',
                                   'Doctrine_Query_From',
                                   'Doctrine_Query_Groupby',
                                   'Doctrine_Query_Having',
                                   'Doctrine_Query_Identifier',
                                   'Doctrine_Query_JoinCondition',
                                   'Doctrine_Query_Orderby',
                                   'Doctrine_Query_Part',
                                   'Doctrine_Query_Set',
                                   'Doctrine_Query_Where',
                                   'Doctrine_Query',
                                   'Doctrine_RawSql_Exception',
                                   'Doctrine_RawSql',
                                   'Doctrine_Record_Exception',
                                   'Doctrine_Record_Iterator',
                                   'Doctrine_Record_State_Exception',
                                   'Doctrine_Record_ValueWrapper_Interface',
                                   'Doctrine_Record',
                                   'Doctrine_Relation_Association_Self',
                                   'Doctrine_Relation_Association',
                                   'Doctrine_Relation_ForeignKey',
                                   'Doctrine_Relation_LocalKey',
                                   'Doctrine_Relation',
                                   'Doctrine_Reporter',
                                   'Doctrine_Reverse_Mssql',
                                   'Doctrine_Schema_Column',
                                   'Doctrine_Schema_Database',
                                   'Doctrine_Schema_Exception',
                                   'Doctrine_Schema_Object',
                                   'Doctrine_Schema_Relation',
                                   'Doctrine_Schema_Table',
                                   'Doctrine_Schema',
                                   'Doctrine_Sequence_Db2_Exception',
                                   'Doctrine_Sequence_Db2',
                                   'Doctrine_Sequence_Exception',
                                   'Doctrine_Sequence_Firebird_Exception',
                                   'Doctrine_Sequence_Firebird',
                                   'Doctrine_Sequence_Informix_Exception',
                                   'Doctrine_Sequence_Informix',
                                   'Doctrine_Sequence_Mssql_Exception',
                                   'Doctrine_Sequence_Mssql',
                                   'Doctrine_Sequence_Mysql_Exception',
                                   'Doctrine_Sequence_Mysql',
                                   'Doctrine_Sequence_Oracle_Exception',
                                   'Doctrine_Sequence_Oracle',
                                   'Doctrine_Sequence_Pgsql_Exception',
                                   'Doctrine_Sequence_Pgsql',
                                   'Doctrine_Sequence_Sqlite_Exception',
                                   'Doctrine_Sequence_Sqlite',
                                   'Doctrine_Sequence',
                                   'Doctrine_Struct',
                                   'Doctrine_Table_Exception',
                                   'Doctrine_Table_Repository_Exception',
                                   'Doctrine_Table_Repository',
                                   'Doctrine_Table',
                                   'Doctrine_Transaction_Exception',
                                   'Doctrine_Transaction_Firebird_Exception',
                                   'Doctrine_Transaction_Firebird',
                                   'Doctrine_Transaction_Informix_Exception',
                                   'Doctrine_Transaction_Informix',
                                   'Doctrine_Transaction_Mssql_Exception',
                                   'Doctrine_Transaction_Mssql',
                                   'Doctrine_Transaction_Mysql_Exception',
                                   'Doctrine_Transaction_Mysql',
                                   'Doctrine_Transaction_Oracle_Exception',
                                   'Doctrine_Transaction_Oracle',
                                   'Doctrine_Transaction_Pgsql_Exception',
                                   'Doctrine_Transaction_Pgsql',
                                   'Doctrine_Transaction_Sqlite_Exception',
                                   'Doctrine_Transaction_Sqlite',
                                   'Doctrine_Transaction',
                                   'Doctrine_Validator_Country',
                                   'Doctrine_Validator_Creditcard',
                                   'Doctrine_Validator_Date',
                                   'Doctrine_Validator_Email',
                                   'Doctrine_Validator_Enum',
                                   'Doctrine_Validator_ErrorStack',
                                   'Doctrine_Validator_Exception',
                                   'Doctrine_Validator_Htmlcolor',
                                   'Doctrine_Validator_Interface.class',
                                   'Doctrine_Validator_Ip',
                                   'Doctrine_Validator_Nospace',
                                   'Doctrine_Validator_Notblank',
                                   'Doctrine_Validator_Notnull',
                                   'Doctrine_Validator_Protected',
                                   'Doctrine_Validator_Range',
                                   'Doctrine_Validator_Regexp',
                                   'Doctrine_Validator_Unique',
                                   'Doctrine_Validator_Usstate',
                                   'Doctrine_Validator',
                                   'Doctrine_ValueHolder',
                                   'Doctrine_View_Exception',
                                   'Doctrine_View',
                                              );

    /**
     * getRuntimeClasses
     * returns an array containing all runtime classes of Doctrine framework
     *
     * @return array
     */
    public static function getRuntimeClasses()
    {
        return self::$classes;
    }
    /**
     * method for making a single file of most used doctrine runtime components
     * including the compiled file instead of multiple files (in worst
     * cases dozens of files) can improve performance by an order of magnitude
     *
     * @throws Doctrine_Compiler_Exception      if something went wrong during the compile operation
     * @return void
     */
    public static function compile($target = null)
    {
        $path = Doctrine::getPath();

        $classes = self::$classes;

        $ret     = array();

        foreach ($classes as $class) {
            if ($class !== 'Doctrine')
                $class = 'Doctrine_'.$class;

            $file  = $path.DIRECTORY_SEPARATOR.str_replace("_",DIRECTORY_SEPARATOR,$class).".php";

            echo "Adding $file" . PHP_EOL;

            if ( ! file_exists($file)) {
                throw new Doctrine_Compiler_Exception("Couldn't compile $file. File $file does not exists.");
            }
            Doctrine::autoload($class);
            $refl  = new ReflectionClass ( $class );
            $lines = file( $file );

            $start = $refl -> getStartLine() - 1;
            $end   = $refl -> getEndLine();

            $ret = array_merge($ret,
                               array_slice($lines,
                               $start,
                              ($end - $start)));

        }

        if ($target == null) {
            $target = $path.DIRECTORY_SEPARATOR.'Doctrine.compiled.php';
        }

        // first write the 'compiled' data to a text file, so
        // that we can use php_strip_whitespace (which only works on files)
        $fp = @fopen($target, 'w');

        if ($fp === false) {
            throw new Doctrine_Compiler_Exception("Couldn't write compiled data. Failed to open $target");
        }
        fwrite($fp, "<?php".
                    " class InvalidKeyException extends Exception { }".
                    implode('', $ret)
              );
        fclose($fp);

        $stripped = php_strip_whitespace($target);
        $fp = @fopen($target, 'w');
        if ($fp === false) {
            throw new Doctrine_Compiler_Exception("Couldn't write compiled data. Failed to open $file");
        }
        fwrite($fp, $stripped);
        fclose($fp);
    }
}

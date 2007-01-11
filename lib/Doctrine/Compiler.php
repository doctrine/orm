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
                                   'Access',
                                   'Adapter_Interface',
                                   'Adapter_Statement',
                                   'Adapter',
                                   'Cache_Query_Sqlite',
                                   'Collection_Batch',
                                   'Collection_Exception',
                                   'Collection_Immediate',
                                   'Collection_Iterator_Expandable',
                                   'Collection_Iterator_Normal',
                                   'Collection_Iterator_Offset',
                                   'Collection_Iterator',
                                   'Collection_Lazy',
                                   'Collection_Offset',
                                   'Collection',
                                   'Compiler_Exception',
                                   'Compiler',
                                   'Configurable',
                                   'Connection_Common',
                                   'Connection_Db2',
                                   'Connection_Exception',
                                   'Connection_Firebird_Exception',
                                   'Connection_Firebird',
                                   'Connection_Informix_Exception',
                                   'Connection_Informix',
                                   'Connection_Mock',
                                   'Connection_Module',
                                   'Connection_Mssql_Exception',
                                   'Connection_Mssql',
                                   'Connection_Mysql_Exception',
                                   'Connection_Mysql',
                                   'Connection_Oracle_Exception',
                                   'Connection_Oracle',
                                   'Connection_Pgsql_Exception',
                                   'Connection_Pgsql',
                                   'Connection_Sqlite_Exception',
                                   'Connection_Sqlite',
                                   'Connection_UnitOfWork',
                                   'Connection',
                                   'DataDict_Exception',
                                   'DataDict_Firebird_Exception',
                                   'DataDict_Firebird',
                                   'DataDict_Informix_Exception',
                                   'DataDict_Informix',
                                   'DataDict_Mssql_Exception',
                                   'DataDict_Mssql',
                                   'DataDict_Mysql_Exception',
                                   'DataDict_Mysql',
                                   'DataDict_Oracle_Exception',
                                   'DataDict_Oracle',
                                   'DataDict_Pgsql_Exception',
                                   'DataDict_Pgsql',
                                   'DataDict_Sqlite_Exception',
                                   'DataDict_Sqlite',
                                   'DataDict',
                                   'Db_Event',
                                   'Db_EventListener_Chain',
                                   'Db_EventListener_Interface',
                                   'Db_EventListener',
                                   'Db_Exception',
                                   'Db_Mock',
                                   'Db_Profiler_Exception',
                                   'Db_Profiler_Query',
                                   'Db_Profiler',
                                   'Db_Statement',
                                   'Db',
                                   'EventListener_AccessorInvoker',
                                   'EventListener_Chain',
                                   'EventListener_Debugger',
                                   'EventListener_Empty',
                                   'EventListener_Interface',
                                   'EventListener',
                                   'Exception',
                                   'Export_Exception',
                                   'Export_Firebird_Exception',
                                   'Export_Firebird',
                                   'Export_Informix_Exception',
                                   'Export_Mssql_Exception',
                                   'Export_Mssql',
                                   'Export_Mysql_Exception',
                                   'Export_Mysql',
                                   'Export_Oracle_Exception',
                                   'Export_Oracle',
                                   'Export_Pgsql_Exception',
                                   'Export_Pgsql',
                                   'Export_Reporter',
                                   'Export_Sqlite_Exception',
                                   'Export_Sqlite',
                                   'Export',
                                   'Expression_Exception',
                                   'Expression_Firebird',
                                   'Expression_Informix',
                                   'Expression_Mssql',
                                   'Expression_Mysql',
                                   'Expression_Oracle',
                                   'Expression_Pgsql',
                                   'Expression_Sqlite',
                                   'Expression',
                                   'Hook_Equal',
                                   'Hook_Integer',
                                   'Hook_Parser_Complex',
                                   'Hook_Parser',
                                   'Hook_WordLike',
                                   'Hook',
                                   'Hydrate_Alias',
                                   'Hydrate',
                                   'Identifier',
                                   'Import_Builder_BaseClass',
                                   'Import_Builder_Exception',
                                   'Import_Builder',
                                   'Import_Exception',
                                   'Import_Firebird_Exception',
                                   'Import_Firebird',
                                   'Import_Informix_Exception',
                                   'Import_Informix',
                                   'Import_Mssql_Exception',
                                   'Import_Mssql',
                                   'Import_Mysql_Exception',
                                   'Import_Mysql',
                                   'Import_Oracle_Exception',
                                   'Import_Oracle',
                                   'Import_Pgsql_Exception',
                                   'Import_Pgsql',
                                   'Import_Reader_Db',
                                   'Import_Reader_Exception',
                                   'Import_Reader_Propel',
                                   'Import_Reader',
                                   'Import_Sqlite_Exception',
                                   'Import_Sqlite',
                                   'Import',
                                   'Index',
                                   'Lib',
                                   'Locking_Exception',
                                   'Locking_Manager_Pessimistic',
                                   'Manager_Exception',
                                   'Manager',
                                   'MPath',
                                   'Null',
                                   'Overloadable',
                                   'Query_Condition',
                                   'Query_Exception',
                                   'Query_From',
                                   'Query_Groupby',
                                   'Query_Having',
                                   'Query_Identifier',
                                   'Query_JoinCondition',
                                   'Query_Orderby',
                                   'Query_Part',
                                   'Query_Set',
                                   'Query_Where',
                                   'Query',
                                   'RawSql_Exception',
                                   'RawSql',
                                   'Record_Exception',
                                   'Record_Iterator',
                                   'Record_State_Exception',
                                   'Record_ValueWrapper_Interface',
                                   'Record',
                                   'Relation_Association_Self',
                                   'Relation_Association',
                                   'Relation_ForeignKey',
                                   'Relation_LocalKey',
                                   'Relation',
                                   'Reporter',
                                   'Reverse_Mssql',
                                   'Schema_Column',
                                   'Schema_Database',
                                   'Schema_Exception',
                                   'Schema_Object',
                                   'Schema_Relation',
                                   'Schema_Table',
                                   'Schema',
                                   'Sequence_Db2_Exception',
                                   'Sequence_Db2',
                                   'Sequence_Exception',
                                   'Sequence_Firebird_Exception',
                                   'Sequence_Firebird',
                                   'Sequence_Informix_Exception',
                                   'Sequence_Informix',
                                   'Sequence_Mssql_Exception',
                                   'Sequence_Mssql',
                                   'Sequence_Mysql_Exception',
                                   'Sequence_Mysql',
                                   'Sequence_Oracle_Exception',
                                   'Sequence_Oracle',
                                   'Sequence_Pgsql_Exception',
                                   'Sequence_Pgsql',
                                   'Sequence_Sqlite_Exception',
                                   'Sequence_Sqlite',
                                   'Sequence',
                                   'Struct',
                                   'Table_Exception',
                                   'Table_Repository_Exception',
                                   'Table_Repository',
                                   'Table',
                                   'Transaction_Exception',
                                   'Transaction_Firebird_Exception',
                                   'Transaction_Firebird',
                                   'Transaction_Informix_Exception',
                                   'Transaction_Informix',
                                   'Transaction_Mssql_Exception',
                                   'Transaction_Mssql',
                                   'Transaction_Mysql_Exception',
                                   'Transaction_Mysql',
                                   'Transaction_Oracle_Exception',
                                   'Transaction_Oracle',
                                   'Transaction_Pgsql_Exception',
                                   'Transaction_Pgsql',
                                   'Transaction_Sqlite_Exception',
                                   'Transaction_Sqlite',
                                   'Transaction',
                                   'Validator_Country',
                                   'Validator_Creditcard',
                                   'Validator_Date',
                                   'Validator_Email',
                                   'Validator_Enum',
                                   'Validator_ErrorStack',
                                   'Validator_Exception',
                                   'Validator_Htmlcolor',
                                   'Validator_Interface.class',
                                   'Validator_Ip',
                                   'Validator_Nospace',
                                   'Validator_Notblank',
                                   'Validator_Notnull',
                                   'Validator_Protected',
                                   'Validator_Range',
                                   'Validator_Regexp',
                                   'Validator_Unique',
                                   'Validator_Usstate',
                                   'Validator',
                                   'ValueHolder',
                                   'View_Exception',
                                   'View',
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

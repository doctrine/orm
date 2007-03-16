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
                                   'Doctrine',
                                   'Access',
                                   'Adapter_Interface',
                                   'Adapter_Statement_Interface',
                                   'Adapter_Statement',
                                   'Adapter',
                                   'Cache_Query_Sqlite',
                                   'Collection',
                                   'Collection_Iterator',
                                   'Collection_Batch',
                                   'Collection_Exception',
                                   'Collection_Immediate',
                                   'Collection_Iterator_Expandable',
                                   'Collection_Iterator_Normal',
                                   'Collection_Iterator_Offset',
                                   'Collection_Lazy',
                                   'Collection_Offset',
                                   'Compiler_Exception',
                                   'Compiler',
                                   'Configurable',
                                   'Connection',
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
                                   'DataDict_Exception',
                                   'DataDict_Firebird',
                                   'DataDict_Informix',
                                   'DataDict_Mssql',
                                   'DataDict_Mysql',
                                   'DataDict_Oracle',
                                   'DataDict_Pgsql',
                                   'DataDict_Sqlite',
                                   'DataDict',
                                   'Db',
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
                                   'EventListener_Interface',
                                   'EventListener',
                                   'EventListener_AccessorInvoker',
                                   'EventListener_Chain',
                                   'EventListener_Debugger',
                                   'EventListener_Empty',
                                   'Exception',
                                   'Export',
                                   'Export_Exception',
                                   'Export_Firebird',
                                   'Export_Mssql',
                                   'Export_Mysql',
                                   'Export_Oracle',
                                   'Export_Pgsql',
                                   'Export_Reporter',
                                   'Export_Sqlite',
                                   'Expression',
                                   'Expression_Exception',
                                   'Expression_Firebird',
                                   'Expression_Informix',
                                   'Expression_Mssql',
                                   'Expression_Mysql',
                                   'Expression_Oracle',
                                   'Expression_Pgsql',
                                   'Expression_Sqlite',
                                   'Hook',
                                   'Hook_Parser',
                                   'Hook_Parser_Complex',
                                   'Hook_Equal',
                                   'Hook_Integer',
                                   'Hook_WordLike',
                                   'Hydrate_Alias',
                                   'Hydrate',
                                   'Identifier',
                                   'Import_Builder',
                                   'Import_Builder_BaseClass',
                                   'Import_Builder_Exception',
                                   'Import',
                                   'Import_Exception',
                                   'Import_Firebird',
                                   'Import_Informix',
                                   'Import_Mssql',
                                   'Import_Mysql',
                                   'Import_Oracle',
                                   'Import_Pgsql',
                                   'Import_Reader_Db',
                                   'Import_Reader',
                                   'Import_Sqlite',
                                   'Lib',
                                   'Locking_Exception',
                                   'Locking_Manager_Pessimistic',
                                   'Manager_Exception',
                                   'Manager',
                                   'Null',
                                   'Overloadable',
                                   'Query',
                                   'Query_Part',
                                   'Query_Condition',
                                   'Query_Exception',
                                   'Query_From',
                                   'Query_Groupby',
                                   'Query_Having',
                                   'Query_JoinCondition',
                                   'Query_Orderby',
                                   'Query_Set',
                                   'Query_Where',
                                   'RawSql_Exception',
                                   'RawSql',
                                   'Record',
                                   'Record_Exception',
                                   'Record_Iterator',
                                   'Record_State_Exception',
                                   'Relation',
                                   'Relation_Association_Self',
                                   'Relation_Association',
                                   'Relation_ForeignKey',
                                   'Relation_LocalKey',
                                   'Reporter',
                                   'Schema_Object',
                                   'Schema_Column',
                                   'Schema_Database',
                                   'Schema_Exception',
                                   'Schema_Relation',
                                   'Schema_Table',
                                   'Schema',
                                   'Sequence',
                                   'Sequence_Db2',
                                   'Sequence_Exception',
                                   'Sequence_Firebird',
                                   'Sequence_Informix',
                                   'Sequence_Mssql',
                                   'Sequence_Mysql',
                                   'Sequence_Oracle',
                                   'Sequence_Pgsql',
                                   'Sequence_Sqlite',
                                   'Table_Exception',
                                   'Table',
                                   'Table_Repository_Exception',
                                   'Table_Repository',
                                   'Transaction',
                                   'Transaction_Exception',
                                   'Transaction_Firebird',
                                   'Transaction_Informix',
                                   'Transaction_Mssql',
                                   'Transaction_Mysql',
                                   'Transaction_Oracle',
                                   'Transaction_Pgsql',
                                   'Transaction_Sqlite',
                                   'Validator',
                                   'Validator_Country',
                                   'Validator_Creditcard',
                                   'Validator_Date',
                                   'Validator_Email',
                                   'Validator_Enum',
                                   'Validator_ErrorStack',
                                   'Validator_Exception',
                                   'Validator_Htmlcolor',
                                   'Validator_Ip',
                                   'Validator_Nospace',
                                   'Validator_Notblank',
                                   'Validator_Notnull',
                                   'Validator_Range',
                                   'Validator_Regexp',
                                   'Validator_Unique',
                                   'Validator_Unsigned',
                                   'Validator_Usstate',
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

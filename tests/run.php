<?php

ini_set('max_execution_time', 900);
ini_set("date.timezone", "GMT+0");

function parseOptions($array) {
    $currentName='';
    $options=array();
    foreach($array as $name) {
        if (strpos($name,'-')===0) {
            $name=str_replace('-','',$name);      
            $currentName=$name;
            if ( ! isset($options[$currentName])) {
                $options[$currentName]=array();         
            }
        } else {
            $values=$options[$currentName];
            array_push($values,$name);    
            $options[$currentName]=$values;
        }
    }
    return $options;
}

function autoload($class) {
    if (strpos($class, 'TestCase') === false) {
        return false;
    }

    $e      = explode('_', $class);
    $count  = count($e);

    $prefix = array_shift($e);

    if ($prefix !== 'Doctrine') {
        return false;
    }

    $dir    = array_shift($e);

    $file   = $dir . '_' . substr(implode('_', $e), 0, -(strlen('_TestCase'))) . 'TestCase.php';

    if ( $count > 3) {
        $file   = str_replace('_', DIRECTORY_SEPARATOR, $file);
    } else {
        $file   = str_replace('_', '', $file);
    }

    // create a test case file if it doesn't exist

    if ( ! file_exists($file)) {
        $contents = file_get_contents('template.tpl');
        $contents = sprintf($contents, $class, $class);

        if ( ! file_exists($dir)) {
            mkdir($dir, 0777);
        }

        file_put_contents($file, $contents);
    }
    require_once($file);

    return true;
}

// include doctrine, and register it's autoloader
require_once dirname(__FILE__) . '/../lib/Doctrine.php';
spl_autoload_register(array('Doctrine', 'autoload'));

// register the autoloader function above
spl_autoload_register('autoload');

// include the models
$models = new DirectoryIterator(dirname(__FILE__) . '/../models/');
foreach($models as $key => $file) {
    if ($file->isFile() && ! $file->isDot()) {
        $e = explode('.', $file->getFileName());
        if (end($e) === 'php') {
          require_once $file->getPathname();
        }
    }
}

require_once dirname(__FILE__) . '/Test.php';
require_once dirname(__FILE__) . '/UnitTestCase.php';

error_reporting(E_ALL | E_STRICT);

$test = new GroupTest('Doctrine Framework Unit Tests');

//TICKET test cases
$tickets = new GroupTest('Tickets tests');
$tickets->addTestCase(new Doctrine_Ticket_Njero_TestCase());
$tickets->addTestCase(new Doctrine_Ticket_428_TestCase());
//If you write a ticket testcase add it here like shown above!
$test->addTestCase($tickets);

// Connection drivers (not yet fully tested)
$driver = new GroupTest("Driver tests");
$driver->addTestCase(new Doctrine_Connection_Pgsql_TestCase());
$driver->addTestCase(new Doctrine_Connection_Oracle_TestCase());
$driver->addTestCase(new Doctrine_Connection_Sqlite_TestCase());
$driver->addTestCase(new Doctrine_Connection_Mssql_TestCase()); 
$driver->addTestCase(new Doctrine_Connection_Mysql_TestCase());
$driver->addTestCase(new Doctrine_Connection_Firebird_TestCase());
$driver->addTestCase(new Doctrine_Connection_Informix_TestCase());
$test->addTestCase($driver);

// Transaction module (FULLY TESTED)
$transaction = new GroupTest("Transaction tests");
$transaction->addTestCase(new Doctrine_Transaction_TestCase());
$transaction->addTestCase(new Doctrine_Transaction_Firebird_TestCase());
$transaction->addTestCase(new Doctrine_Transaction_Informix_TestCase());
$transaction->addTestCase(new Doctrine_Transaction_Mysql_TestCase());
$transaction->addTestCase(new Doctrine_Transaction_Mssql_TestCase());
$transaction->addTestCase(new Doctrine_Transaction_Pgsql_TestCase());
$transaction->addTestCase(new Doctrine_Transaction_Oracle_TestCase());
$transaction->addTestCase(new Doctrine_Transaction_Sqlite_TestCase());
$test->addTestCase($transaction);

// DataDict module (FULLY TESTED)
$data_dict = new GroupTest('DataDict tests');
$data_dict->addTestCase(new Doctrine_DataDict_TestCase());
$data_dict->addTestCase(new Doctrine_DataDict_Firebird_TestCase());
$data_dict->addTestCase(new Doctrine_DataDict_Informix_TestCase());
$data_dict->addTestCase(new Doctrine_DataDict_Mysql_TestCase());
$data_dict->addTestCase(new Doctrine_DataDict_Mssql_TestCase());
$data_dict->addTestCase(new Doctrine_DataDict_Pgsql_TestCase());
$data_dict->addTestCase(new Doctrine_DataDict_Oracle_TestCase());
$data_dict->addTestCase(new Doctrine_DataDict_Sqlite_TestCase());
$test->addTestCase($data_dict);

// Sequence module (not yet fully tested)
$sequence = new GroupTest('Sequence tests');
$sequence->addTestCase(new Doctrine_Sequence_TestCase());
$sequence->addTestCase(new Doctrine_Sequence_Firebird_TestCase());
$sequence->addTestCase(new Doctrine_Sequence_Informix_TestCase());
$sequence->addTestCase(new Doctrine_Sequence_Mysql_TestCase());
$sequence->addTestCase(new Doctrine_Sequence_Mssql_TestCase());
$sequence->addTestCase(new Doctrine_Sequence_Pgsql_TestCase());
$sequence->addTestCase(new Doctrine_Sequence_Oracle_TestCase());
$sequence->addTestCase(new Doctrine_Sequence_Sqlite_TestCase());
$test->addTestCase($sequence);

// Export module (not yet fully tested)
$export = new GroupTest('Export tests');
//$export->addTestCase(new Doctrine_Export_Reporter_TestCase());
$export->addTestCase(new Doctrine_Export_Firebird_TestCase());
$export->addTestCase(new Doctrine_Export_Informix_TestCase());
$export->addTestCase(new Doctrine_Export_TestCase());
$export->addTestCase(new Doctrine_Export_Mssql_TestCase());
$export->addTestCase(new Doctrine_Export_Pgsql_TestCase());
$export->addTestCase(new Doctrine_Export_Oracle_TestCase());
$export->addTestCase(new Doctrine_Export_Record_TestCase());
$export->addTestCase(new Doctrine_Export_Mysql_TestCase());
$export->addTestCase(new Doctrine_Export_Sqlite_TestCase());
$test->addTestCase($export);

//$test->addTestCase(new Doctrine_CascadingDelete_TestCase());

// Import module (not yet fully tested)
$import = new GroupTest('Import tests');
//$import->addTestCase(new Doctrine_Import_TestCase());
$import->addTestCase(new Doctrine_Import_Firebird_TestCase());
$import->addTestCase(new Doctrine_Import_Informix_TestCase());
$import->addTestCase(new Doctrine_Import_Mysql_TestCase());
$import->addTestCase(new Doctrine_Import_Mssql_TestCase());
$import->addTestCase(new Doctrine_Import_Pgsql_TestCase());
$import->addTestCase(new Doctrine_Import_Oracle_TestCase());
$import->addTestCase(new Doctrine_Import_Sqlite_TestCase());
$test->addTestCase($import);

// Expression module (not yet fully tested)
$expression = new GroupTest('Expression tests');
$expression->addTestCase(new Doctrine_Expression_TestCase());
$expression->addTestCase(new Doctrine_Expression_Driver_TestCase());
$expression->addTestCase(new Doctrine_Expression_Firebird_TestCase());
$expression->addTestCase(new Doctrine_Expression_Informix_TestCase());
$expression->addTestCase(new Doctrine_Expression_Mysql_TestCase());
$expression->addTestCase(new Doctrine_Expression_Mssql_TestCase());
$expression->addTestCase(new Doctrine_Expression_Pgsql_TestCase());
$expression->addTestCase(new Doctrine_Expression_Oracle_TestCase());
$expression->addTestCase(new Doctrine_Expression_Sqlite_TestCase());
$test->addTestCase($expression);

// Core
$core = new GroupTest('Core tests: Access, Configurable, Manager, Connection, Table, UnitOfWork, Collection, Hydrate, Tokenizer');
$core->addTestCase(new Doctrine_Access_TestCase());
//$core->addTestCase(new Doctrine_Configurable_TestCase());
$core->addTestCase(new Doctrine_Manager_TestCase());
$core->addTestCase(new Doctrine_Connection_TestCase());
$core->addTestCase(new Doctrine_Table_TestCase());
$core->addTestCase(new Doctrine_UnitOfWork_TestCase());
//$core->addTestCase(new Doctrine_Collection_TestCase());
$core->addTestCase(new Doctrine_Collection_Snapshot_TestCase());
$core->addTestCase(new Doctrine_Hydrate_FetchMode_TestCase());
$core->addTestCase(new Doctrine_Tokenizer_TestCase());
//$core->addTestCase(new Doctrine_Collection_Offset_TestCase());
//$core->addTestCase(new Doctrine_BatchIterator_TestCase());
//$core->addTestCase(new Doctrine_Hydrate_TestCase());
$test->addTestCase($core);

// Relation handling
$relation = new GroupTest('Relation tests: includes TreeStructure');
$relation->addTestCase(new Doctrine_TreeStructure_TestCase());
$relation->addTestCase(new Doctrine_Relation_TestCase());
//$relation->addTestCase(new Doctrine_Relation_Access_TestCase());
//$relation->addTestCase(new Doctrine_Relation_ManyToMany_TestCase());
$relation->addTestCase(new Doctrine_Relation_ManyToMany2_TestCase());
$relation->addTestCase(new Doctrine_Relation_OneToMany_TestCase());
$relation->addTestCase(new Doctrine_Relation_Nest_TestCase());
$relation->addTestCase(new Doctrine_Relation_OneToOne_TestCase());
$relation->addTestCase(new Doctrine_Relation_Parser_TestCase());
$test->addTestCase($relation);

// Datatypes
$data_types = new GroupTest('DataTypes tests: Enum and Boolean');
$data_types->addTestCase(new Doctrine_DataType_Enum_TestCase());
$data_types->addTestCase(new Doctrine_DataType_Boolean_TestCase());
$test->addTestCase($data_types);

// Utility components
$plugins = new GroupTest('Plugin tests: View, Validator, Hook');
//$utility->addTestCase(new Doctrine_PessimisticLocking_TestCase());
$plugins->addTestCase(new Doctrine_View_TestCase());
$plugins->addTestCase(new Doctrine_Validator_TestCase());
$plugins->addTestCase(new Doctrine_Validator_Future_TestCase());
$plugins->addTestCase(new Doctrine_Validator_Past_TestCase());
$plugins->addTestCase(new Doctrine_Hook_TestCase());
//$plugins->addTestCase(new Doctrine_I18n_TestCase());
$test->addTestCase($plugins);

// Db component
$db = new GroupTest('Db tests: Db and Profiler');
$db->addTestCase(new Doctrine_Db_TestCase());
$db->addTestCase(new Doctrine_Connection_Profiler_TestCase());
$test->addTestCase($db);

// Eventlisteners
$event_listener = new GroupTest('EventListener tests');
$event_listener->addTestCase(new Doctrine_EventListener_TestCase());
$event_listener->addTestCase(new Doctrine_EventListener_Chain_TestCase());
$test->addTestCase($event_listener);

// Query tests
$query_tests = new GroupTest('Query tests');
$query_tests->addTestCase(new Doctrine_Query_Condition_TestCase());
$query_tests->addTestCase(new Doctrine_Query_MultiJoin_TestCase());
$query_tests->addTestCase(new Doctrine_Query_MultiJoin2_TestCase());
$query_tests->addTestCase(new Doctrine_Query_ReferenceModel_TestCase());
$query_tests->addTestCase(new Doctrine_Query_ComponentAlias_TestCase());
$query_tests->addTestCase(new Doctrine_Query_ShortAliases_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Expression_TestCase());
$query_tests->addTestCase(new Doctrine_Query_OneToOneFetching_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Check_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Limit_TestCase());
//$query_tests->addTestCase(new Doctrine_Query_IdentifierQuoting_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Update_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Delete_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Join_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Having_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Orderby_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Subquery_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Driver_TestCase());
$query_tests->addTestCase(new Doctrine_Record_Hook_TestCase());
$query_tests->addTestCase(new Doctrine_Query_AggregateValue_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Where_TestCase());
$query_tests->addTestCase(new Doctrine_Query_From_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Select_TestCase());
$query_tests->addTestCase(new Doctrine_Query_JoinCondition_TestCase());
$query_tests->addTestCase(new Doctrine_Query_MultipleAggregateValue_TestCase());
$query_tests->addTestCase(new Doctrine_Query_TestCase());
$query_tests->addTestCase(new Doctrine_Query_MysqlSubquery_TestCase());
$query_tests->addTestCase(new Doctrine_Query_PgsqlSubquery_TestCase());
$query_tests->addTestCase(new Doctrine_Query_MysqlSubqueryHaving_TestCase());
$query_tests->addTestCase(new Doctrine_Query_SelectExpression_TestCase());
$query_tests->addTestCase(new Doctrine_Query_Registry_TestCase());
$test->addTestCase($query_tests);

// Record
$record = new GroupTest('Record tests');
$record->addTestCase(new Doctrine_Record_Filter_TestCase());
$record->addTestCase(new Doctrine_Record_TestCase());
$record->addTestCase(new Doctrine_Record_State_TestCase());
$record->addTestCase(new Doctrine_Record_SerializeUnserialize_TestCase());
// This test used to segfault php because of infinite recursion in Connection/UnitOfWork
$record->addTestCase(new Doctrine_Record_Lock_TestCase());
$record->addTestCase(new Doctrine_Record_ZeroValues_TestCase());
//$record->addTestCase(new Doctrine_Record_SaveBlankRecord_TestCase());
$test->addTestCase($record);


$test->addTestCase(new Doctrine_Schema_TestCase());

$test->addTestCase(new Doctrine_CustomPrimaryKey_TestCase());
$test->addTestCase(new Doctrine_CustomResultSetOrder_TestCase());


$test->addTestCase(new Doctrine_ColumnAggregationInheritance_TestCase());

$test->addTestCase(new Doctrine_ColumnAlias_TestCase());


$test->addTestCase(new Doctrine_RawSql_TestCase());

$test->addTestCase(new Doctrine_NewCore_TestCase());

$test->addTestCase(new Doctrine_Template_TestCase());

//$test->addTestCase(new Doctrine_Import_Builder_TestCase());


//$test->addTestCase(new Doctrine_IntegrityAction_TestCase());

//$test->addTestCase(new Doctrine_AuditLog_TestCase());

$test->addTestCase(new Doctrine_NestedSet_SingleRoot_TestCase());

// Search tests
$search = new GroupTest('Search tests');
$search->addTestCase(new Doctrine_Search_TestCase());
$search->addTestCase(new Doctrine_Search_Query_TestCase());

$test->addTestCase($search);

// Cache tests
$cache = new GroupTest('Cache tests');
$cache->addTestCase(new Doctrine_Query_Cache_TestCase());
$cache->addTestCase(new Doctrine_Cache_Apc_TestCase());
//$cache->addTestCase(new Doctrine_Cache_Memcache_TestCase());
//$cache->addTestCase(new Doctrine_Cache_Sqlite_TestCase());
//$cache->addTestCase(new Doctrine_Cache_Query_SqliteTestCase());
//$cache->addTestCase(new Doctrine_Cache_FileTestCase());
//$cache->addTestCase(new Doctrine_Cache_SqliteTestCase());
//$cache->addTestCase(new Doctrine_Cache_TestCase());
$test->addTestCase($cache);

$test->addTestCase(new Doctrine_Query_ApplyInheritance_TestCase());

$test->addTestCase(new Doctrine_Migration_TestCase());

$test->addTestCase(new Doctrine_Import_Schema_TestCase());

$test->addTestCase(new Doctrine_Export_Schema_TestCase());

class CliReporter extends HtmlReporter{
    public function paintHeader(){
        echo "Doctrine UnitTests\n";
        echo "====================\n";
    }
    public function paintFooter(){
        echo "\n";
        foreach ($this->_test->getMessages() as $message) {
            print $message . "\n";
        }
        echo "====================\n";
        print "Tested: " . $this->_test->getTestCaseCount() . ' test cases' ."\n";
        print "Successes: " . $this->_test->getPassCount() . " passes. \n";
        print "Failures: " . $this->_test->getFailCount() . " fails. \n";
    }
}

class MyReporter extends HtmlReporter {
    public function paintHeader() {
?>
<html>
<head>
  <title>Doctrine Unit Tests</title>
  <style>
.fail { color: red; } pre { background-color: lightgray; }
  </style>
</head>

<body>

<h1>Doctrine Unit Tests</h1>
<?php

    }

    public function paintFooter()
    {

        print '<pre>';
        foreach ($this->_test->getMessages() as $message) {
            print "<p>$message</p>";
        }
        print '</pre>';
        $colour = ($this->_test->getFailCount() > 0 ? 'red' : 'green');
        print '<div style=\'';
        print "padding: 8px; margin-top: 1em; background-color: $colour; color: white;";
        print '\'>';
        print $this->_test->getTestCaseCount() . ' test cases.';
        print '<strong>' . $this->_test->getPassCount() . '</strong> passes and ';
        print '<strong>' . $this->_test->getFailCount() . '</strong> fails.';
        print '</div>';
    }
}


?>
<?php
if (PHP_SAPI === 'cli') {
    $reporter = new CliReporter();
    $argv = $_SERVER['argv'];
    array_shift($argv);
    $options = parseOptions($argv);
} else {
    $options = $_GET;
    $reporter = new MyReporter();
}


if (isset($options['group'])) {
    $testGroup = new GroupTest('Custom');
    foreach($options['group'] as $group) {
        if ( ! isset($$group)) {
            if (class_exists($group)) {
                $testGroup->addTestCase(new $group);
            }
            die($group . " is not a valid group of tests\n");
        }
        $testGroup->addTestCase($$group);
    }
} else {
    $testGroup = $test;
}
$filter = '';
if (isset($options['filter'])) {
    $filter = $options['filter'];
}

if (isset($options['help'])) {
    echo "Doctrine test runner help\n";
    echo "===========================\n";
    echo " To run all tests simply run this script without arguments. \n";
    echo "\n Flags:\n";
    echo " -coverage will generate coverage report data that can be viewed with the cc.php script in this folder. NB! This takes time. You need xdebug to run this\n";
    echo " -group <groupName1> <groupName2> <className1> Use this option to run just a group of tests or tests with a given classname. Groups are currently defined as the variable name they are called in this script.\n";
    echo " -filter <string1> <string2> case insensitive strings that will be applied to the className of the tests. A test_classname must contain all of these strings to be run\n"; 
    echo "\nAvailable groups:\n tickets, transaction, driver, data_dict, sequence, export, import, expression, core, relation, data_types, utility, db, event_listener, query_tests, record, cache\n";
    die();
}

if (isset($options['coverage'])) {
    xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
    $testGroup->run($reporter, $filter);
    $result['path'] = Doctrine::getPath() . DIRECTORY_SEPARATOR;
    $result['coverage'] = xdebug_get_code_coverage();
    xdebug_stop_code_coverage();
    file_put_contents('coverage.txt', serialize($result));
} else {
    $testGroup->run($reporter, $filter);
}

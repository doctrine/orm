<?php

ini_set('max_execution_time', 900);

function autoload($class) {
    if(strpos($class, 'TestCase') === false)
        return false;

    $e      = explode('_', $class);
    $count  = count($e);

    $prefix = array_shift($e);

    if ($prefix !== 'Doctrine') {
        return false;
    }

    $dir    = array_shift($e);

    $file   = $dir . '_' . substr(implode('_', $e), 0, -(strlen('_TestCase'))) . 'TestCase.php';

    if($count > 3) {
        $file   = str_replace('_', DIRECTORY_SEPARATOR, $file);
    } else {
        $file   = str_replace('_', '', $file);
    }

    // create a test case file if it doesn't exist

    if ( ! file_exists($file)) {
        $contents = file_get_contents('template.tpl');
        $contents = sprintf($contents, $class, $class);

        if( ! file_exists($dir)) {
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

require_once dirname(__FILE__) . '/../models/location.php';
require_once dirname(__FILE__) . '/../models/Blog.php';
require_once dirname(__FILE__) . '/classes.php';

require_once dirname(__FILE__) . '/Test.php';
require_once dirname(__FILE__) . '/UnitTestCase.php';

error_reporting(E_ALL | E_STRICT);

$test = new GroupTest('Doctrine Framework Unit Tests');



// DATABASE ABSTRACTION tests
/**   */

// Temp tests
/**

$test->addTestCase(new Doctrine_Ticket330_TestCase());
    */
 /**   */
// Connection drivers (not yet fully tested)
$test->addTestCase(new Doctrine_Connection_Pgsql_TestCase());
$test->addTestCase(new Doctrine_Connection_Oracle_TestCase());
$test->addTestCase(new Doctrine_Connection_Sqlite_TestCase());
$test->addTestCase(new Doctrine_Connection_Mssql_TestCase()); 
$test->addTestCase(new Doctrine_Connection_Mysql_TestCase());
$test->addTestCase(new Doctrine_Connection_Firebird_TestCase());
$test->addTestCase(new Doctrine_Connection_Informix_TestCase());

// Transaction module (FULLY TESTED)
$test->addTestCase(new Doctrine_Transaction_TestCase());
$test->addTestCase(new Doctrine_Transaction_Firebird_TestCase());
$test->addTestCase(new Doctrine_Transaction_Informix_TestCase());
$test->addTestCase(new Doctrine_Transaction_Mysql_TestCase());
$test->addTestCase(new Doctrine_Transaction_Mssql_TestCase());
$test->addTestCase(new Doctrine_Transaction_Pgsql_TestCase());
$test->addTestCase(new Doctrine_Transaction_Oracle_TestCase());
$test->addTestCase(new Doctrine_Transaction_Sqlite_TestCase());

// DataDict module (FULLY TESTED)
$test->addTestCase(new Doctrine_DataDict_TestCase());
$test->addTestCase(new Doctrine_DataDict_Firebird_TestCase());
$test->addTestCase(new Doctrine_DataDict_Informix_TestCase());
$test->addTestCase(new Doctrine_DataDict_Mysql_TestCase());
$test->addTestCase(new Doctrine_DataDict_Mssql_TestCase());
$test->addTestCase(new Doctrine_DataDict_Pgsql_TestCase());
$test->addTestCase(new Doctrine_DataDict_Oracle_TestCase());
$test->addTestCase(new Doctrine_DataDict_Sqlite_TestCase());

// Sequence module (not yet fully tested)
$test->addTestCase(new Doctrine_Sequence_TestCase());
$test->addTestCase(new Doctrine_Sequence_Firebird_TestCase());
$test->addTestCase(new Doctrine_Sequence_Informix_TestCase());
$test->addTestCase(new Doctrine_Sequence_Mysql_TestCase());
$test->addTestCase(new Doctrine_Sequence_Mssql_TestCase());
$test->addTestCase(new Doctrine_Sequence_Pgsql_TestCase());
$test->addTestCase(new Doctrine_Sequence_Oracle_TestCase());
$test->addTestCase(new Doctrine_Sequence_Sqlite_TestCase());

// Export module (not yet fully tested)


//$test->addTestCase(new Doctrine_Export_Reporter_TestCase());
$test->addTestCase(new Doctrine_Export_Firebird_TestCase());
$test->addTestCase(new Doctrine_Export_Informix_TestCase());
$test->addTestCase(new Doctrine_Export_TestCase());
$test->addTestCase(new Doctrine_Export_Mssql_TestCase());
$test->addTestCase(new Doctrine_Export_Pgsql_TestCase());
$test->addTestCase(new Doctrine_Export_Oracle_TestCase());
$test->addTestCase(new Doctrine_Export_Record_TestCase());
$test->addTestCase(new Doctrine_Export_Mysql_TestCase());

$test->addTestCase(new Doctrine_Export_Sqlite_TestCase());

//$test->addTestCase(new Doctrine_CascadingDelete_TestCase());

// Import module (not yet fully tested)
//$test->addTestCase(new Doctrine_Import_TestCase());
$test->addTestCase(new Doctrine_Import_Firebird_TestCase());
$test->addTestCase(new Doctrine_Import_Informix_TestCase());
$test->addTestCase(new Doctrine_Import_Mysql_TestCase());
$test->addTestCase(new Doctrine_Import_Mssql_TestCase());
$test->addTestCase(new Doctrine_Import_Pgsql_TestCase());
$test->addTestCase(new Doctrine_Import_Oracle_TestCase());
$test->addTestCase(new Doctrine_Import_Sqlite_TestCase());


// Expression module (not yet fully tested)
$test->addTestCase(new Doctrine_Expression_TestCase());
$test->addTestCase(new Doctrine_Expression_Firebird_TestCase());
$test->addTestCase(new Doctrine_Expression_Informix_TestCase());
$test->addTestCase(new Doctrine_Expression_Mysql_TestCase());
$test->addTestCase(new Doctrine_Expression_Mssql_TestCase());
$test->addTestCase(new Doctrine_Expression_Pgsql_TestCase());
$test->addTestCase(new Doctrine_Expression_Oracle_TestCase());
$test->addTestCase(new Doctrine_Expression_Sqlite_TestCase());

// Core

$test->addTestCase(new Doctrine_Access_TestCase());
//$test->addTestCase(new Doctrine_Configurable_TestCase());

$test->addTestCase(new Doctrine_Manager_TestCase());
$test->addTestCase(new Doctrine_Connection_TestCase());
$test->addTestCase(new Doctrine_Table_TestCase());


$test->addTestCase(new Doctrine_UnitOfWork_TestCase());

//$test->addTestCase(new Doctrine_Collection_TestCase());
// Relation handling

$test->addTestCase(new Doctrine_TreeStructure_TestCase());
$test->addTestCase(new Doctrine_Relation_TestCase());

//$test->addTestCase(new Doctrine_Relation_Access_TestCase());
//$test->addTestCase(new Doctrine_Relation_ManyToMany_TestCase());


$test->addTestCase(new Doctrine_Relation_OneToMany_TestCase());

$test->addTestCase(new Doctrine_Relation_Nest_TestCase());

$test->addTestCase(new Doctrine_Relation_OneToOne_TestCase());

$test->addTestCase(new Doctrine_Relation_Parser_TestCase());

// Datatypes
$test->addTestCase(new Doctrine_Enum_TestCase());
 

$test->addTestCase(new Doctrine_Boolean_TestCase());

// Utility components

//$test->addTestCase(new Doctrine_PessimisticLocking_TestCase());


$test->addTestCase(new Doctrine_View_TestCase());

$test->addTestCase(new Doctrine_Validator_TestCase());

$test->addTestCase(new Doctrine_Hook_TestCase());

// Db component
$test->addTestCase(new Doctrine_Db_TestCase());
$test->addTestCase(new Doctrine_Connection_Profiler_TestCase());


// Eventlisteners
$test->addTestCase(new Doctrine_EventListener_TestCase());
$test->addTestCase(new Doctrine_EventListener_Chain_TestCase());



$test->addTestCase(new Doctrine_Record_Filter_TestCase());

$test->addTestCase(new Doctrine_SchemaTestCase());

$test->addTestCase(new Doctrine_Query_Condition_TestCase());

$test->addTestCase(new Doctrine_CustomPrimaryKey_TestCase());

$test->addTestCase(new Doctrine_CustomResultSetOrder_TestCase());

// Query tests

$test->addTestCase(new Doctrine_Query_MultiJoin_TestCase());

$test->addTestCase(new Doctrine_Query_MultiJoin2_TestCase());

$test->addTestCase(new Doctrine_Query_ReferenceModel_TestCase());

$test->addTestCase(new Doctrine_Query_ComponentAlias_TestCase());



$test->addTestCase(new Doctrine_Query_ShortAliases_TestCase());

$test->addTestCase(new Doctrine_Query_Expression_TestCase());

$test->addTestCase(new Doctrine_ColumnAggregationInheritance_TestCase());

$test->addTestCase(new Doctrine_ColumnAlias_TestCase());

$test->addTestCase(new Doctrine_Query_OneToOneFetching_TestCase());

$test->addTestCase(new Doctrine_Cache_Apc_TestCase());
$test->addTestCase(new Doctrine_Cache_Memcache_TestCase());
$test->addTestCase(new Doctrine_Cache_Sqlite_TestCase());

$test->addTestCase(new Doctrine_Query_Check_TestCase());

$test->addTestCase(new Doctrine_Query_Limit_TestCase());



$test->addTestCase(new Doctrine_Query_IdentifierQuoting_TestCase());
$test->addTestCase(new Doctrine_Query_Update_TestCase());
$test->addTestCase(new Doctrine_Query_Delete_TestCase());

$test->addTestCase(new Doctrine_Query_Join_TestCase());

$test->addTestCase(new Doctrine_Record_TestCase());

$test->addTestCase(new Doctrine_Query_Having_TestCase());

$test->addTestCase(new Doctrine_RawSql_TestCase());

$test->addTestCase(new Doctrine_Query_Orderby_TestCase());

$test->addTestCase(new Doctrine_Query_Subquery_TestCase());

$test->addTestCase(new Doctrine_Query_Driver_TestCase());

$test->addTestCase(new Doctrine_Record_Hook_TestCase());

$test->addTestCase(new Doctrine_Query_AggregateValue_TestCase());




$test->addTestCase(new Doctrine_NewCore_TestCase());

// Record

$test->addTestCase(new Doctrine_Record_State_TestCase());

//$test->addTestCase(new Doctrine_Query_Cache_TestCase());

$test->addTestCase(new Doctrine_Tokenizer_TestCase());


$test->addTestCase(new Doctrine_Collection_Snapshot_TestCase());

$test->addTestCase(new Doctrine_Hydrate_FetchMode_TestCase());

$test->addTestCase(new Doctrine_Query_Where_TestCase());

$test->addTestCase(new Doctrine_Query_From_TestCase());

$test->addTestCase(new Doctrine_Query_Select_TestCase());

$test->addTestCase(new Doctrine_Query_JoinCondition_TestCase());

$test->addTestCase(new Doctrine_Query_MultipleAggregateValue_TestCase());

$test->addTestCase(new Doctrine_Query_TestCase());

$test->addTestCase(new Doctrine_Ticket364_TestCase());

$test->addTestCase(new Doctrine_Template_TestCase());

$test->addTestCase(new Doctrine_Query_MysqlSubquery_TestCase());

$test->addTestCase(new Doctrine_Query_PgsqlSubquery_TestCase());

$test->addTestCase(new Doctrine_Query_MysqlSubqueryHaving_TestCase());

//$test->addTestCase(new Doctrine_IntegrityAction_TestCase());

//$test->addTestCase(new Doctrine_AuditLog_TestCase());

// Cache tests
//$test->addTestCase(new Doctrine_Cache_Query_SqliteTestCase());
//$test->addTestCase(new Doctrine_Cache_FileTestCase());
//$test->addTestCase(new Doctrine_Cache_SqliteTestCase());
//$test->addTestCase(new Doctrine_Collection_Offset_TestCase());
//$test->addTestCase(new Doctrine_BatchIterator_TestCase());
//$test->addTestCase(new Doctrine_Hydrate_TestCase());
//$test->addTestCase(new Doctrine_Cache_TestCase());


class MyReporter extends HtmlReporter {
    public function paintHeader() {}
    public function paintFooter()
    {
    	print "<pre>";
    	foreach ($this->_test->getMessages() as $message) {
    	   print $message . "\n";
    	}
    	print "</pre>";
        $colour = ($this->_test->getFailCount() > 0 ? "red" : "green");
        print "<div style=\"";
        print "padding: 8px; margin-top: 1em; background-color: $colour; color: white;";
        print "\">";
        print $this->_test->getTestCaseCount() . ' test cases';
        print " test cases complete:\n";
        print "<strong>" . $this->_test->getPassCount() . "</strong> passes, ";
        print "<strong>" . $this->_test->getFailCount() . "</strong> fails and ";
        print "</div>\n";
    }
}


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
$test->run(new MyReporter());
?>
</body>
</html>


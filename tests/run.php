<?php
ob_start();


function autoload($class) {
    if(strpos($class, 'TestCase') === false)
        return false;

    $e      = explode('_', $class);
    $count  = count($e);

    array_shift($e);

    $dir    = array_shift($e);

    $file   = $dir . '_' . substr(implode('_', $e), 0, -(strlen('_TestCase'))) . 'TestCase.php';

    if($count > 3) {
        $file   = str_replace('_', DIRECTORY_SEPARATOR, $file);
    } else {
        $file   = str_replace('_', '', $file);
    }
    print $file ."<br \>";
    // create a test case file if it doesn't exist

    if( ! file_exists($file)) {
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

require_once dirname(__FILE__) . '/../lib/Doctrine.php';

spl_autoload_register(array('Doctrine', 'autoload'));
spl_autoload_register('autoload');

require_once('classes.php');
require_once('simpletest/unit_tester.php');
require_once('simpletest/reporter.php');
require_once('UnitTestCase.php');
require_once('DriverTestCase.php');

error_reporting(E_ALL);
print '<pre>';

$test = new GroupTest('Doctrine Framework Unit Tests');



// DATABASE ABSTRACTION tests

// Connection drivers (not yet fully tested)
$test->addTestCase(new Doctrine_Connection_Pgsql_TestCase());
$test->addTestCase(new Doctrine_Connection_Oracle_TestCase());
$test->addTestCase(new Doctrine_Connection_Sqlite_TestCase());
$test->addTestCase(new Doctrine_Connection_Mssql_TestCase()); 
$test->addTestCase(new Doctrine_Connection_Mysql_TestCase());
$test->addTestCase(new Doctrine_Connection_Firebird_TestCase());
$test->addTestCase(new Doctrine_Connection_Informix_TestCase());
/**
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

// Export module (not yet fully tested)
$test->addTestCase(new Doctrine_Export_TestCase());
$test->addTestCase(new Doctrine_Export_Reporter_TestCase());
$test->addTestCase(new Doctrine_Export_Firebird_TestCase());
$test->addTestCase(new Doctrine_Export_Informix_TestCase());
$test->addTestCase(new Doctrine_Export_Mysql_TestCase());
$test->addTestCase(new Doctrine_Export_Mssql_TestCase());
$test->addTestCase(new Doctrine_Export_Pgsql_TestCase());
$test->addTestCase(new Doctrine_Export_Oracle_TestCase());
$test->addTestCase(new Doctrine_Export_Sqlite_TestCase());

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
$test->addTestCase(new Doctrine_Connection_Transaction_TestCase());

$test->addTestCase(new Doctrine_Collection_TestCase());

// Relation handling
$test->addTestCase(new Doctrine_Relation_TestCase());
$test->addTestCase(new Doctrine_Relation_Access_TestCase());
$test->addTestCase(new Doctrine_Relation_ManyToMany_TestCase());


// Datatypes
$test->addTestCase(new Doctrine_Enum_TestCase());
$test->addTestCase(new Doctrine_Boolean_TestCase());


// Utility components
$test->addTestCase(new Doctrine_Hook_TestCase());
$test->addTestCase(new Doctrine_PessimisticLocking_TestCase());
$test->addTestCase(new Doctrine_Validator_TestCase());
$test->addTestCase(new Doctrine_RawSql_TestCase());
$test->addTestCase(new Doctrine_View_TestCase());


// Db component
$test->addTestCase(new Doctrine_Db_TestCase());
$test->addTestCase(new Doctrine_Db_Profiler_TestCase());


// Record
$test->addTestCase(new Doctrine_Record_TestCase());
$test->addTestCase(new Doctrine_Record_State_TestCase());
//$test->addTestCase(new Doctrine_Record_Filter_TestCase());

// Eventlisteners
$test->addTestCase(new Doctrine_EventListener_TestCase());
$test->addTestCase(new Doctrine_EventListener_Chain_TestCase());

// Old test cases (should be removed)
$test->addTestCase(new Doctrine_SchemaTestCase());
$test->addTestCase(new Doctrine_BatchIterator_TestCase());
$test->addTestCase(new Doctrine_CustomPrimaryKey_TestCase());
$test->addTestCase(new Doctrine_CustomResultSetOrderTestCase());
$test->addTestCase(new Doctrine_Filter_TestCase());
//$test->addTestCase(new Doctrine_Collection_Offset_TestCase());

// Query tests
$test->addTestCase(new Doctrine_Query_MultiJoin_TestCase());
$test->addTestCase(new Doctrine_Query_ReferenceModel_TestCase());
$test->addTestCase(new Doctrine_Query_Condition_TestCase());
$test->addTestCase(new Doctrine_Query_ComponentAlias_TestCase());
$test->addTestCase(new Doctrine_Query_Subquery_TestCase());
$test->addTestCase(new Doctrine_Query_TestCase());
$test->addTestCase(new Doctrine_Query_ShortAliases_TestCase());
$test->addTestCase(new Doctrine_Query_From_TestCase());
$test->addTestCase(new Doctrine_Query_Delete_TestCase());
$test->addTestCase(new Doctrine_Query_Where_TestCase());
$test->addTestCase(new Doctrine_Query_Limit_TestCase());
$test->addTestCase(new Doctrine_Query_IdentifierQuoting_TestCase());
$test->addTestCase(new Doctrine_Query_Update_TestCase());
$test->addTestCase(new Doctrine_Query_AggregateValue_TestCase());
$test->addTestCase(new Doctrine_Query_Select_TestCase());
$test->addTestCase(new Doctrine_Query_Expression_TestCase());
$test->addTestCase(new Doctrine_Query_Having_TestCase());
$test->addTestCase(new Doctrine_Query_JoinCondition_TestCase());


$test->addTestCase(new Doctrine_TreeStructure_TestCase());
*/
// Cache tests
//$test->addTestCase(new Doctrine_Cache_Query_SqliteTestCase());
//$test->addTestCase(new Doctrine_Cache_FileTestCase());
//$test->addTestCase(new Doctrine_Cache_SqliteTestCase());

class MyReporter extends HtmlReporter {
    public function paintHeader() {}
    public function paintFooter()
    {
        $colour = ($this->getFailCount() + $this->getExceptionCount() > 0 ? "red" : "green");
        print "<div style=\"";
        print "padding: 8px; margin-top: 1em; background-color: $colour; color: white;";
        print "\">";
        print $this->getTestCaseProgress() . "/" . $this->getTestCaseCount();
        print " test cases complete:\n";
        print "<strong>" . $this->getPassCount() . "</strong> passes, ";
        print "<strong>" . $this->getFailCount() . "</strong> fails and ";
        print "<strong>" . $this->getExceptionCount() . "</strong> exceptions.";
        print "</div>\n";
    }
}

if (TextReporter::inCli()) {
    if ($argc == 4)
    {
        $dsn = $argv[1];
        $username = $argv[2];
        $password = $argv[3];
    }
    exit ($test->run(new TextReporter()) ? 0 : 1);
} else {
    if (isset($_POST))
    {
        $dsn        = isset($_POST["dsn"])?$_POST["dsn"]:null;
        $username   = isset($_POST["username"])?$_POST["username"]:null;
        $password   = isset($_POST["password"])?$_POST["password"]:null;
    }
    $test->run(new MyReporter());
    $output = ob_get_clean();
}
/**
$cache = Doctrine_Manager::getInstance()->getCurrentConnection()->getCacheHandler();
if(isset($cache)) {
    $a     = $cache->getQueries();
    print "Executed cache queries: ".count($a)."\n";

    foreach($a as $query) {
        print $query."\n";
    }

}
*/
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
<h3>DSN Settings</h3>
<form method="post">
<table>
<tr>
  <th>DSN</th>
  <td><input type="text" name="dsn" /></td>
</tr>
<tr>
  <th>Username</th>
  <td><input type="text" name="username" /></td>
</tr>
<tr>
  <th>Password</th>
  <td><input type="text" name="password" /></td>
</tr>
<tr>
  <td>&nbsp;</td>
  <td><input type="submit" name="submit" /></td>
</tr>
</table>
</form>
<h3>Tests</h3>
<pre>
<?php echo $output; ?>
</pre>
<h3>Queries</h3>
<pre>
<?php
$dbh = Doctrine_Manager::getInstance()->getCurrentConnection()->getDBH();
$a   = $dbh->getQueries();

print "Executed queries: ".count($a)."\n";

foreach($a as $query) {
    print $query."\n";
}
?>
</pre>
</body>
</html>


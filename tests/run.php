<?php
ob_start();

require_once("ConfigurableTestCase.php");
require_once("ManagerTestCase.php");
require_once("SessionTestCase.php");
require_once("TableTestCase.php");
require_once("EventListenerTestCase.php");
require_once("BatchIteratorTestCase.php");
require_once("CacheFileTestCase.php");
require_once("RecordTestCase.php");
require_once("AccessTestCase.php");
require_once("ValidatorTestCase.php");
require_once("CollectionTestCase.php");
require_once("PessimisticLockingTestCase.php");

require_once("CacheSqliteTestCase.php");
require_once("CollectionOffsetTestCase.php");
require_once("QueryTestCase.php");
require_once("CacheQuerySqliteTestCase.php");
require_once("ViewTestCase.php");
require_once("RawSqlTestCase.php");
require_once("CustomPrimaryKeyTestCase.php");
require_once("FilterTestCase.php");
require_once("ValueHolderTestCase.php");
require_once("QueryLimitTestCase.php");

error_reporting(E_ALL);

$test = new GroupTest("Doctrine Framework Unit Tests");
/**
$test->addTestCase(new Doctrine_RecordTestCase());

$test->addTestCase(new Doctrine_SessionTestCase());

$test->addTestCase(new Doctrine_TableTestCase());

$test->addTestCase(new Doctrine_ManagerTestCase());

$test->addTestCase(new Doctrine_AccessTestCase());

$test->addTestCase(new Doctrine_EventListenerTestCase());

$test->addTestCase(new Doctrine_BatchIteratorTestCase());

$test->addTestCase(new Doctrine_ConfigurableTestCase());

$test->addTestCase(new Doctrine_Collection_OffsetTestCase());

$test->addTestCase(new Doctrine_PessimisticLockingTestCase());

$test->addTestCase(new Doctrine_ViewTestCase());

$test->addTestCase(new Doctrine_Cache_Query_SqliteTestCase());

$test->addTestCase(new Doctrine_CustomPrimaryKeyTestCase());

$test->addTestCase(new Doctrine_Filter_TestCase());

$test->addTestCase(new Doctrine_ValueHolder_TestCase());

$test->addTestCase(new Doctrine_ValidatorTestCase());

$test->addTestCase(new Doctrine_CollectionTestCase());

$test->addTestCase(new Doctrine_QueryTestCase());

$test->addTestCase(new Doctrine_Query_Limit_TestCase());
*/                                           
$test->addTestCase(new Doctrine_RawSql_TestCase());
//$test->addTestCase(new Doctrine_Cache_FileTestCase());
//$test->addTestCase(new Doctrine_Cache_SqliteTestCase());


print "<pre>";
$test->run(new HtmlReporter());
/**
$cache = Doctrine_Manager::getInstance()->getCurrentSession()->getCacheHandler();
if(isset($cache)) {
    $a     = $cache->getQueries();
    print "Executed cache queries: ".count($a)."\n";

    foreach($a as $query) {
        print $query."\n";
    }

}
*/

$dbh = Doctrine_Manager::getInstance()->getCurrentSession()->getDBH();
$a   = $dbh->getQueries();

print "Executed queries: ".count($a)."\n";

foreach($a as $query) {
    print $query."\n";
}
ob_end_flush();
?>

<?php
ob_start();
require_once("ConfigurableTestCase.class.php");
require_once("ManagerTestCase.class.php");
require_once("SessionTestCase.class.php");
require_once("TableTestCase.class.php");
require_once("EventListenerTestCase.class.php");
require_once("BatchIteratorTestCase.class.php");
require_once("CacheFileTestCase.class.php");
require_once("RecordTestCase.class.php");
require_once("AccessTestCase.class.php");
require_once("ValidatorTestCase.class.php");
require_once("CollectionTestCase.class.php");

require_once("CacheSqliteTestCase.class.php");
require_once("CollectionOffsetTestCase.class.php");
require_once("SenseiTestCase.class.php");
require_once("QueryTestCase.class.php");


print "<pre>";
error_reporting(E_ALL);

$test = new GroupTest("Doctrine Framework Unit Tests");





$test->addTestCase(new Doctrine_TableTestCase());

$test->addTestCase(new Doctrine_SessionTestCase());

$test->addTestCase(new Doctrine_RecordTestCase());



$test->addTestCase(new Doctrine_ValidatorTestCase());

$test->addTestCase(new Doctrine_ManagerTestCase());

$test->addTestCase(new Doctrine_AccessTestCase());

$test->addTestCase(new Doctrine_EventListenerTestCase());

$test->addTestCase(new Doctrine_BatchIteratorTestCase());

$test->addTestCase(new Doctrine_ConfigurableTestCase());

$test->addTestCase(new Doctrine_CollectionTestCase());

$test->addTestCase(new Doctrine_Collection_OffsetTestCase());

$test->addTestCase(new Sensei_UnitTestCase());

$test->addTestCase(new Doctrine_QueryTestCase());
//$test->addTestCase(new Doctrine_Cache_FileTestCase());
//$test->addTestCase(new Doctrine_Cache_SqliteTestCase());








$test->run(new HtmlReporter());
$cache = Doctrine_Manager::getInstance()->getCurrentSession()->getCacheHandler();
if(isset($cache)) {
    $a     = $cache->getQueries();
    print "Executed cache queries: ".count($a)."\n";
    /**
    foreach($a as $query) {
        print $query."\n";
    }
    */
}

$dbh = Doctrine_Manager::getInstance()->getCurrentSession()->getDBH();
$a   = $dbh->getQueries();

print "Executed queries: ".count($a)."\n";

foreach($a as $query) {
    print $query."\n";
}
ob_end_flush();
?>

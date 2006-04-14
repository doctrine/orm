<?php
require_once("ConfigurableTestCase.class.php");
require_once("ManagerTestCase.class.php");
require_once("SessionTestCase.class.php");
require_once("TableTestCase.class.php");
require_once("EventListenerTestCase.class.php");
require_once("BatchIteratorTestCase.class.php");
require_once("CacheFileTestCase.class.php");
require_once("RecordTestCase.class.php");
require_once("DQLParserTestCase.class.php");
require_once("AccessTestCase.class.php");
require_once("ValidatorTestCase.class.php");
print "<pre>";
error_reporting(E_ALL);

$test = new GroupTest("Doctrine Framework Unit Tests");





$test->addTestCase(new Doctrine_RecordTestCase());

$test->addTestCase(new Doctrine_SessionTestCase());
$test->addTestCase(new Doctrine_ValidatorTestCase());

$test->addTestCase(new Doctrine_ManagerTestCase());
$test->addTestCase(new Doctrine_TableTestCase());

$test->addTestCase(new Doctrine_AccessTestCase());
$test->addTestCase(new Doctrine_ConfigurableTestCase());


$test->addTestCase(new Doctrine_EventListenerTestCase());
//$test->addTestCase(new Doctrine_BatchIteratorTestCase());
//$test->addTestCase(new Doctrine_Cache_FileTestCase());




$test->addTestCase(new Doctrine_DQL_ParserTestCase());




$test->run(new HtmlReporter());
$dbh = Doctrine_Manager::getInstance()->getCurrentSession()->getDBH();
$a   = $dbh->getQueries();

print "Executed queries: ".count($a)."\n";

foreach($a as $query) {
    $e = explode(" ",$query);
    print $query."\n";
}
?>

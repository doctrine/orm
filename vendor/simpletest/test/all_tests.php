<?php
    // $Id: all_tests.php,v 1.20 2005/02/05 04:51:31 lastcraft Exp $
    define('TEST', __FILE__);
    require_once('../unit_tester.php');
    require_once('../shell_tester.php');
    require_once('../reporter.php');
    require_once('../mock_objects.php');
    require_once('unit_tests.php');
    
    // Uncomment and modify the following line if you are accessing
    // the net via a proxy server.
    //
    // SimpleTestOptions::useProxy('http://my-proxy', 'optional username', 'optional password');
        
    class AllTests extends GroupTest {
        function AllTests() {
            $this->GroupTest('All tests for SimpleTest ' . SimpleTestOptions::getVersion());
            $this->addTestCase(new UnitTests());
            $this->addTestFile('shell_test.php');
            $this->addTestFile('live_test.php');
            $this->addTestFile('acceptance_test.php');
            $this->addTestFile('real_sites_test.php');
        }
    }

    $test = &new AllTests();
    if (SimpleReporter::inCli()) {
        exit ($test->run(new TextReporter()) ? 0 : 1);
    }
    $test->run(new HtmlReporter());
?>
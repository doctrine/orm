<?php
    // $Id: unit_tests.php,v 1.47 2005/02/05 04:51:31 lastcraft Exp $
    if (! defined('TEST')) {
        define('TEST', __FILE__);
    }
    require_once('../unit_tester.php');
    require_once('../web_tester.php');
    require_once('../shell_tester.php');
    require_once('../reporter.php');
    require_once('../mock_objects.php');
    require_once('../extensions/pear_test_case.php');
    require_once('../extensions/phpunit_test_case.php');
    
    class UnitTests extends GroupTest {
        function UnitTests() {
            $this->GroupTest('Unit tests');
            $this->addTestFile('errors_test.php');
            $this->addTestFile('options_test.php');
            $this->addTestFile('dumper_test.php');
            $this->addTestFile('expectation_test.php');
            $this->addTestFile('unit_tester_test.php');
            $this->addTestFile('simple_mock_test.php');
            $this->addTestFile('adapter_test.php');
            $this->addTestFile('socket_test.php');
            $this->addTestFile('encoding_test.php');
            $this->addTestFile('url_test.php');
            $this->addTestFile('http_test.php');
            $this->addTestFile('authentication_test.php');
            $this->addTestFile('user_agent_test.php');
            $this->addTestFile('parser_test.php');
            $this->addTestFile('tag_test.php');
            $this->addTestFile('form_test.php');
            $this->addTestFile('page_test.php');
            $this->addTestFile('frames_test.php');
            $this->addTestFile('browser_test.php');
            $this->addTestFile('web_tester_test.php');
            $this->addTestFile('shell_tester_test.php');
            $this->addTestFile('xml_test.php');
        }
    }
    
    if (TEST == __FILE__) {
        $test = &new UnitTests();
        if (SimpleReporter::inCli()) {
            exit ($test->run(new TextReporter()) ? 0 : 1);
        }
        $test->run(new HtmlReporter());
    }
?>
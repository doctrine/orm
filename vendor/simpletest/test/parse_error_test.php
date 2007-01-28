<?php
    // $Id: parse_error_test.php,v 1.1 2005/01/24 00:32:14 lastcraft Exp $
    
    require_once('../unit_tester.php');
    require_once('../reporter.php');

    $test = &new GroupTest('This should fail');
    $test->addTestFile('test_with_parse_error.php');
    $test->run(new HtmlReporter());
?>
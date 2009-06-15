<?php
    require_once('simpletest/unit_tester.php');
    require_once('simpletest/reporter.php');
    
    require_once(dirname(__FILE__).'/acceptance_test.php');
    require_once(dirname(__FILE__).'/annotation_test.php');
    require_once(dirname(__FILE__).'/constrained_annotation_test.php');
    require_once(dirname(__FILE__).'/annotation_parser_test.php');
    require_once(dirname(__FILE__).'/doc_comment_test.php');
    
    $suite = new TestSuite('All tests');
    $suite->add(new TestOfAnnotations);
    $suite->add(new TestOfPerformanceFeatures);
    $suite->add(new TestOfSupportingFeatures);
    $suite->add(new TestOfAnnotation);
    $suite->add(new TestOfConstrainedAnnotation);
    $suite->add(new TestOfMatchers);
    $suite->add(new TestOfAnnotationMatchers);
    $suite->add(new TestOfDocComment);
	
    $reporter = TextReporter::inCli() ? new TextReporter() : new HtmlReporter();

    Addendum::setRawMode(false);
    $suite->run($reporter);
    
    Addendum::setRawMode(true);
    $suite->run($reporter);
?>

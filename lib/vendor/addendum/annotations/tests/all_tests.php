<?php
    require_once('simpletest/unit_tester.php');
    require_once('simpletest/reporter.php');
    
    require_once(dirname(__FILE__).'/acceptance_test.php');
    require_once(dirname(__FILE__).'/annotation_test.php');
    require_once(dirname(__FILE__).'/constrained_annotation_test.php');
    require_once(dirname(__FILE__).'/annotation_parser_test.php');
    require_once(dirname(__FILE__).'/doc_comment_test.php');
    
    class AllTests extends GroupTest {
          function __construct($title = false) {
              parent::__construct($title);
              $this->addTestClass('TestOfAnnotations');
              $this->addTestClass('TestOfPerformanceFeatures');
              $this->addTestClass('TestOfSupportingFeatures');
              $this->addTestClass('TestOfAnnotation');
              $this->addTestClass('TestOfConstrainedAnnotation');
              $this->addTestClass('TestOfMatchers');
              $this->addTestClass('TestOfAnnotationMatchers');
              $this->addTestClass('TestOfDocComment');
              
          }
      }
    
    Addendum::setRawMode(false);
    $test = new AllTests('All tests in reflection mode');
    $test->run(new HtmlReporter());
    
    Addendum::setRawMode(true);
    $test = new AllTests('All tests in raw mode');
    $test->run(new HtmlReporter());
?>

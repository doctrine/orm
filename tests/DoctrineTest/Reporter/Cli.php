<?php

class DoctrineTest_Reporter_Cli extends DoctrineTest_Reporter{
    public function paintHeader($name){
        echo $name . "\n";
        echo "====================\n";
    }
    public function paintFooter(){
        echo "\n";
        foreach ($this->_test->getMessages() as $message) {
            print $message . "\n";
        }
        echo "====================\n";
        print "Tested: " . $this->_test->getTestCaseCount() . ' test cases' ."\n";
        print "Successes: " . $this->_test->getPassCount() . " passes. \n";
        print "Failures: " . $this->_test->getFailCount() . " fails. \n";
    }


    public function getProgressIndicator(){
        return ".";
    }
}

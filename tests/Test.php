<?php
class GroupTest
{
    protected $_testCases = array();

    public function addTestCase(UnitTestCase $testCase)
    {
        $this->_testCases[] = $testCase;
    }
    public function run(HtmlReporter $reporter)
    {
    	foreach ($this->_testCases as $testCase) {
    	    $testCase->run();
    	}
        $reporter->setTestCase($this);
        
        $reporter->paintHeader();
        $reporter->paintFooter();
    }
    public function getMessages()
    {
    	$messages = array();
        foreach($this->_testCases as $testCase) {
            $messages = array_merge($messages, $testCase->getMessages());
        }
        return $messages;
    }
    public function getFailCount()
    {
    	$fails = 0;
        foreach ($this->_testCases as $testCase) {
            $fails += $testCase->getFailCount();
        }
        return $fails;
    }
    public function getTestCaseCount()
    {
        return count($this->_testCases);
    }
    public function getPassCount()
    {
    	$passes = 0;
        foreach ($this->_testCases as $testCase) {
            $passes += $testCase->getPassCount();
        }
        return $passes;
    }
}
class HtmlReporter
{
    protected $_test;
    
    public function setTestCase(GroupTest $test) 
    {
        $this->_test = $test;
    }
}
class UnitTestCase
{
    protected $_passed = 0;
    
    protected $_failed = 0;
    
    protected $_messages = array();

    public function assertEqual($value, $value2)
    {
        if ($value == $value2) {
            $this->_passed++;
        } else {
            $this->_fail();
        }
    }
    public function assertNotEqual($value, $value2)
    {
        if ($value != $value2) {
            $this->_passed++;
        } else {
            $this->_fail();
        }
    }
    public function assertTrue($expr)
    {
        if ($expr) {
            $this->_passed++;
        } else {
            $this->_fail();
        }
    }
    public function assertFalse($expr)
    {
        if ( ! $expr) {
            $this->_passed++;
        } else {
            $this->_fail();
        }
    }
    public function pass() 
    {
        $this->_passed++;
    }
    public function fail()
    {
        $this->_fail();	
    }
    public function _fail()
    {
    	$trace = debug_backtrace();
    	array_shift($trace);


        foreach ($trace as $stack) {

            if (substr($stack['function'], 0, 4) === 'test') {
                $class = new ReflectionClass($stack['class']);

                if ( ! isset($line)) {
                    $line = $stack['line'];
                }

                $this->_messages[] = $class->getName() . ' : method ' . $stack['function'] . ' failed on line ' . $line;
                break;
            }
            $line = $stack['line'];

    	}

        $this->_failed++;
    }
    public function run() 
    {
        foreach (get_class_methods($this) as $method) {
            if (substr($method, 0, 4) === 'test') {
                $this->setUp();

                $this->$method();
            }
        }
    }
    public function getMessages() 
    {
        return $this->_messages;
    }
    public function getFailCount()
    {
        return $this->_failed;
    }
    public function getPassCount()
    {
        return $this->_passed;
    }
}

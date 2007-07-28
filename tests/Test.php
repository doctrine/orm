<?php
class GroupTest extends UnitTestCase
{
    protected $_testCases = array();

    public function __construct()
    {
        if (extension_loaded('xdebug')) {
            //xdebug_start_code_coverage(XDEBUG_CC_DEAD_CODE | XDEBUG_CC_UNUSED);
        }
    }

    public function addTestCase(UnitTestCase $testCase)
    {
        $this->_testCases[] = $testCase;
    }
    public function run(HtmlReporter $reporter)
    {

        $reporter->paintHeader();
    	foreach ($this->_testCases as $k => $testCase) {
    	    $testCase->run();
    	    
    	    $this->_passed += $testCase->getPassCount();
    	    $this->_failed += $testCase->getFailCount();
            $this->_messages = array_merge($this->_messages, $testCase->getMessages());

    	    $this->_testCases[$k] = null;
            if(PHP_SAPI === "cli"){
                echo ".";
            }
            set_time_limit(900);
    	}
        $reporter->setTestCase($this);
        
        $reporter->paintFooter();
    }


    public function getTestCaseCount()
    {
        return count($this->_testCases);
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

    public function assertIdentical($value, $value2)
    {
        if ($value === $value2) {
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

                $errorMessage = $class->getName() . ' : method ' . $stack['function'] . ' failed on line ' . $line;
                $this->_messages[] =  $errorMessage;
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

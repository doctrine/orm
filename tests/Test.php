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
        if($testCase instanceOf GroupTest) {
            $this->_testCases = array_merge($this->_testCases, $testCase->getTestCases());
         } else {
            $this->_testCases[get_class($testCase)] = $testCase;
         }
    }

    public function shouldBeRun($testCase, $filter){
        if( ! is_array($filter)) {
            return true;
         }
        foreach($filter as $subFilter) {
            $name = strtolower(get_class($testCase));
            $pos = strpos($name, strtolower($subFilter));
            //it can be 0 so we have to use === to see if false
            if ($pos === false) {
                return false;
             }
        }
        return true;
    }
    public function run(HtmlReporter $reporter = null, $filter = null)
    {
        $reporter->paintHeader();
        foreach ($this->_testCases as $k => $testCase) {
            if ( ! $this->shouldBeRun($testCase, $filter)) {
                continue;
            }
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

    public function getTestCases(){
        return $this->_testCases;
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
            $seperator = "<br>";
            if(PHP_SAPI === "cli"){
                $seperator = "\n";
             }
            $message = "$seperator Value1: $value $seperator != $seperator Value2: $value2 $seperator";
            $this->_fail($message);
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
    public function fail($message = "")
    {
        $this->_fail($message);    
    }
    public function _fail($message = "")
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
                $this->_messages[] =  $errorMessage . " " . $message;
                break;
            }
            $line = $stack['line'];
        }
        $this->_failed++;
    }
    public function run(HtmlReporter $reporter = null, $filter = null) 
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

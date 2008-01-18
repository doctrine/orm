<?php
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
            if(is_array($value)){
                $value = var_export($value, true);
             }
            if(is_array($value2)){
                $value2 = var_export($value2, true);
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

    public function assertNull($expr)
    {
        if (is_null($expr)) {
            $this->pass();
        } else {
            $this->fail();
        }
    }

    public function assertNotNull($expr)
    {
        if (is_null($expr)) {
            $this->fail();
        } else {
            $this->pass();
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
    public function run(DoctrineTest_Reporter $reporter = null, $filter = null)
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

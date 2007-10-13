<?php
class GroupTest extends UnitTestCase
{
    protected $_testCases = array();
    protected $_name;
    protected $_title;

    public function __construct($title, $name)
    {
        $this->_title = $title;
        $this->_name =  $name;
    }

    public function getName(){
        return $this->_name;
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
        if ( ! is_array($filter)) {
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
    public function run(DoctrineTest_Reporter $reporter = null, $filter = null)
    {
        $reporter->paintHeader($this->_title);
        foreach ($this->_testCases as $k => $testCase) {
            if ( ! $this->shouldBeRun($testCase, $filter)) {
                continue;
            }
            try{
                $testCase->run();
            } catch(Exception $e) {
                $this->_failed += 1;
                $this->_messages[] = 'Unexpected exception thrown with message [' . $e->getMessage() . '] in ' . $e->getFile() . ' on line ' . $e->getLine();
            }
            $this->_passed += $testCase->getPassCount();
            $this->_failed += $testCase->getFailCount();
            $this->_messages = array_merge($this->_messages, $testCase->getMessages());

            $this->_testCases[$k] = null;
            if(PHP_SAPI === 'cli'){
                echo '.';
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


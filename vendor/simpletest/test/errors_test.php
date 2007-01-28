<?php
    require_once(dirname(__FILE__) . '/../errors.php');
    
    class TestOfErrorQueue extends UnitTestCase {
        
        function setUp() {
            $queue = &SimpleErrorQueue::instance();
            $queue->clear();
        }
        
        function tearDown() {
            $queue = &SimpleErrorQueue::instance();
            $queue->clear();
        }
        
        function testSingleton() {
            $this->assertReference(
                    SimpleErrorQueue::instance(),
                    SimpleErrorQueue::instance());
            $this->assertIsA(SimpleErrorQueue::instance(), 'SimpleErrorQueue');
        }
        
        function testEmpty() {
            $queue = &SimpleErrorQueue::instance();
            $this->assertTrue($queue->isEmpty());
            $this->assertFalse($queue->extract());
        }
        
        function testOrder() {
            $queue = &SimpleErrorQueue::instance();
            $queue->add(1024, 'Ouch', 'here.php', 100, array());
            $this->assertFalse($queue->isEmpty());
            $queue->add(512, 'Yuk', 'there.php', 101, array());
            $this->assertEqual(
                    $queue->extract(),
                    array(1024, 'Ouch', 'here.php', 100, array()));
            $this->assertEqual(
                    $queue->extract(),
                    array(512, 'Yuk', 'there.php', 101, array()));
            $this->assertFalse($queue->extract());
        }
    }
    
    class TestOfErrorTrap extends UnitTestCase {
        var $_old;
        
        function setUp() {
            $this->_old = error_reporting(E_ALL);
            set_error_handler('simpleTestErrorHandler');
        }
        
        function tearDown() {
            restore_error_handler();
            error_reporting($this->_old);
        }
        
        function testTrappedErrorPlacedInQueue() {
            $queue = &SimpleErrorQueue::instance();
            $this->assertFalse($queue->extract());
            trigger_error('Ouch!');
            list($severity, $message, $file, $line, $globals) = $queue->extract();
            $this->assertEqual($message, 'Ouch!');
            $this->assertEqual($file, __FILE__);
            $this->assertFalse($queue->extract());
        }
    }
    
    class TestOfErrors extends UnitTestCase {
        var $_old;
        
        function setUp() {
            $this->_old = error_reporting(E_ALL);
        }
        
        function tearDown() {
            error_reporting($this->_old);
        }
        
        function testDefaultWhenAllReported() {
            error_reporting(E_ALL);
            trigger_error('Ouch!');
            $this->assertError('Ouch!');
        }
        
        function testNoticeWhenReported() {
            error_reporting(E_ALL);
            trigger_error('Ouch!', E_USER_NOTICE);
            $this->assertError('Ouch!');
        }
        
        function testWarningWhenReported() {
            error_reporting(E_ALL);
            trigger_error('Ouch!', E_USER_WARNING);
            $this->assertError('Ouch!');
        }
        
        function testErrorWhenReported() {
            error_reporting(E_ALL);
            trigger_error('Ouch!', E_USER_ERROR);
            $this->assertError('Ouch!');
        }
        
        function testNoNoticeWhenNotReported() {
            error_reporting(0);
            trigger_error('Ouch!', E_USER_NOTICE);
            $this->assertNoErrors();
        }
        
        function testNoWarningWhenNotReported() {
            error_reporting(0);
            trigger_error('Ouch!', E_USER_WARNING);
            $this->assertNoErrors();
        }
        
        function testNoErrorWhenNotReported() {
            error_reporting(0);
            trigger_error('Ouch!', E_USER_ERROR);
            $this->assertNoErrors();
        }
        
        function testNoticeSuppressedWhenReported() {
            error_reporting(E_ALL);
            @trigger_error('Ouch!', E_USER_NOTICE);
            $this->assertNoErrors();
        }
        
        function testWarningSuppressedWhenReported() {
            error_reporting(E_ALL);
            @trigger_error('Ouch!', E_USER_WARNING);
            $this->assertNoErrors();
        }
        
        function testErrorSuppressedWhenReported() {
            error_reporting(E_ALL);
            @trigger_error('Ouch!', E_USER_ERROR);
            $this->assertNoErrors();
        }
    }
?>
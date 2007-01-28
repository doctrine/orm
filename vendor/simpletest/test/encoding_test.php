<?php
    // $Id: encoding_test.php,v 1.6 2005/01/02 23:43:28 lastcraft Exp $
    
    require_once(dirname(__FILE__) . '/../url.php');
    
    class FormEncodingTestCase extends UnitTestCase {
        
        function testEmpty() {
            $encoding = &new SimpleFormEncoding();
            $this->assertIdentical($encoding->getValue('a'), false);
            $this->assertIdentical($encoding->getKeys(), array());
            $this->assertIdentical($encoding->asString(), '');
        }
        
        function testPrefilled() {
            $encoding = &new SimpleFormEncoding(array('a' => 'aaa'));
            $this->assertIdentical($encoding->getValue('a'), 'aaa');
            $this->assertIdentical($encoding->getKeys(), array('a'));
            $this->assertIdentical($encoding->asString(), 'a=aaa');
        }
        
        function testPrefilledWithObject() {
            $encoding = &new SimpleFormEncoding(new SimpleFormEncoding(array('a' => 'aaa')));
            $this->assertIdentical($encoding->getValue('a'), 'aaa');
            $this->assertIdentical($encoding->getKeys(), array('a'));
            $this->assertIdentical($encoding->asString(), 'a=aaa');
        }
        
        function testMultiplePrefilled() {
            $encoding = &new SimpleFormEncoding(array('a' => array('a1', 'a2')));
            $this->assertIdentical($encoding->getValue('a'), array('a1', 'a2'));
            $this->assertIdentical($encoding->asString(), 'a=a1&a=a2');
        }
        
        function testSingleParameter() {
            $encoding = &new SimpleFormEncoding();
            $encoding->add('a', 'Hello');
            $this->assertEqual($encoding->getValue('a'), 'Hello');
            $this->assertIdentical($encoding->asString(), 'a=Hello');
        }
        
        function testFalseParameter() {
            $encoding = &new SimpleFormEncoding();
            $encoding->add('a', false);
            $this->assertEqual($encoding->getValue('a'), false);
            $this->assertIdentical($encoding->asString(), '');
        }
        
        function testUrlEncoding() {
            $encoding = &new SimpleFormEncoding();
            $encoding->add('a', 'Hello there!');
            $this->assertIdentical($encoding->asString(), 'a=Hello+there%21');
        }
        
        function testMultipleParameter() {
            $encoding = &new SimpleFormEncoding();
            $encoding->add('a', 'Hello');
            $encoding->add('b', 'Goodbye');
            $this->assertIdentical($encoding->asString(), 'a=Hello&b=Goodbye');
        }
        
        function testEmptyParameters() {
            $encoding = &new SimpleFormEncoding();
            $encoding->add('a', '');
            $encoding->add('b', '');
            $this->assertIdentical($encoding->asString(), 'a=&b=');
        }
        
        function testRepeatedParameter() {
            $encoding = &new SimpleFormEncoding();
            $encoding->add('a', 'Hello');
            $encoding->add('a', 'Goodbye');
            $this->assertIdentical($encoding->getValue('a'), array('Hello', 'Goodbye'));
            $this->assertIdentical($encoding->asString(), 'a=Hello&a=Goodbye');
        }
        
        function testDefaultCoordinatesAreUnset() {
            $encoding = &new SimpleFormEncoding();
            $this->assertIdentical($encoding->getX(), false);
            $this->assertIdentical($encoding->getY(), false);
        }
        
        function testSettingCoordinates() {
            $encoding = &new SimpleFormEncoding();
            $encoding->setCoordinates('32', '45');
            $this->assertIdentical($encoding->getX(), 32);
            $this->assertIdentical($encoding->getY(), 45);
            $this->assertIdentical($encoding->asString(), '?32,45');
        }
        
        function testClearingCordinates() {
            $encoding = &new SimpleFormEncoding();
            $encoding->setCoordinates('32', '45');
            $encoding->setCoordinates();
            $this->assertIdentical($encoding->getX(), false);
            $this->assertIdentical($encoding->getY(), false);
        }
        
        function testAddingLists() {
            $encoding = &new SimpleFormEncoding();
            $encoding->add('a', array('Hello', 'Goodbye'));
            $this->assertIdentical($encoding->getValue('a'), array('Hello', 'Goodbye'));
            $this->assertIdentical($encoding->asString(), 'a=Hello&a=Goodbye');
        }
        
        function testMergeInHash() {
            $encoding = &new SimpleFormEncoding(array('a' => 'A1', 'b' => 'B'));
            $encoding->merge(array('a' => 'A2'));
            $this->assertIdentical($encoding->getValue('a'), array('A1', 'A2'));
            $this->assertIdentical($encoding->getValue('b'), 'B');
        }
        
        function testMergeInObject() {
            $encoding = &new SimpleFormEncoding(array('a' => 'A1', 'b' => 'B'));
            $encoding->merge(new SimpleFormEncoding(array('a' => 'A2')));
            $this->assertIdentical($encoding->getValue('a'), array('A1', 'A2'));
            $this->assertIdentical($encoding->getValue('b'), 'B');
        }
        
        function testMergeInObjectWithCordinates() {
            $incoming = new SimpleFormEncoding(array('a' => 'A2'));
            $incoming->setCoordinates(25, 24);
            
            $encoding = &new SimpleFormEncoding(array('a' => 'A1'));
            $encoding->setCoordinates(1, 2);
            $encoding->merge($incoming);
            
            $this->assertIdentical($encoding->getValue('a'), array('A1', 'A2'));
            $this->assertIdentical($encoding->getX(), 25);
            $this->assertIdentical($encoding->getY(), 24);
            $this->assertIdentical($encoding->asString(), 'a=A1&a=A2?25,24');
        }
    }
?>
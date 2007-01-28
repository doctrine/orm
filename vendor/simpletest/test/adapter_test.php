<?php
    // $Id: adapter_test.php,v 1.7 2005/01/13 01:31:57 lastcraft Exp $
    
    class SameTestClass {
    }
    
    class TestOfPearAdapter extends PHPUnit_TestCase {
        
        function testBoolean() {
            $this->assertTrue(true, "PEAR true");
            $this->assertFalse(false, "PEAR false");
        }
        
        function testName() {
            $this->assertTrue($this->getName() == get_class($this));
        }
        
        function testPass() {
            $this->pass("PEAR pass");
        }
        
        function testNulls() {
            $value = null;
            $this->assertNull($value, "PEAR null");
            $value = 0;
            $this->assertNotNull($value, "PEAR not null");
        }
        
        function testType() {
            $this->assertType("Hello", "string", "PEAR type");
        }
        
        function testEquals() {
            $this->assertEquals(12, 12, "PEAR identity");
            $this->setLooselyTyped(true);
            $this->assertEquals("12", 12, "PEAR equality");
        }
        
        function testSame() {
            $same = &new SameTestClass();
            $this->assertSame($same, $same, "PEAR same");
        }
        
        function testRegExp() {
            $this->assertRegExp('/hello/', "A big hello from me", "PEAR regex");
        }
    }
    
    class TestOfPhpUnitAdapter extends TestCase {
        function TestOfPhpUnitAdapter() {
            $this->TestCase("TestOfPhpUnitAdapter");
        }
        
        function testBoolean() {
            $this->assert(true, "PHP Unit true");
        }
        
        function testName() {
            $this->assertTrue($this->name() == "TestOfPhpUnitAdapter");
        }
        
        function testEquals() {
            $this->assertEquals(12, 12, "PHP Unit equality");
        }
        
        function testMultilineEquals() {
            $this->assertEquals("a\nb\n", "a\nb\n", "PHP Unit equality");
        }
        
        function testRegExp() {
            $this->assertRegexp('/hello/', "A big hello from me", "PEAR regex");
        }
    }
?>
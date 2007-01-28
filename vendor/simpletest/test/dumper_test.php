<?php
    // $Id: dumper_test.php,v 1.4 2004/09/24 22:55:10 lastcraft Exp $

    class DumperDummy {
    }

    class TestOfTextFormatting extends UnitTestCase {
        
        function testClipping() {
            $dumper = new SimpleDumper();
            $this->assertEqual(
                    $dumper->clipString("Hello", 6),
                    "Hello",
                    "Hello, 6->%s");
            $this->assertEqual(
                    $dumper->clipString("Hello", 5),
                    "Hello",
                    "Hello, 5->%s");
            $this->assertEqual(
                    $dumper->clipString("Hello world", 3),
                    "Hel...",
                    "Hello world, 3->%s");
            $this->assertEqual(
                    $dumper->clipString("Hello world", 6, 3),
                    "Hello ...",
                    "Hello world, 6, 3->%s");
            $this->assertEqual(
                    $dumper->clipString("Hello world", 3, 6),
                    "...o w...",
                    "Hello world, 3, 6->%s");
            $this->assertEqual(
                    $dumper->clipString("Hello world", 4, 11),
                    "...orld",
                    "Hello world, 4, 11->%s");
            $this->assertEqual(
                    $dumper->clipString("Hello world", 4, 12),
                    "...orld",
                    "Hello world, 4, 12->%s");
        }
        
        function testDescribeNull() {
            $dumper = new SimpleDumper();
            $this->assertWantedPattern('/null/i', $dumper->describeValue(null));
        }
        
        function testDescribeBoolean() {
            $dumper = new SimpleDumper();
            $this->assertWantedPattern('/boolean/i', $dumper->describeValue(true));
            $this->assertWantedPattern('/true/i', $dumper->describeValue(true));
            $this->assertWantedPattern('/false/i', $dumper->describeValue(false));
        }
        
        function testDescribeString() {
            $dumper = new SimpleDumper();
            $this->assertWantedPattern('/string/i', $dumper->describeValue('Hello'));
            $this->assertWantedPattern('/Hello/', $dumper->describeValue('Hello'));
        }
        
        function testDescribeInteger() {
            $dumper = new SimpleDumper();
            $this->assertWantedPattern('/integer/i', $dumper->describeValue(35));
            $this->assertWantedPattern('/35/', $dumper->describeValue(35));
        }
        
        function testDescribeFloat() {
            $dumper = new SimpleDumper();
            $this->assertWantedPattern('/float/i', $dumper->describeValue(0.99));
            $this->assertWantedPattern('/0\.99/', $dumper->describeValue(0.99));
        }
        
        function testDescribeArray() {
            $dumper = new SimpleDumper();
            $this->assertWantedPattern('/array/i', $dumper->describeValue(array(1, 4)));
            $this->assertWantedPattern('/2/i', $dumper->describeValue(array(1, 4)));
        }
        
        function testDescribeObject() {
            $dumper = new SimpleDumper();
            $this->assertWantedPattern(
                    '/object/i',
                    $dumper->describeValue(new DumperDummy()));
            $this->assertWantedPattern(
                    '/DumperDummy/i',
                    $dumper->describeValue(new DumperDummy()));
        }
    }
?>
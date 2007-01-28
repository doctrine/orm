<?php
    // $Id: parser_test.php,v 1.51 2004/11/30 05:34:00 lastcraft Exp $
    
    require_once(dirname(__FILE__) . '/../parser.php');
    
    Mock::generate('SimpleSaxParser');

    class TestOfParallelRegex extends UnitTestCase {
        
        function testNoPatterns() {
            $regex = &new ParallelRegex(false);
            $this->assertFalse($regex->match("Hello", $match));
            $this->assertEqual($match, "");
        }
        
        function testNoSubject() {
            $regex = &new ParallelRegex(false);
            $regex->addPattern(".*");
            $this->assertTrue($regex->match("", $match));
            $this->assertEqual($match, "");
        }
        
        function testMatchAll() {
            $regex = &new ParallelRegex(false);
            $regex->addPattern(".*");
            $this->assertTrue($regex->match("Hello", $match));
            $this->assertEqual($match, "Hello");
        }
        
        function testCaseSensitive() {
            $regex = &new ParallelRegex(true);
            $regex->addPattern("abc");
            $this->assertTrue($regex->match("abcdef", $match));
            $this->assertEqual($match, "abc");
            $this->assertTrue($regex->match("AAABCabcdef", $match));
            $this->assertEqual($match, "abc");
        }
        
        function testCaseInsensitive() {
            $regex = &new ParallelRegex(false);
            $regex->addPattern("abc");
            $this->assertTrue($regex->match("abcdef", $match));
            $this->assertEqual($match, "abc");
            $this->assertTrue($regex->match("AAABCabcdef", $match));
            $this->assertEqual($match, "ABC");
        }
        
        function testMatchMultiple() {
            $regex = &new ParallelRegex(true);
            $regex->addPattern("abc");
            $regex->addPattern("ABC");
            $this->assertTrue($regex->match("abcdef", $match));
            $this->assertEqual($match, "abc");
            $this->assertTrue($regex->match("AAABCabcdef", $match));
            $this->assertEqual($match, "ABC");
            $this->assertFalse($regex->match("Hello", $match));
        }
        
        function testPatternLabels() {
            $regex = &new ParallelRegex(false);
            $regex->addPattern("abc", "letter");
            $regex->addPattern("123", "number");
            $this->assertIdentical($regex->match("abcdef", $match), "letter");
            $this->assertEqual($match, "abc");
            $this->assertIdentical($regex->match("0123456789", $match), "number");
            $this->assertEqual($match, "123");
        }
    }
    
    class TestOfStateStack extends UnitTestCase {
        
        function testStartState() {
            $stack = &new SimpleStateStack("one");
            $this->assertEqual($stack->getCurrent(), "one");
        }
        
        function testExhaustion() {
            $stack = &new SimpleStateStack("one");
            $this->assertFalse($stack->leave());
        }
        
        function testStateMoves() {
            $stack = &new SimpleStateStack("one");
            $stack->enter("two");
            $this->assertEqual($stack->getCurrent(), "two");
            $stack->enter("three");
            $this->assertEqual($stack->getCurrent(), "three");
            $this->assertTrue($stack->leave());
            $this->assertEqual($stack->getCurrent(), "two");
            $stack->enter("third");
            $this->assertEqual($stack->getCurrent(), "third");
            $this->assertTrue($stack->leave());
            $this->assertTrue($stack->leave());
            $this->assertEqual($stack->getCurrent(), "one");
        }
    }
    
    class TestParser {
        
        function accept() {
        }
        
        function a() {
        }
        
        function b() {
        }
    }
    Mock::generate('TestParser');

    class TestOfLexer extends UnitTestCase {
        
        function testEmptyPage() {
            $handler = &new MockTestParser($this);
            $handler->expectNever("accept");
            $handler->setReturnValue("accept", true);
            $handler->expectNever("accept");
            $handler->setReturnValue("accept", true);
            $lexer = &new SimpleLexer($handler);
            $lexer->addPattern("a+");
            $this->assertTrue($lexer->parse(""));
        }
        
        function testSinglePattern() {
            $handler = &new MockTestParser($this);
            $handler->expectArgumentsAt(0, "accept", array("aaa", LEXER_MATCHED));
            $handler->expectArgumentsAt(1, "accept", array("x", LEXER_UNMATCHED));
            $handler->expectArgumentsAt(2, "accept", array("a", LEXER_MATCHED));
            $handler->expectArgumentsAt(3, "accept", array("yyy", LEXER_UNMATCHED));
            $handler->expectArgumentsAt(4, "accept", array("a", LEXER_MATCHED));
            $handler->expectArgumentsAt(5, "accept", array("x", LEXER_UNMATCHED));
            $handler->expectArgumentsAt(6, "accept", array("aaa", LEXER_MATCHED));
            $handler->expectArgumentsAt(7, "accept", array("z", LEXER_UNMATCHED));
            $handler->expectCallCount("accept", 8);
            $handler->setReturnValue("accept", true);
            $lexer = &new SimpleLexer($handler);
            $lexer->addPattern("a+");
            $this->assertTrue($lexer->parse("aaaxayyyaxaaaz"));
            $handler->tally();
        }
        
        function testMultiplePattern() {
            $handler = &new MockTestParser($this);
            $target = array("a", "b", "a", "bb", "x", "b", "a", "xxxxxx", "a", "x");
            for ($i = 0; $i < count($target); $i++) {
                $handler->expectArgumentsAt($i, "accept", array($target[$i], '*'));
            }
            $handler->expectCallCount("accept", count($target));
            $handler->setReturnValue("accept", true);
            $lexer = &new SimpleLexer($handler);
            $lexer->addPattern("a+");
            $lexer->addPattern("b+");
            $this->assertTrue($lexer->parse("ababbxbaxxxxxxax"));
            $handler->tally();
        }
    }

    class TestOfLexerModes extends UnitTestCase {
        
        function testIsolatedPattern() {
            $handler = &new MockTestParser($this);
            $handler->expectArgumentsAt(0, "a", array("a", LEXER_MATCHED));
            $handler->expectArgumentsAt(1, "a", array("b", LEXER_UNMATCHED));
            $handler->expectArgumentsAt(2, "a", array("aa", LEXER_MATCHED));
            $handler->expectArgumentsAt(3, "a", array("bxb", LEXER_UNMATCHED));
            $handler->expectArgumentsAt(4, "a", array("aaa", LEXER_MATCHED));
            $handler->expectArgumentsAt(5, "a", array("x", LEXER_UNMATCHED));
            $handler->expectArgumentsAt(6, "a", array("aaaa", LEXER_MATCHED));
            $handler->expectArgumentsAt(7, "a", array("x", LEXER_UNMATCHED));
            $handler->expectCallCount("a", 8);
            $handler->setReturnValue("a", true);
            $lexer = &new SimpleLexer($handler, "a");
            $lexer->addPattern("a+", "a");
            $lexer->addPattern("b+", "b");
            $this->assertTrue($lexer->parse("abaabxbaaaxaaaax"));
            $handler->tally();
        }
        
        function testModeChange() {
            $handler = &new MockTestParser($this);
            $handler->expectArgumentsAt(0, "a", array("a", LEXER_MATCHED));
            $handler->expectArgumentsAt(1, "a", array("b", LEXER_UNMATCHED));
            $handler->expectArgumentsAt(2, "a", array("aa", LEXER_MATCHED));
            $handler->expectArgumentsAt(3, "a", array("b", LEXER_UNMATCHED));
            $handler->expectArgumentsAt(4, "a", array("aaa", LEXER_MATCHED));
            $handler->expectArgumentsAt(0, "b", array(":", LEXER_ENTER));
            $handler->expectArgumentsAt(1, "b", array("a", LEXER_UNMATCHED));
            $handler->expectArgumentsAt(2, "b", array("b", LEXER_MATCHED));
            $handler->expectArgumentsAt(3, "b", array("a", LEXER_UNMATCHED));
            $handler->expectArgumentsAt(4, "b", array("bb", LEXER_MATCHED));
            $handler->expectArgumentsAt(5, "b", array("a", LEXER_UNMATCHED));
            $handler->expectArgumentsAt(6, "b", array("bbb", LEXER_MATCHED));
            $handler->expectArgumentsAt(7, "b", array("a", LEXER_UNMATCHED));
            $handler->expectCallCount("a", 5);
            $handler->expectCallCount("b", 8);
            $handler->setReturnValue("a", true);
            $handler->setReturnValue("b", true);
            $lexer = &new SimpleLexer($handler, "a");
            $lexer->addPattern("a+", "a");
            $lexer->addEntryPattern(":", "a", "b");
            $lexer->addPattern("b+", "b");
            $this->assertTrue($lexer->parse("abaabaaa:ababbabbba"));
            $handler->tally();
        }
        
        function testNesting() {
            $handler = &new MockTestParser($this);
            $handler->setReturnValue("a", true);
            $handler->setReturnValue("b", true);
            $handler->expectArgumentsAt(0, "a", array("aa", LEXER_MATCHED));
            $handler->expectArgumentsAt(1, "a", array("b", LEXER_UNMATCHED));
            $handler->expectArgumentsAt(2, "a", array("aa", LEXER_MATCHED));
            $handler->expectArgumentsAt(3, "a", array("b", LEXER_UNMATCHED));
            $handler->expectArgumentsAt(0, "b", array("(", LEXER_ENTER));
            $handler->expectArgumentsAt(1, "b", array("bb", LEXER_MATCHED));
            $handler->expectArgumentsAt(2, "b", array("a", LEXER_UNMATCHED));
            $handler->expectArgumentsAt(3, "b", array("bb", LEXER_MATCHED));
            $handler->expectArgumentsAt(4, "b", array(")", LEXER_EXIT));
            $handler->expectArgumentsAt(4, "a", array("aa", LEXER_MATCHED));
            $handler->expectArgumentsAt(5, "a", array("b", LEXER_UNMATCHED));
            $handler->expectCallCount("a", 6);
            $handler->expectCallCount("b", 5);
            $lexer = &new SimpleLexer($handler, "a");
            $lexer->addPattern("a+", "a");
            $lexer->addEntryPattern("(", "a", "b");
            $lexer->addPattern("b+", "b");
            $lexer->addExitPattern(")", "b");
            $this->assertTrue($lexer->parse("aabaab(bbabb)aab"));
            $handler->tally();
        }
        
        function testSingular() {
            $handler = &new MockTestParser($this);
            $handler->setReturnValue("a", true);
            $handler->setReturnValue("b", true);
            $handler->expectArgumentsAt(0, "a", array("aa", LEXER_MATCHED));
            $handler->expectArgumentsAt(1, "a", array("aa", LEXER_MATCHED));
            $handler->expectArgumentsAt(2, "a", array("xx", LEXER_UNMATCHED));
            $handler->expectArgumentsAt(3, "a", array("xx", LEXER_UNMATCHED));
            $handler->expectArgumentsAt(0, "b", array("b", LEXER_SPECIAL));
            $handler->expectArgumentsAt(1, "b", array("bbb", LEXER_SPECIAL));
            $handler->expectCallCount("a", 4);
            $handler->expectCallCount("b", 2);
            $lexer = &new SimpleLexer($handler, "a");
            $lexer->addPattern("a+", "a");
            $lexer->addSpecialPattern("b+", "a", "b");
            $this->assertTrue($lexer->parse("aabaaxxbbbxx"));
            $handler->tally();
        }
        
        function testUnwindTooFar() {
            $handler = &new MockTestParser($this);
            $handler->setReturnValue("a", true);
            $handler->expectArgumentsAt(0, "a", array("aa", LEXER_MATCHED));
            $handler->expectArgumentsAt(1, "a", array(")", LEXER_EXIT));
            $handler->expectCallCount("a", 2);
            $lexer = &new SimpleLexer($handler, "a");
            $lexer->addPattern("a+", "a");
            $lexer->addExitPattern(")", "a");
            $this->assertFalse($lexer->parse("aa)aa"));
            $handler->tally();
        }
    }

    class TestOfLexerHandlers extends UnitTestCase {
        
        function testModeMapping() {
            $handler = &new MockTestParser($this);
            $handler->setReturnValue("a", true);
            $handler->expectArgumentsAt(0, "a", array("aa", LEXER_MATCHED));
            $handler->expectArgumentsAt(1, "a", array("(", LEXER_ENTER));
            $handler->expectArgumentsAt(2, "a", array("bb", LEXER_MATCHED));
            $handler->expectArgumentsAt(3, "a", array("a", LEXER_UNMATCHED));
            $handler->expectArgumentsAt(4, "a", array("bb", LEXER_MATCHED));
            $handler->expectArgumentsAt(5, "a", array(")", LEXER_EXIT));
            $handler->expectArgumentsAt(6, "a", array("b", LEXER_UNMATCHED));
            $handler->expectCallCount("a", 7);
            $lexer = &new SimpleLexer($handler, "mode_a");
            $lexer->addPattern("a+", "mode_a");
            $lexer->addEntryPattern("(", "mode_a", "mode_b");
            $lexer->addPattern("b+", "mode_b");
            $lexer->addExitPattern(")", "mode_b");
            $lexer->mapHandler("mode_a", "a");
            $lexer->mapHandler("mode_b", "a");
            $this->assertTrue($lexer->parse("aa(bbabb)b"));
            $handler->tally();
        }
    }
    
    Mock::generate("HtmlSaxParser");
    
    class TestOfHtmlLexer extends UnitTestCase {
        var $_handler;
        var $_lexer;
        
        function setUp() {
            $this->_handler = &new MockSimpleSaxParser($this);
            $this->_handler->setReturnValue("acceptStartToken", true);
            $this->_handler->setReturnValue("acceptEndToken", true);
            $this->_handler->setReturnValue("acceptAttributeToken", true);
            $this->_handler->setReturnValue("acceptEntityToken", true);
            $this->_handler->setReturnValue("acceptTextToken", true);
            $this->_handler->setReturnValue("ignore", true);
            $this->_lexer = &SimpleSaxParser::createLexer($this->_handler);
        }
        
        function tearDown() {
            $this->_handler->tally();
        }
        
        function testUninteresting() {
            $this->_handler->expectOnce("acceptTextToken", array("<html></html>", "*"));
            $this->assertTrue($this->_lexer->parse("<html></html>"));
        }
        
        function testSkipCss() {
            $this->_handler->expectMaximumCallCount("acceptTextToken", 0);
            $this->_handler->expectAtLeastOnce("ignore");
            $this->assertTrue($this->_lexer->parse("<style>Lot's of styles</style>"));
        }
        
        function testSkipJavaScript() {
            $this->_handler->expectMaximumCallCount("acceptTextToken", 0);
            $this->_handler->expectAtLeastOnce("ignore");
            $this->assertTrue($this->_lexer->parse("<SCRIPT>Javascript code {';:^%^%£$'@\"*(}</SCRIPT>"));
        }
        
        function testSkipComments() {
            $this->_handler->expectMaximumCallCount("acceptTextToken", 0);
            $this->_handler->expectAtLeastOnce("ignore");
            $this->assertTrue($this->_lexer->parse("<!-- <style>Lot's of styles</style> -->"));
        }
        
        function testTitleTag() {
            $this->_handler->expectArgumentsAt(0, "acceptStartToken", array("<title", "*"));
            $this->_handler->expectArgumentsAt(1, "acceptStartToken", array(">", "*"));
            $this->_handler->expectCallCount("acceptStartToken", 2);
            $this->_handler->expectOnce("acceptTextToken", array("Hello", "*"));
            $this->_handler->expectOnce("acceptEndToken", array("</title>", "*"));
            $this->assertTrue($this->_lexer->parse("<title>Hello</title>"));
        }
        
        function testFramesetTag() {
            $this->_handler->expectArgumentsAt(0, "acceptStartToken", array("<frameset", "*"));
            $this->_handler->expectArgumentsAt(1, "acceptStartToken", array(">", "*"));
            $this->_handler->expectCallCount("acceptStartToken", 2);
            $this->_handler->expectOnce("acceptTextToken", array("Frames", "*"));
            $this->_handler->expectOnce("acceptEndToken", array("</frameset>", "*"));
            $this->assertTrue($this->_lexer->parse("<frameset>Frames</frameset>"));
        }
        
        function testInputTag() {
            $this->_handler->expectArgumentsAt(0, "acceptStartToken", array("<input", "*"));
            $this->_handler->expectArgumentsAt(1, "acceptStartToken", array("name", "*"));
            $this->_handler->expectArgumentsAt(2, "acceptStartToken", array("value", "*"));
            $this->_handler->expectArgumentsAt(3, "acceptStartToken", array(">", "*"));
            $this->_handler->expectArgumentsAt(0, "acceptAttributeToken", array("=a.b.c", "*"));
            $this->_handler->expectArgumentsAt(1, "acceptAttributeToken", array("= d", "*"));
            $this->assertTrue($this->_lexer->parse("<input name=a.b.c value = d>"));
        }
        
        function testEmptyLink() {
            $this->_handler->expectArgumentsAt(0, "acceptStartToken", array("<a", "*"));
            $this->_handler->expectArgumentsAt(1, "acceptStartToken", array(">", "*"));
            $this->_handler->expectCallCount("acceptStartToken", 2);
            $this->_handler->expectOnce("acceptEndToken", array("</a>", "*"));
            $this->assertTrue($this->_lexer->parse("<html><a></a></html>"));
        }
        
        function testLabelledLink() {
            $this->_handler->expectArgumentsAt(0, "acceptStartToken", array("<a", "*"));
            $this->_handler->expectArgumentsAt(1, "acceptStartToken", array(">", "*"));
            $this->_handler->expectCallCount("acceptStartToken", 2);
            $this->_handler->expectOnce("acceptEndToken", array("</a>", "*"));
            $this->_handler->expectArgumentsAt(0, "acceptTextToken", array("<html>", "*"));
            $this->_handler->expectArgumentsAt(1, "acceptTextToken", array("label", "*"));
            $this->_handler->expectArgumentsAt(2, "acceptTextToken", array("</html>", "*"));
            $this->_handler->expectCallCount("acceptTextToken", 3);
            $this->assertTrue($this->_lexer->parse("<html><a>label</a></html>"));
        }
        
        function testLinkAddress() {
            $this->_handler->expectArgumentsAt(0, "acceptTextToken", array("<html>", "*"));
            $this->_handler->expectArgumentsAt(1, "acceptTextToken", array("label", "*"));
            $this->_handler->expectArgumentsAt(2, "acceptTextToken", array("</html>", "*"));
            $this->_handler->expectCallCount("acceptTextToken", 3);
            $this->_handler->expectArgumentsAt(0, "acceptStartToken", array("<a", "*"));
            $this->_handler->expectArgumentsAt(1, "acceptStartToken", array("href", "*"));
            $this->_handler->expectArgumentsAt(2, "acceptStartToken", array(">", "*"));
            $this->_handler->expectCallCount("acceptStartToken", 3);
            $this->_handler->expectArgumentsAt(0, "acceptAttributeToken", array("= '", "*"));
            $this->_handler->expectArgumentsAt(1, "acceptAttributeToken", array("here.html", "*"));
            $this->_handler->expectArgumentsAt(2, "acceptAttributeToken", array("'", "*"));
            $this->_handler->expectCallCount("acceptAttributeToken", 3);
            $this->assertTrue($this->_lexer->parse("<html><a href = 'here.html'>label</a></html>"));
        }
        
        function testEncodedLinkAddress() {
            $this->_handler->expectArgumentsAt(0, "acceptTextToken", array("<html>", "*"));
            $this->_handler->expectArgumentsAt(1, "acceptTextToken", array("label", "*"));
            $this->_handler->expectArgumentsAt(2, "acceptTextToken", array("</html>", "*"));
            $this->_handler->expectCallCount("acceptTextToken", 3);
            $this->_handler->expectArgumentsAt(0, "acceptStartToken", array("<a", "*"));
            $this->_handler->expectArgumentsAt(1, "acceptStartToken", array("href", "*"));
            $this->_handler->expectArgumentsAt(2, "acceptStartToken", array(">", "*"));
            $this->_handler->expectCallCount("acceptStartToken", 3);
            $this->_handler->expectArgumentsAt(0, "acceptAttributeToken", array("= '", "*"));
            $this->_handler->expectArgumentsAt(1, "acceptAttributeToken", array("here&amp;there.html", "*"));
            $this->_handler->expectArgumentsAt(2, "acceptAttributeToken", array("'", "*"));
            $this->_handler->expectCallCount("acceptAttributeToken", 3);
            $this->assertTrue($this->_lexer->parse("<html><a href = 'here&amp;there.html'>label</a></html>"));
        }
        
        function testEmptyLinkWithId() {
            $this->_handler->expectArgumentsAt(0, "acceptTextToken", array("<html>", "*"));
            $this->_handler->expectArgumentsAt(1, "acceptTextToken", array("label", "*"));
            $this->_handler->expectArgumentsAt(2, "acceptTextToken", array("</html>", "*"));
            $this->_handler->expectCallCount("acceptTextToken", 3);
            $this->_handler->expectArgumentsAt(0, "acceptStartToken", array("<a", "*"));
            $this->_handler->expectArgumentsAt(1, "acceptStartToken", array("id", "*"));
            $this->_handler->expectArgumentsAt(2, "acceptStartToken", array(">", "*"));
            $this->_handler->expectCallCount("acceptStartToken", 3);
            $this->_handler->expectArgumentsAt(0, "acceptAttributeToken", array("=\"", "*"));
            $this->_handler->expectArgumentsAt(1, "acceptAttributeToken", array("0", "*"));
            $this->_handler->expectArgumentsAt(2, "acceptAttributeToken", array("\"", "*"));
            $this->_handler->expectCallCount("acceptAttributeToken", 3);
            $this->assertTrue($this->_lexer->parse("<html><a id=\"0\">label</a></html>"));
        }
        
        function testComplexLink() {
            $this->_handler->expectArgumentsAt(0, "acceptStartToken", array("<a", LEXER_ENTER));
            $this->_handler->expectArgumentsAt(1, "acceptStartToken", array("HREF", "*"));
            $this->_handler->expectArgumentsAt(2, "acceptStartToken", array("bool", "*"));
            $this->_handler->expectArgumentsAt(3, "acceptStartToken", array("Style", "*"));
            $this->_handler->expectArgumentsAt(4, "acceptStartToken", array(">", LEXER_EXIT));
            $this->_handler->expectCallCount("acceptStartToken", 5);
            $this->_handler->expectArgumentsAt(0, "acceptAttributeToken", array("= '", "*"));
            $this->_handler->expectArgumentsAt(1, "acceptAttributeToken", array("here.html", LEXER_UNMATCHED));
            $this->_handler->expectArgumentsAt(2, "acceptAttributeToken", array("'", "*"));
            $this->_handler->expectArgumentsAt(3, "acceptAttributeToken", array("=\"", "*"));
            $this->_handler->expectArgumentsAt(4, "acceptAttributeToken", array("'coo", "*"));
            $this->_handler->expectArgumentsAt(5, "acceptAttributeToken", array('\"', "*"));
            $this->_handler->expectArgumentsAt(6, "acceptAttributeToken", array("l'", "*"));
            $this->_handler->expectArgumentsAt(7, "acceptAttributeToken", array("\"", "*"));
            $this->_handler->expectCallCount("acceptAttributeToken", 8);
            $this->assertTrue($this->_lexer->parse("<HTML><a HREF = 'here.html' bool Style=\"'coo\\\"l'\">label</A></Html>"));
        }
        
        function testSubmit() {
            $this->_handler->expectArgumentsAt(0, "acceptStartToken", array("<input", LEXER_ENTER));
            $this->_handler->expectArgumentsAt(1, "acceptStartToken", array("type", "*"));
            $this->_handler->expectArgumentsAt(2, "acceptStartToken", array("name", "*"));
            $this->_handler->expectArgumentsAt(3, "acceptStartToken", array("value", "*"));
            $this->_handler->expectArgumentsAt(4, "acceptStartToken", array("/", "*"));
            $this->_handler->expectArgumentsAt(5, "acceptStartToken", array(">", LEXER_EXIT));
            $this->_handler->expectCallCount("acceptStartToken", 6);
            $this->assertTrue($this->_lexer->parse('<input type="submit" name="N" value="V" />'));
        }
        
        function testFramesParsedWithoutError() {
            $this->assertTrue($this->_lexer->parse(
                    '<frameset><frame src="frame.html"></frameset>'));
            $this->assertTrue($this->_lexer->parse(
                    '<frameset><frame src="frame.html"><noframes>Hello</noframes></frameset>'));
        }
    }
    
    class TestOfTextExtraction extends UnitTestCase {
        
        function testSpaceNormalisation() {
            $this->assertEqual(
                    SimpleSaxParser::normalise("\nOne\tTwo   \nThree\t"),
                    'One Two Three');            
        }
        
        function testTagSuppression() {
            $this->assertEqual(
                    SimpleSaxParser::normalise('<b>Hello</b>'),
                    'Hello');            
        }
        
        function testAdjoiningTagSuppression() {
            $this->assertEqual(
                    SimpleSaxParser::normalise('<b>Hello</b><em>Goodbye</em>'),
                    'HelloGoodbye');            
        }
        
        function testExtractImageAltTextWithDifferentQuotes() {
            $this->assertEqual(
                    SimpleSaxParser::normalise('<img alt="One"><img alt=\'Two\'><img alt=Three>'),
                    'One Two Three');
        }
        
        function testExtractImageAltTextMultipleTimes() {
            $this->assertEqual(
                    SimpleSaxParser::normalise('<img alt="One"><img alt="Two"><img alt="Three">'),
                    'One Two Three');
        }
        
        function testHtmlEntityTranslation() {
            $this->assertEqual(
                    SimpleSaxParser::normalise('&lt;&gt;&quot;&amp;'),
                    '<>"&');
        }
    }

    class TestSimpleSaxParser extends SimpleSaxParser {
        var $_lexer;
        
        function TestSimpleSaxParser(&$listener, &$lexer) {
            $this->_lexer = &$lexer;
            $this->SimpleSaxParser($listener);
        }
        
        function &createLexer() {
            return $this->_lexer;
        }
    }
    
    Mock::generate("SimpleSaxListener");
    Mock::generate("SimpleLexer");
    
    class TestOfSaxGeneration extends UnitTestCase {
        var $_listener;
        var $_lexer;
        
        function setUp() {
            $this->_listener = &new MockSimpleSaxListener($this);
            $this->_lexer = &new MockSimpleLexer($this);
            $this->_parser = &new TestSimpleSaxParser($this->_listener, $this->_lexer);
        }
        
        function tearDown() {
            $this->_listener->tally();
            $this->_lexer->tally();
        }
        
        function testLexerFailure() {
            $this->_lexer->setReturnValue("parse", false);
            $this->assertFalse($this->_parser->parse("<html></html>"));
        }
        
        function testLexerSuccess() {
            $this->_lexer->setReturnValue("parse", true);
            $this->assertTrue($this->_parser->parse("<html></html>"));
        }
        
        function testSimpleLinkStart() {
            $this->_parser->parse("");
            $this->_listener->expectOnce("startElement", array("a", array()));
            $this->_listener->setReturnValue("startElement", true);
            $this->assertTrue($this->_parser->acceptStartToken("<a", LEXER_ENTER));
            $this->assertTrue($this->_parser->acceptStartToken(">", LEXER_EXIT));
        }
        
        function testSimpleTitleStart() {
            $this->_parser->parse("");
            $this->_listener->expectOnce("startElement", array("title", array()));
            $this->_listener->setReturnValue("startElement", true);
            $this->assertTrue($this->_parser->acceptStartToken("<title", LEXER_ENTER));
            $this->assertTrue($this->_parser->acceptStartToken(">", LEXER_EXIT));
        }
        
        function testLinkStart() {
            $this->_parser->parse("");
            $this->_listener->expectOnce("startElement", array("a", array("href" => "here.html")));
            $this->_listener->setReturnValue("startElement", true);
            $this->assertTrue($this->_parser->acceptStartToken("<a", LEXER_ENTER));
            $this->assertTrue($this->_parser->acceptStartToken("href", LEXER_MATCHED));
            $this->assertTrue($this->_parser->acceptAttributeToken("=\"", LEXER_ENTER));
            $this->assertTrue($this->_parser->acceptAttributeToken("here.html", LEXER_UNMATCHED));
            $this->assertTrue($this->_parser->acceptAttributeToken("\"", LEXER_EXIT));
            $this->assertTrue($this->_parser->acceptStartToken(">", LEXER_EXIT));
        }
        
        function testLinkStartWithEncodedUrl() {
            $this->_parser->parse("");
            $this->_listener->expectOnce(
                    "startElement",
                    array("a", array("href" => "here&there.html")));
            $this->_listener->setReturnValue("startElement", true);
            $this->assertTrue($this->_parser->acceptStartToken("<a", LEXER_ENTER));
            $this->assertTrue($this->_parser->acceptStartToken("href", LEXER_MATCHED));
            $this->assertTrue($this->_parser->acceptAttributeToken("=\"", LEXER_ENTER));
            $this->assertTrue($this->_parser->acceptAttributeToken("here&amp;there.html", LEXER_UNMATCHED));
            $this->assertTrue($this->_parser->acceptAttributeToken("\"", LEXER_EXIT));
            $this->assertTrue($this->_parser->acceptStartToken(">", LEXER_EXIT));
        }
        
        function testLinkStartWithId() {
            $this->_parser->parse("");
            $this->_listener->expectOnce(
                    "startElement",
                    array("a", array("id" => "0")));
            $this->_listener->setReturnValue("startElement", true);
            $this->assertTrue($this->_parser->acceptStartToken("<a", LEXER_ENTER));
            $this->assertTrue($this->_parser->acceptStartToken("id", LEXER_MATCHED));
            $this->assertTrue($this->_parser->acceptAttributeToken("= \"", LEXER_ENTER));
            $this->assertTrue($this->_parser->acceptAttributeToken("0", LEXER_UNMATCHED));
            $this->assertTrue($this->_parser->acceptAttributeToken("\"", LEXER_EXIT));
            $this->assertTrue($this->_parser->acceptStartToken(">", LEXER_EXIT));
        }
        
        function testLinkEnd() {
            $this->_parser->parse("");
            $this->_listener->expectOnce("endElement", array("a"));
            $this->_listener->setReturnValue("endElement", true);
            $this->assertTrue($this->_parser->acceptEndToken("</a>", LEXER_SPECIAL));
        }
        
        function testInput() {
            $this->_parser->parse("");
            $this->_listener->expectOnce(
                    "startElement",
                    array("input", array("name" => "a")));
            $this->_listener->setReturnValue("startElement", true);
            $this->assertTrue($this->_parser->acceptStartToken("<input", LEXER_ENTER));
            $this->assertTrue($this->_parser->acceptStartToken("name", LEXER_MATCHED));
            $this->assertTrue($this->_parser->acceptAttributeToken("= a", LEXER_SPECIAL));
            $this->assertTrue($this->_parser->acceptStartToken(">", LEXER_EXIT));
        }
         
        function testButton() {
            $this->_parser->parse("");
            $this->_listener->expectOnce(
                    "startElement",
                    array("button", array("name" => "a")));
            $this->_listener->setReturnValue("startElement", true);
            $this->assertTrue($this->_parser->acceptStartToken("<button", LEXER_ENTER));
            $this->assertTrue($this->_parser->acceptStartToken("name", LEXER_MATCHED));
            $this->assertTrue($this->_parser->acceptAttributeToken("= a", LEXER_SPECIAL));
            $this->assertTrue($this->_parser->acceptStartToken(">", LEXER_EXIT));
        }
       
        function testContent() {
            $this->_parser->parse("");
            $this->_listener->expectOnce("addContent", array("stuff"));
            $this->_listener->setReturnValue("addContent", true);
            $this->assertTrue($this->_parser->acceptTextToken("stuff", LEXER_UNMATCHED));
        }
        
        function testIgnore() {
            $this->_parser->parse("");
            $this->_listener->expectNever("addContent");
            $this->assertTrue($this->_parser->ignore("stuff", LEXER_UNMATCHED));
        }
    }
?>
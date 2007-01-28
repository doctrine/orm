<?php
    // $Id: user_agent_test.php,v 1.22 2005/01/02 22:46:10 lastcraft Exp $
    
    require_once(dirname(__FILE__) . '/../user_agent.php');
    require_once(dirname(__FILE__) . '/../authentication.php');
    require_once(dirname(__FILE__) . '/../http.php');
    require_once(dirname(__FILE__) . '/../encoding.php');
    Mock::generate('SimpleHttpRequest');
    Mock::generate('SimpleHttpResponse');
    Mock::generate('SimpleHttpHeaders');
    Mock::generatePartial('SimpleUserAgent', 'MockRequestUserAgent', array('_createHttpRequest'));

    class TestOfSimpleCookieJar extends UnitTestCase {
        
        function testAddCookie() {
            $jar = new SimpleCookieJar();
            $jar->setCookie(new SimpleCookie("a", "A"));
            $cookies = $jar->getValidCookies();
            $this->assertEqual(count($cookies), 1);
            $this->assertEqual($cookies[0]->getValue(), "A");
        }
        
        function testHostFilter() {
            $jar = new SimpleCookieJar();
            $cookie = new SimpleCookie('a', 'A');
            $cookie->setHost('my-host.com');
            $jar->setCookie($cookie);
            $cookie = new SimpleCookie('b', 'B');
            $cookie->setHost('another-host.com');
            $jar->setCookie($cookie);
            $cookie = new SimpleCookie('c', 'C');
            $jar->setCookie($cookie);
            $cookies = $jar->getValidCookies('my-host.com');
            $this->assertEqual(count($cookies), 2);
            $this->assertEqual($cookies[0]->getValue(), 'A');
            $this->assertEqual($cookies[1]->getValue(), 'C');
            $this->assertEqual(count($jar->getValidCookies('another-host.com')), 2);
            $this->assertEqual(count($jar->getValidCookies('www.another-host.com')), 2);
            $this->assertEqual(count($jar->getValidCookies('new-host.org')), 1);
            $this->assertEqual(count($jar->getValidCookies()), 3);
        }
        
        function testPathFilter() {
            $jar = new SimpleCookieJar();
            $jar->setCookie(new SimpleCookie("a", "A", "/path/"));
            $this->assertEqual(count($jar->getValidCookies(false, "/")), 0);
            $this->assertEqual(count($jar->getValidCookies(false, "/elsewhere")), 0);
            $this->assertEqual(count($jar->getValidCookies(false, "/path/")), 1);
            $this->assertEqual(count($jar->getValidCookies(false, "/path")), 1);
            $this->assertEqual(count($jar->getValidCookies(false, "/pa")), 0);
            $this->assertEqual(count($jar->getValidCookies(false, "/path/here/")), 1);
        }
        
        function testPathFilterDeeply() {
            $jar = new SimpleCookieJar();
            $jar->setCookie(new SimpleCookie("a", "A", "/path/more_path/"));
            $this->assertEqual(count($jar->getValidCookies(false, "/path/")), 0);
            $this->assertEqual(count($jar->getValidCookies(false, "/path")), 0);
            $this->assertEqual(count($jar->getValidCookies(false, "/pa")), 0);
            $this->assertEqual(count($jar->getValidCookies(false, "/path/more_path/")), 1);
            $this->assertEqual(count($jar->getValidCookies(false, "/path/more_path/and_more")), 1);
            $this->assertEqual(count($jar->getValidCookies(false, "/path/not_here/")), 0);
        }
        
        function testMultipleCookieWithDifferentPaths() {
            $jar = new SimpleCookieJar();
            $jar->setCookie(new SimpleCookie("a", "abc", "/"));
            $jar->setCookie(new SimpleCookie("a", "123", "/path/here/"));
            $cookies = $jar->getValidCookies("my-host.com", "/");
            $this->assertEqual($cookies[0]->getPath(), "/");
            $cookies = $jar->getValidCookies("my-host.com", "/path/");
            $this->assertEqual($cookies[0]->getPath(), "/");
            $cookies = $jar->getValidCookies("my-host.com", "/path/here");
            $this->assertEqual($cookies[0]->getPath(), "/");
            $this->assertEqual($cookies[1]->getPath(), "/path/here/");
            $cookies = $jar->getValidCookies("my-host.com", "/path/here/there");
            $this->assertEqual($cookies[0]->getPath(), "/");
            $this->assertEqual($cookies[1]->getPath(), "/path/here/");
        }
        
        function testOverwrite() {
            $jar = new SimpleCookieJar();
            $jar->setCookie(new SimpleCookie("a", "abc", "/"));
            $jar->setCookie(new SimpleCookie("a", "cde", "/"));
            $cookies = $jar->getValidCookies();
            $this->assertIdentical($cookies[0]->getValue(), "cde");
        }
        
        function testClearSessionCookies() {
            $jar = new SimpleCookieJar();
            $jar->setCookie(new SimpleCookie("a", "A", "/"));
            $jar->restartSession();
            $this->assertEqual(count($jar->getValidCookies(false, "/")), 0);
        }
        
        function testExpiryFilterByDate() {
            $cookie = new SimpleCookie("a", "A", "/", "Wed, 25-Dec-02 04:24:20 GMT");
            $jar = new SimpleCookieJar();
            $jar->setCookie($cookie);
            $jar->restartSession("Wed, 25-Dec-02 04:24:19 GMT");
            $this->assertIdentical($list = $jar->getValidCookies(false, "/"), array($cookie));
            $jar->restartSession("Wed, 25-Dec-02 04:24:21 GMT");
            $this->assertIdentical($list = $jar->getValidCookies(false, "/"), array());
        }
        
        function testExpiryFilterByAgeing() {
            $cookie = new SimpleCookie("a", "A", "/", "Wed, 25-Dec-02 04:24:20 GMT");
            $jar = new SimpleCookieJar();
            $jar->setCookie($cookie);
            $jar->restartSession("Wed, 25-Dec-02 04:24:19 GMT");
            $this->assertIdentical($list = $jar->getValidCookies(false, "/"), array($cookie));
            $jar->agePrematurely(2);
            $jar->restartSession("Wed, 25-Dec-02 04:24:19 GMT");
            $this->assertIdentical($list = $jar->getValidCookies(false, "/"), array());
        }
        
        function testCookieClearing() {
            $jar = new SimpleCookieJar();
            $jar->setCookie(new SimpleCookie("a", "abc", "/"));
            $jar->setCookie(new SimpleCookie("a", "", "/"));
            $this->assertEqual(count($cookies = $jar->getValidCookies(false, "/")), 1);
            $this->assertIdentical($cookies[0]->getValue(), "");
        }
        
        function testCookieClearByDate() {
            $jar = new SimpleCookieJar();
            $jar->setCookie(new SimpleCookie("a", "abc", "/", "Wed, 25-Dec-02 04:24:21 GMT"));
            $jar->setCookie(new SimpleCookie("a", "def", "/", "Wed, 25-Dec-02 04:24:19 GMT"));
            $cookies = $jar->getValidCookies(false, "/");
            $this->assertIdentical($cookies[0]->getValue(), "def");
            $jar->restartSession("Wed, 25-Dec-02 04:24:20 GMT");
            $this->assertEqual(count($jar->getValidCookies(false, "/")), 0);
        }
    }
    
    class TestOfFetchingUrlParameters extends UnitTestCase {
        
        function testGet() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getMimeType', 'text/html');
            $headers->setReturnValue('getResponseCode', 200);
            $headers->setReturnValue('getNewCookies', array());
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', 'stuff');
            $response->setReturnReference('getHeaders', $headers);
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference('fetch', $response);
            
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReference('_createHttpRequest', $request);
            $agent->expectOnce('_createHttpRequest', array(
                    'GET',
                    new SimpleUrl('http://test:secret@this.com/page.html?a=A&b=B'),
                    new SimpleFormEncoding()));
            $agent->SimpleUserAgent();
            
            $agent->fetchResponse(
                    'GET',
                    new SimpleUrl('http://test:secret@this.com/page.html'),
                    new SimpleFormEncoding(array('a' => 'A', 'b' => 'B')));
            $agent->tally();
        }
        
        function testHead() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getMimeType', 'text/html');
            $headers->setReturnValue('getResponseCode', 200);
            $headers->setReturnValue('getNewCookies', array());
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', 'stuff');
            $response->setReturnReference('getHeaders', $headers);
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference('fetch', $response);
            
            $url = new SimpleUrl('http://this.com/page.html');
            $url->addRequestParameters(array('a' => 'A', 'b' => 'B'));
            
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReference('_createHttpRequest', $request);
            $agent->expectOnce('_createHttpRequest', array(
                    'HEAD',
                    new SimpleUrl('http://test:secret@this.com/page.html?a=A&b=B'),
                    new SimpleFormEncoding()));
            $agent->SimpleUserAgent();
            
            $agent->fetchResponse(
                    'HEAD',
                    new SimpleUrl('http://test:secret@this.com/page.html'),
                    new SimpleFormEncoding(array('a' => 'A', 'b' => 'B')));
            $agent->tally();
        }
        
        function testPost() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getMimeType', 'text/html');
            $headers->setReturnValue('getResponseCode', 200);
            $headers->setReturnValue('getNewCookies', array());
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', 'stuff');
            $response->setReturnReference('getHeaders', $headers);
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference('fetch', $response);
            
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReference('_createHttpRequest', $request);
            $agent->expectOnce('_createHttpRequest', array(
                    'POST',
                    new SimpleUrl('http://test:secret@this.com/page.html'),
                    new SimpleFormEncoding(array('a' => 'A', 'b' => 'B'))));
            $agent->SimpleUserAgent();
            
            $agent->fetchResponse(
                    'POST',
                    new SimpleUrl('http://test:secret@this.com/page.html'),
                    new SimpleFormEncoding(array('a' => 'A', 'b' => 'B')));
            $agent->tally();
        }
    }

    class TestOfAdditionalHeaders extends UnitTestCase {
        
        function testAdditionalHeaderAddedToRequest() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getNewCookies', array());
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnReference('getHeaders', $headers);
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference('fetch', $response);
            $request->expectOnce(
                    'addHeaderLine',
                    array('User-Agent: SimpleTest'));
            
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReference('_createHttpRequest', $request);
            $agent->SimpleUserAgent();
            
            $agent->addHeader('User-Agent: SimpleTest');
            $response = &$agent->fetchResponse('GET', new SimpleUrl('http://this.host/'));
            $request->tally();
        }
    }

    class TestOfBrowserCookies extends UnitTestCase {

        function &_createStandardResponse() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue("getNewCookies", array());
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("isError", false);
            $response->setReturnValue("getContent", "stuff");
            $response->setReturnReference("getHeaders", $headers);
            return $response;
        }
        
        function &_createCookieSite($cookies) {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue("getNewCookies", $cookies);
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue("isError", false);
            $response->setReturnReference("getHeaders", $headers);
            $response->setReturnValue("getContent", "stuff");
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference("fetch", $response);
            return $request;
        }
        
        function &_createPartialFetcher(&$request) {
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReference('_createHttpRequest', $request);
            $agent->SimpleUserAgent();
            return $agent;
        }
        
        function testSendingExistingCookie() {
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference('fetch', $this->_createStandardResponse());
            $request->expectOnce('setCookie', array(new SimpleCookie('a', 'A')));
            
            $agent = &$this->_createPartialFetcher($request);
            $agent->setCookie('a', 'A');
            $response = $agent->fetchResponse(
                    'GET',
                    new SimpleUrl('http://this.com/this/path/page.html'),
                    array());
            $this->assertEqual($response->getContent(), "stuff");
            $request->tally();
        }
        
        function testOverwriteCookieThatAlreadyExists() {
            $request = &$this->_createCookieSite(array(new SimpleCookie("a", "AAAA", "this/path/")));
            $agent = &$this->_createPartialFetcher($request);
            
            $agent->setCookie("a", "A");
            $agent->fetchResponse(
                    "GET",
                    new SimpleUrl('http://this.com/this/path/page.html'),
                    array());
            $this->assertEqual($agent->getCookieValue("this.com", "this/path/", "a"), "AAAA");
        }
        
        function testClearCookieBySettingExpiry() {
            $request = &$this->_createCookieSite(array(
                    new SimpleCookie("a", "b", "this/path/", "Wed, 25-Dec-02 04:24:19 GMT")));
            $agent = &$this->_createPartialFetcher($request);
            
            $agent->setCookie("a", "A", "this/path/", "Wed, 25-Dec-02 04:24:21 GMT");
            $agent->fetchResponse(
                    'GET',
                    new SimpleUrl('http://this.com/this/path/page.html'),
                    array());
            $this->assertIdentical(
                    $agent->getCookieValue("this.com", "this/path/", "a"),
                    "b");
            $agent->restart("Wed, 25-Dec-02 04:24:20 GMT");
            $this->assertIdentical(
                    $agent->getCookieValue("this.com", "this/path/", "a"),
                    false);
        }
        
        function testAgeingAndClearing() {
            $request = &$this->_createCookieSite(array(
                    new SimpleCookie("a", "A", "this/path/", "Wed, 25-Dec-02 04:24:21 GMT")));
            $agent = &$this->_createPartialFetcher($request);
            
            $agent->fetchResponse(
                    'GET',
                    new SimpleUrl('http://this.com/this/path/page.html'),
                    array());
            $agent->restart("Wed, 25-Dec-02 04:24:20 GMT");
            $this->assertIdentical(
                    $agent->getCookieValue("this.com", "this/path/", "a"),
                    "A");
            $agent->ageCookies(2);
            $agent->restart("Wed, 25-Dec-02 04:24:20 GMT");
            $this->assertIdentical(
                    $agent->getCookieValue("this.com", "this/path/", "a"),
                    false);
        }
        
        function testReadingIncomingAndSetCookies() {
            $request = &$this->_createCookieSite(array(
                    new SimpleCookie("a", "AAA", "this/path/")));
            $agent = &$this->_createPartialFetcher($request);
            
            $this->assertNull($agent->getBaseCookieValue("a", false));
            $agent->fetchResponse(
                    'GET',
                    new SimpleUrl('http://this.com/this/path/page.html'),
                    array());
            $agent->setCookie("b", "BBB", "this.com", "this/path/");
            $this->assertEqual(
                    $agent->getBaseCookieValue("a", new SimpleUrl('http://this.com/this/path/page.html')),
                    "AAA");
            $this->assertEqual(
                    $agent->getBaseCookieValue("b", new SimpleUrl('http://this.com/this/path/page.html')),
                    "BBB");
        }
    }

    class TestOfHttpRedirects extends UnitTestCase {
        
        function &createRedirect($content, $redirect) {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getNewCookies', array());
            $headers->setReturnValue('isRedirect', (boolean)$redirect);
            $headers->setReturnValue('getLocation', $redirect);
            $headers->setReturnValue('getNewCookies', array());
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('getContent', $content);
            $response->setReturnReference('getHeaders', $headers);
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference('fetch', $response);
            return $request;
        }
        
        function testDisabledRedirects() {
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReference(
                    '_createHttpRequest',
                    $this->createRedirect('stuff', 'there.html'));
            $agent->expectOnce('_createHttpRequest');
            $agent->SimpleUserAgent();
            
            $agent->setMaximumRedirects(0);
            $response = &$agent->fetchResponse('GET', new SimpleUrl('here.html'));
            
            $this->assertEqual($response->getContent(), 'stuff');
            $agent->tally();
        }
        
        function testSingleRedirect() {
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReferenceAt(
                    0,
                    '_createHttpRequest',
                    $this->createRedirect('first', 'two.html'));
            $agent->setReturnReferenceAt(
                    1,
                    '_createHttpRequest',
                    $this->createRedirect('second', 'three.html'));
            $agent->expectCallCount('_createHttpRequest', 2);
            $agent->SimpleUserAgent();
            
            $agent->setMaximumRedirects(1);
            $response = &$agent->fetchResponse('GET', new SimpleUrl('one.html'));
            
            $this->assertEqual($response->getContent(), 'second');
            $agent->tally();
        }
        
        function testDoubleRedirect() {
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReferenceAt(
                    0,
                    '_createHttpRequest',
                    $this->createRedirect('first', 'two.html'));
            $agent->setReturnReferenceAt(
                    1,
                    '_createHttpRequest',
                    $this->createRedirect('second', 'three.html'));
            $agent->setReturnReferenceAt(
                    2,
                    '_createHttpRequest',
                    $this->createRedirect('third', 'four.html'));
            $agent->expectCallCount('_createHttpRequest', 3);
            $agent->SimpleUserAgent();
            
            $agent->setMaximumRedirects(2);
            $response = &$agent->fetchResponse('GET', new SimpleUrl('one.html'));
            
            $this->assertEqual($response->getContent(), 'third');
            $agent->tally();
        }
        
        function testSuccessAfterRedirect() {
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReferenceAt(
                    0,
                    '_createHttpRequest',
                    $this->createRedirect('first', 'two.html'));
            $agent->setReturnReferenceAt(
                    1,
                    '_createHttpRequest',
                    $this->createRedirect('second', false));
            $agent->setReturnReferenceAt(
                    2,
                    '_createHttpRequest',
                    $this->createRedirect('third', 'four.html'));
            $agent->expectCallCount('_createHttpRequest', 2);
            $agent->SimpleUserAgent();
            
            $agent->setMaximumRedirects(2);
            $response = &$agent->fetchResponse('GET', new SimpleUrl('one.html'));
            
            $this->assertEqual($response->getContent(), 'second');
            $agent->tally();
        }
        
        function testRedirectChangesPostToGet() {
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReferenceAt(
                    0,
                    '_createHttpRequest',
                    $this->createRedirect('first', 'two.html'));
            $agent->expectArgumentsAt(0, '_createHttpRequest', array('POST', '*', '*'));
            $agent->setReturnReferenceAt(
                    1,
                    '_createHttpRequest',
                    $this->createRedirect('second', 'three.html'));
            $agent->expectArgumentsAt(1, '_createHttpRequest', array('GET', '*', '*'));
            $agent->expectCallCount('_createHttpRequest', 2);
            $agent->SimpleUserAgent();
            
            $agent->setMaximumRedirects(1);
            $response = &$agent->fetchResponse('POST', new SimpleUrl('one.html'));
            
            $agent->tally();
        }
    }
    
    class TestOfBadHosts extends UnitTestCase {
        
        function &_createSimulatedBadHost() {
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnValue('isError', true);
            $response->setReturnValue('getError', 'Bad socket');
            $response->setReturnValue('getContent', false);
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference('fetch', $response);
            return $request;
        }
        
        function testUntestedHost() {
            $request = &$this->_createSimulatedBadHost();
            
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReference('_createHttpRequest', $request);
            $agent->SimpleUserAgent();
            
            $response = &$agent->fetchResponse(
                    'GET',
                    new SimpleUrl('http://this.host/this/path/page.html'));
            $this->assertTrue($response->isError());
        }
    }
    
    class TestOfAuthorisation extends UnitTestCase {
        
        function testAuthenticateHeaderAdded() {
            $headers = &new MockSimpleHttpHeaders($this);
            $headers->setReturnValue('getNewCookies', array());
            
            $response = &new MockSimpleHttpResponse($this);
            $response->setReturnReference('getHeaders', $headers);
            
            $request = &new MockSimpleHttpRequest($this);
            $request->setReturnReference('fetch', $response);
            $request->expectOnce(
                    'addHeaderLine',
                    array('Authorization: Basic ' . base64_encode('test:secret')));
            
            $agent = &new MockRequestUserAgent($this);
            $agent->setReturnReference('_createHttpRequest', $request);
            $agent->SimpleUserAgent();
            
            $response = &$agent->fetchResponse(
                    'GET',
                    new SimpleUrl('http://test:secret@this.host'));
            $request->tally();
        }
    }
?>
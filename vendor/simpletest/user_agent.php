<?php
    /**
     *	Base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	WebTester
     *	@version	$Id: user_agent.php,v 1.43 2005/01/02 22:46:08 lastcraft Exp $
     */

    /**#@+
     *	include other SimpleTest class files
     */
    require_once(dirname(__FILE__) . '/http.php');
    require_once(dirname(__FILE__) . '/encoding.php');
    require_once(dirname(__FILE__) . '/authentication.php');
    /**#@-*/
   
    define('DEFAULT_MAX_REDIRECTS', 3);
    define('DEFAULT_CONNECTION_TIMEOUT', 15);
    
    /**
     *    Repository for cookies. This stuff is a
     *    tiny bit browser dependent.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleCookieJar {
        var $_cookies;
        
        /**
         *    Constructor. Jar starts empty.
         *    @access public
         */
        function SimpleCookieJar() {
            $this->_cookies = array();
        }
        
        /**
         *    Removes expired and temporary cookies as if
         *    the browser was closed and re-opened.
         *    @param string/integer $now   Time to test expiry against.
         *    @access public
         */
        function restartSession($date = false) {
            $surviving_cookies = array();
            for ($i = 0; $i < count($this->_cookies); $i++) {
                if (! $this->_cookies[$i]->getValue()) {
                    continue;
                }
                if (! $this->_cookies[$i]->getExpiry()) {
                    continue;
                }
                if ($date && $this->_cookies[$i]->isExpired($date)) {
                    continue;
                }
                $surviving_cookies[] = $this->_cookies[$i];
            }
            $this->_cookies = $surviving_cookies;
        }
        
        /**
         *    Ages all cookies in the cookie jar.
         *    @param integer $interval     The old session is moved
         *                                 into the past by this number
         *                                 of seconds. Cookies now over
         *                                 age will be removed.
         *    @access public
         */
        function agePrematurely($interval) {
            for ($i = 0; $i < count($this->_cookies); $i++) {
                $this->_cookies[$i]->agePrematurely($interval);
            }
        }
        
        /**
         *    Adds a cookie to the jar. This will overwrite
         *    cookies with matching host, paths and keys.
         *    @param SimpleCookie $cookie        New cookie.
         *    @access public
         */
        function setCookie($cookie) {
            for ($i = 0; $i < count($this->_cookies); $i++) {
                $is_match = $this->_isMatch(
                        $cookie,
                        $this->_cookies[$i]->getHost(),
                        $this->_cookies[$i]->getPath(),
                        $this->_cookies[$i]->getName());
                if ($is_match) {
                    $this->_cookies[$i] = $cookie;
                    return;
                }
            }
            $this->_cookies[] = $cookie;
        }
        
        /**
         *    Fetches a hash of all valid cookies filtered
         *    by host, path and keyed by name
         *    Any cookies with missing categories will not
         *    be filtered out by that category. Expired
         *    cookies must be cleared by restarting the session.
         *    @param string $host   Host name requirement.
         *    @param string $path   Path encompassing cookies.
         *    @return hash          Valid cookie objects keyed
         *                          on the cookie name.
         *    @access public
         */
        function getValidCookies($host = false, $path = "/") {
            $valid_cookies = array();
            foreach ($this->_cookies as $cookie) {
                if ($this->_isMatch($cookie, $host, $path, $cookie->getName())) {
                    $valid_cookies[] = $cookie;
                }
            }
            return $valid_cookies;
        }
        
        /**
         *    Tests cookie for matching against search
         *    criteria.
         *    @param SimpleTest $cookie    Cookie to test.
         *    @param string $host          Host must match.
         *    @param string $path          Cookie path must be shorter than
         *                                 this path.
         *    @param string $name          Name must match.
         *    @return boolean              True if matched.
         *    @access private
         */
        function _isMatch($cookie, $host, $path, $name) {
            if ($cookie->getName() != $name) {
                return false;
            }
            if ($host && $cookie->getHost() && !$cookie->isValidHost($host)) {
                return false;
            }
            if (! $cookie->isValidPath($path)) {
                return false;
            }
            return true;
        }
        
        /**
         *    Adds the current cookies to a request.
         *    @param SimpleHttpRequest $request    Request to modify.
         *    @param SimpleUrl $url                Cookie selector.
         *    @access private
         */
        function addHeaders(&$request, $url) {
            $cookies = $this->getValidCookies($url->getHost(), $url->getPath());
            foreach ($cookies as $cookie) {
                $request->setCookie($cookie);
            }
        }
    }

    /**
     *    Fetches web pages whilst keeping track of
     *    cookies and authentication.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleUserAgent {
        var $_cookie_jar;
        var $_authenticator;
        var $_max_redirects;
        var $_proxy;
        var $_proxy_username;
        var $_proxy_password;
        var $_connection_timeout;
        var $_additional_headers;
        
        /**
         *    Starts with no cookies, realms or proxies.
         *    @access public
         */
        function SimpleUserAgent() {
            $this->_cookie_jar = &new SimpleCookieJar();
            $this->_authenticator = &new SimpleAuthenticator();
            $this->setMaximumRedirects(DEFAULT_MAX_REDIRECTS);
            $this->_proxy = false;
            $this->_proxy_username = false;
            $this->_proxy_password = false;
            $this->setConnectionTimeout(DEFAULT_CONNECTION_TIMEOUT);
            $this->_additional_headers = array();
        }
        
        /**
         *    Removes expired and temporary cookies as if
         *    the browser was closed and re-opened. Authorisation
         *    has to be obtained again as well.
         *    @param string/integer $date   Time when session restarted.
         *                                  If omitted then all persistent
         *                                  cookies are kept.
         *    @access public
         */
        function restart($date = false) {
            $this->_cookie_jar->restartSession($date);
            $this->_authenticator->restartSession();
        }
        
        /**
         *    Adds a header to every fetch.
         *    @param string $header       Header line to add to every
         *                                request until cleared.
         *    @access public
         */
        function addHeader($header) {
            $this->_additional_headers[] = $header;
        }
        
        /**
         *    Ages the cookies by the specified time.
         *    @param integer $interval    Amount in seconds.
         *    @access public
         */
        function ageCookies($interval) {
            $this->_cookie_jar->agePrematurely($interval);
        }
        
        /**
         *    Sets an additional cookie. If a cookie has
         *    the same name and path it is replaced.
         *    @param string $name            Cookie key.
         *    @param string $value           Value of cookie.
         *    @param string $host            Host upon which the cookie is valid.
         *    @param string $path            Cookie path if not host wide.
         *    @param string $expiry          Expiry date.
         *    @access public
         */
        function setCookie($name, $value, $host = false, $path = '/', $expiry = false) {
            $cookie = new SimpleCookie($name, $value, $path, $expiry);
            if ($host) {
                $cookie->setHost($host);
            }
            $this->_cookie_jar->setCookie($cookie);
        }
        
        /**
         *    Reads the most specific cookie value from the
         *    browser cookies.
         *    @param string $host        Host to search.
         *    @param string $path        Applicable path.
         *    @param string $name        Name of cookie to read.
         *    @return string             False if not present, else the
         *                               value as a string.
         *    @access public
         */
        function getCookieValue($host, $path, $name) {
            $longest_path = '';
            foreach ($this->_cookie_jar->getValidCookies($host, $path) as $cookie) {
                if ($name == $cookie->getName()) {
                    if (strlen($cookie->getPath()) > strlen($longest_path)) {
                        $value = $cookie->getValue();
                        $longest_path = $cookie->getPath();
                    }
                }
            }
            return (isset($value) ? $value : false);
        }
        
        /**
         *    Reads the current cookies within the base URL.
         *    @param string $name     Key of cookie to find.
         *    @param SimpleUrl $base  Base URL to search from.
         *    @return string          Null if there is no base URL, false
         *                            if the cookie is not set.
         *    @access public
         */
        function getBaseCookieValue($name, $base) {
            if (! $base) {
                return null;
            }
            return $this->getCookieValue($base->getHost(), $base->getPath(), $name);
        }
        
        /**
         *    Sets the socket timeout for opening a connection.
         *    @param integer $timeout      Maximum time in seconds.
         *    @access public
         */
        function setConnectionTimeout($timeout) {
            $this->_connection_timeout = $timeout;
        }
        
        /**
         *    Sets the maximum number of redirects before
         *    a page will be loaded anyway.
         *    @param integer $max        Most hops allowed.
         *    @access public
         */
        function setMaximumRedirects($max) {
            $this->_max_redirects = $max;
        }
        
        /**
         *    Sets proxy to use on all requests for when
         *    testing from behind a firewall. Set URL
         *    to false to disable.
         *    @param string $proxy        Proxy URL.
         *    @param string $username     Proxy username for authentication.
         *    @param string $password     Proxy password for authentication.
         *    @access public
         */
        function useProxy($proxy, $username, $password) {
            if (! $proxy) {
                $this->_proxy = false;
                return;
            }
            if (strncmp($proxy, 'http://', 7) != 0) {
                $proxy = 'http://'. $proxy;
            }
            $this->_proxy = &new SimpleUrl($proxy);
            $this->_proxy_username = $username;
            $this->_proxy_password = $password;
        }
        
        /**
         *    Test to see if the redirect limit is passed.
         *    @param integer $redirects        Count so far.
         *    @return boolean                  True if over.
         *    @access private
         */
        function _isTooManyRedirects($redirects) {
            return ($redirects > $this->_max_redirects);
        }
        
        /**
         *    Sets the identity for the current realm.
         *    @param string $host        Host to which realm applies.
         *    @param string $realm       Full name of realm.
         *    @param string $username    Username for realm.
         *    @param string $password    Password for realm.
         *    @access public
         */
        function setIdentity($host, $realm, $username, $password) {
            $this->_authenticator->setIdentityForRealm($host, $realm, $username, $password);
        }
        
        /**
         *    Fetches a URL as a response object. Will keep trying if redirected.
         *    It will also collect authentication realm information.
         *    @param string $method                   GET, POST, etc.
         *    @param string/SimpleUrl $url            Target to fetch.
         *    @param SimpleFormEncoding $parameters   Additional parameters for request.
         *    @return SimpleHttpResponse              Hopefully the target page.
         *    @access public
         */
        function &fetchResponse($method, $url, $parameters = false) {
            if ($method != 'POST') {
                $url->addRequestParameters($parameters);
                $parameters = false;
            }
            $response = &$this->_fetchWhileRedirected($method, $url, $parameters);
            if ($headers = $response->getHeaders()) {
                if ($headers->isChallenge()) {
                    $this->_authenticator->addRealm(
                            $url,
                            $headers->getAuthentication(),
                            $headers->getRealm());
                }
            }
            return $response;
        }
        
        /**
         *    Fetches the page until no longer redirected or
         *    until the redirect limit runs out.
         *    @param string $method                  GET, POST, etc.
         *    @param SimpleUrl $url                  Target to fetch.
         *    @param SimpelFormEncoding $parameters  Additional parameters for request.
         *    @return SimpleHttpResponse             Hopefully the target page.
         *    @access private
         */
        function &_fetchWhileRedirected($method, $url, $parameters) {
            $redirects = 0;
            do {
                $response = &$this->_fetch($method, $url, $parameters);
                if ($response->isError()) {
                    return $response;
                }
                $headers = $response->getHeaders();
                $location = new SimpleUrl($headers->getLocation());
                $url = $location->makeAbsolute($url);
                $this->_addCookiesToJar($url, $headers->getNewCookies());
                if (! $headers->isRedirect()) {
                    break;
                }
                $method = 'GET';
                $parameters = false;
            } while (! $this->_isTooManyRedirects(++$redirects));
            return $response;
        }
        
        /**
         *    Actually make the web request.
         *    @param string $method                   GET, POST, etc.
         *    @param SimpleUrl $url                   Target to fetch.
         *    @param SimpleFormEncoding $parameters   Additional parameters for request.
         *    @return SimpleHttpResponse              Headers and hopefully content.
         *    @access protected
         */
        function &_fetch($method, $url, $parameters) {
            if (! $parameters) {
                $parameters = new SimpleFormEncoding();
            }
            $request = &$this->_createRequest($method, $url, $parameters);
            return $request->fetch($this->_connection_timeout);
        }
        
        /**
         *    Creates a full page request.
         *    @param string $method                   Fetching method.
         *    @param SimpleUrl $url                   Target to fetch as url object.
         *    @param SimpleFormEncoding $parameters   POST/GET parameters.
         *    @return SimpleHttpRequest               New request.
         *    @access private
         */
        function &_createRequest($method, $url, $parameters) {
            $request = &$this->_createHttpRequest($method, $url, $parameters);
            $this->_addAdditionalHeaders($request);
            $this->_cookie_jar->addHeaders($request, $url);
            $this->_authenticator->addHeaders($request, $url);
            return $request;
        }
        
        /**
         *    Builds the appropriate HTTP request object.
         *    @param string $method                  Fetching method.
         *    @param SimpleUrl $url                  Target to fetch as url object.
         *    @param SimpleFormEncoding $parameters  POST/GET parameters.
         *    @return SimpleHttpRequest              New request object.
         *    @access protected
         */
        function &_createHttpRequest($method, $url, $parameters) {
            if ($method == 'POST') {
                $request = &new SimpleHttpRequest(
                        $this->_createRoute($url),
                        'POST',
                        $parameters);
                return $request;
            }
            if ($parameters) {
                $url->addRequestParameters($parameters);
            }
            return new SimpleHttpRequest($this->_createRoute($url), $method);
        }
        
        /**
         *    Sets up either a direct route or via a proxy.
         *    @param SimpleUrl $url   Target to fetch as url object.
         *    @return SimpleRoute     Route to take to fetch URL.
         *    @access protected
         */
        function &_createRoute($url) {
            if ($this->_proxy) {
                return new SimpleProxyRoute(
                        $url,
                        $this->_proxy,
                        $this->_proxy_username,
                        $this->_proxy_password);
            }
            return new SimpleRoute($url);
        }
        
        /**
         *    Adds additional manual headers.
         *    @param SimpleHttpRequest $request    Outgoing request.
         *    @access private
         */
        function _addAdditionalHeaders(&$request) {
            foreach ($this->_additional_headers as $header) {
                $request->addHeaderLine($header);
            }
        }
        
        /**
         *    Extracts new cookies into the cookie jar.
         *    @param SimpleUrl $url     Target to fetch as url object.
         *    @param array $cookies     New cookies.
         *    @access private
         */
        function _addCookiesToJar($url, $cookies) {
            foreach ($cookies as $cookie) {
                if ($url->getHost()) {
                    $cookie->setHost($url->getHost());
                }
                $this->_cookie_jar->setCookie($cookie);
            }
        }
    }
?>
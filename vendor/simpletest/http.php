<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	WebTester
     *	@version	$Id: http.php,v 1.98 2005/02/02 23:25:23 lastcraft Exp $
     */

    /**#@+
     *	include other SimpleTest class files
     */
    require_once(dirname(__FILE__) . '/socket.php');
    require_once(dirname(__FILE__) . '/url.php');
    /**#@-*/
    
    /**
     *    Cookie data holder. Cookie rules are full of pretty
     *    arbitary stuff. I have used...
     *    http://wp.netscape.com/newsref/std/cookie_spec.html
     *    http://www.cookiecentral.com/faq/
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleCookie {
        var $_host;
        var $_name;
        var $_value;
        var $_path;
        var $_expiry;
        var $_is_secure;
        
        /**
         *    Constructor. Sets the stored values.
         *    @param string $name            Cookie key.
         *    @param string $value           Value of cookie.
         *    @param string $path            Cookie path if not host wide.
         *    @param string $expiry          Expiry date as string.
         *    @param boolean $is_secure      Currently ignored.
         */
        function SimpleCookie($name, $value = false, $path = false, $expiry = false, $is_secure = false) {
            $this->_host = false;
            $this->_name = $name;
            $this->_value = $value;
            $this->_path = ($path ? $this->_fixPath($path) : "/");
            $this->_expiry = false;
            if (is_string($expiry)) {
                $this->_expiry = strtotime($expiry);
            } elseif (is_integer($expiry)) {
                $this->_expiry = $expiry;
            }
            $this->_is_secure = $is_secure;
        }
        
        /**
         *    Sets the host. The cookie rules determine
         *    that the first two parts are taken for
         *    certain TLDs and three for others. If the
         *    new host does not match these rules then the
         *    call will fail.
         *    @param string $host       New hostname.
         *    @return boolean           True if hostname is valid.
         *    @access public
         */
        function setHost($host) {
            if ($host = $this->_truncateHost($host)) {
                $this->_host = $host;
                return true;
            }
            return false;
        }
        
        /**
         *    Accessor for the truncated host to which this
         *    cookie applies.
         *    @return string       Truncated hostname.
         *    @access public
         */
        function getHost() {
            return $this->_host;
        }
        
        /**
         *    Test for a cookie being valid for a host name.
         *    @param string $host    Host to test against.
         *    @return boolean        True if the cookie would be valid
         *                           here.
         */
        function isValidHost($host) {
            return ($this->_truncateHost($host) === $this->getHost());
        }
        
        /**
         *    Extracts just the domain part that determines a
         *    cookie's host validity.
         *    @param string $host    Host name to truncate.
         *    @return string        Domain or false on a bad host.
         *    @access private
         */
        function _truncateHost($host) {
            $tlds = SimpleUrl::getAllTopLevelDomains();
            if (preg_match('/[a-z\-]+\.(' . $tlds . ')$/i', $host, $matches)) {
                return $matches[0];
            } elseif (preg_match('/[a-z\-]+\.[a-z\-]+\.[a-z\-]+$/i', $host, $matches)) {
                return $matches[0];
            }
            return false;
        }
        
        /**
         *    Accessor for name.
         *    @return string       Cookie key.
         *    @access public
         */
        function getName() {
            return $this->_name;
        }
        
        /**
         *    Accessor for value. A deleted cookie will
         *    have an empty string for this.
         *    @return string       Cookie value.
         *    @access public
         */
        function getValue() {
            return $this->_value;
        }
        
        /**
         *    Accessor for path.
         *    @return string       Valid cookie path.
         *    @access public
         */
        function getPath() {
            return $this->_path;
        }
        
        /**
         *    Tests a path to see if the cookie applies
         *    there. The test path must be longer or
         *    equal to the cookie path.
         *    @param string $path       Path to test against.
         *    @return boolean           True if cookie valid here.
         *    @access public
         */
        function isValidPath($path) {
            return (strncmp(
                    $this->_fixPath($path),
                    $this->getPath(),
                    strlen($this->getPath())) == 0);
        }
        
        /**
         *    Accessor for expiry.
         *    @return string       Expiry string.
         *    @access public
         */
        function getExpiry() {
            if (! $this->_expiry) {
                return false;
            }
            return gmdate("D, d M Y H:i:s", $this->_expiry) . " GMT";
        }
        
        /**
         *    Test to see if cookie is expired against
         *    the cookie format time or timestamp.
         *    Will give true for a session cookie.
         *    @param integer/string $now  Time to test against. Result
         *                                will be false if this time
         *                                is later than the cookie expiry.
         *                                Can be either a timestamp integer
         *                                or a cookie format date.
         *    @access public
         */
        function isExpired($now) {
            if (! $this->_expiry) {
                return true;
            }
            if (is_string($now)) {
                $now = strtotime($now);
            }
            return ($this->_expiry < $now);
        }
        
        /**
         *    Ages the cookie by the specified number of
         *    seconds.
         *    @param integer $interval   In seconds.
         *    @public
         */
        function agePrematurely($interval) {
            if ($this->_expiry) {
                $this->_expiry -= $interval;
            }
        }
        
        /**
         *    Accessor for the secure flag.
         *    @return boolean       True if cookie needs SSL.
         *    @access public
         */
        function isSecure() {
            return $this->_is_secure;
        }
        
        /**
         *    Adds a trailing and leading slash to the path
         *    if missing.
         *    @param string $path            Path to fix.
         *    @access private
         */
        function _fixPath($path) {
            if (substr($path, 0, 1) != '/') {
                $path = '/' . $path;
            }
            if (substr($path, -1, 1) != '/') {
                $path .= '/';
            }
            return $path;
        }
    }
    
    /**
     *    Creates HTTP headers for the end point of
     *    a HTTP request.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleRoute {
        var $_url;
        
        /**
         *    Sets the target URL.
         *    @param SimpleUrl $url   URL as object.
         *    @access public
         */
        function SimpleRoute($url) {
            $this->_url = $url;
        }
        
        /**
         *    Resource name.
         *    @return SimpleUrl        Current url.
         *    @access protected
         */
        function getUrl() {
            return $this->_url;
        }
        
        /**
         *    Creates the first line which is the actual request.
         *    @param string $method   HTTP request method, usually GET.
         *    @return string          Request line content.
         *    @access protected
         */
        function _getRequestLine($method) {
            return $method . ' ' . $this->_url->getPath() .
                    $this->_url->getEncodedRequest() . ' HTTP/1.0';
        }
        
        /**
         *    Creates the host part of the request.
         *    @return string          Host line content.
         *    @access protected
         */
        function _getHostLine() {
            $line = 'Host: ' . $this->_url->getHost();
            if ($this->_url->getPort()) {
                $line .= ':' . $this->_url->getPort();
            }
            return $line;
        }
        
        /**
         *    Opens a socket to the route.
         *    @param string $method      HTTP request method, usually GET.
         *    @param integer $timeout    Connection timeout.
         *    @return SimpleSocket       New socket.
         *    @access public
         */
        function &createConnection($method, $timeout) {
            $default_port = ('https' == $this->_url->getScheme()) ? 443 : 80;
            $socket = &$this->_createSocket(
                    $this->_url->getScheme() ? $this->_url->getScheme() : 'http',
                    $this->_url->getHost(),
                    $this->_url->getPort() ? $this->_url->getPort() : $default_port,
                    $timeout);
            if (! $socket->isError()) {
                $socket->write($this->_getRequestLine($method) . "\r\n");
                $socket->write($this->_getHostLine() . "\r\n");
                $socket->write("Connection: close\r\n");
            }
            return $socket;
        }
        
        /**
         *    Factory for socket.
         *    @param string $scheme                   Protocol to use.
         *    @param string $host                     Hostname to connect to.
         *    @param integer $port                    Remote port.
         *    @param integer $timeout                 Connection timeout.
         *    @return SimpleSocket/SimpleSecureSocket New socket.
         *    @access protected
         */
        function &_createSocket($scheme, $host, $port, $timeout) {
            if (in_array($scheme, array('https'))) {
                return new SimpleSecureSocket($host, $port, $timeout);
            }
            return new SimpleSocket($host, $port, $timeout);
        }
    }
    
    /**
     *    Creates HTTP headers for the end point of
     *    a HTTP request via a proxy server.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleProxyRoute extends SimpleRoute {
        var $_proxy;
        var $_username;
        var $_password;
        
        /**
         *    Stashes the proxy address.
         *    @param SimpleUrl $url     URL as object.
         *    @param string $proxy      Proxy URL.
         *    @param string $username   Username for autentication.
         *    @param string $password   Password for autentication.
         *    @access public
         */
        function SimpleProxyRoute($url, $proxy, $username = false, $password = false) {
            $this->SimpleRoute($url);
            $this->_proxy = $proxy;
            $this->_username = $username;
            $this->_password = $password;
        }
        
        /**
         *    Creates the first line which is the actual request.
         *    @param string $method   HTTP request method, usually GET.
         *    @param SimpleUrl $url   URL as object.
         *    @return string          Request line content.
         *    @access protected
         */
        function _getRequestLine($method) {
            $url = $this->getUrl();
            $scheme = $url->getScheme() ? $url->getScheme() : 'http';
            $port = $url->getPort() ? ':' . $url->getPort() : '';
            return $method . ' ' . $scheme . '://' . $url->getHost() . $port .
                    $url->getPath() . $url->getEncodedRequest() . ' HTTP/1.0';
        }
        
        /**
         *    Creates the host part of the request.
         *    @param SimpleUrl $url   URL as object.
         *    @return string          Host line content.
         *    @access protected
         */
        function _getHostLine() {
            $host = 'Host: ' . $this->_proxy->getHost();
            $port = $this->_proxy->getPort() ? $this->_proxy->getPort() : 8080;
            return "$host:$port";
        }
        
        /**
         *    Opens a socket to the route.
         *    @param string $method       HTTP request method, usually GET.
         *    @param integer $timeout     Connection timeout.
         *    @return SimpleSocket        New socket.
         *    @access public
         */
        function &createConnection($method, $timeout) {
            $socket = &$this->_createSocket(
                    $this->_proxy->getScheme() ? $this->_proxy->getScheme() : 'http',
                    $this->_proxy->getHost(),
                    $this->_proxy->getPort() ? $this->_proxy->getPort() : 8080,
                    $timeout);
            if (! $socket->isError()) {
                $socket->write($this->_getRequestLine($method) . "\r\n");
                $socket->write($this->_getHostLine() . "\r\n");
                if ($this->_username && $this->_password) {
                    $socket->write('Proxy-Authorization: Basic ' .
                            base64_encode($this->_username . ':' . $this->_password) .
                            "\r\n");
                }
                $socket->write("Connection: close\r\n");
            }
            return $socket;
        }
    }

    /**
     *    HTTP request for a web page. Factory for
     *    HttpResponse object.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleHttpRequest {
        var $_route;
        var $_method;
        var $_encoding;
        var $_headers;
        var $_cookies;
        
        /**
         *    Saves the URL ready for fetching.
         *    @param SimpleRoute $route   Request route.
         *    @param string $method                  HTTP request method,
         *                                           usually GET.
         *    @param SimpleFormEncoding $encoding    Content to send with
         *                                           request or false.
         *    @access public
         */
        function SimpleHttpRequest(&$route, $method, $encoding = false) {
            $this->_route = &$route;
            $this->_method = $method;
            $this->_encoding = $encoding;
            $this->_headers = array();
            $this->_cookies = array();
        }
        
        /**
         *    Fetches the content and parses the headers.
         *    @param integer $timeout      Connection timeout.
         *    @return SimpleHttpResponse   A response which may only have
         *                                 an error.
         *    @access public
         */
        function &fetch($timeout) {
            $socket = &$this->_route->createConnection($this->_method, $timeout);
            if ($socket->isError()) {
                return $this->_createResponse($socket);
            }
            $this->_dispatchRequest($socket, $this->_method, $this->_encoding);
            return $this->_createResponse($socket);
        }
        
        /**
         *    Sends the headers.
         *    @param SimpleSocket $socket           Open socket.
         *    @param string $method                 HTTP request method,
         *                                          usually GET.
         *    @param SimpleFormEncoding $encoding   Content to send with request.
         *    @access private
         */
        function _dispatchRequest(&$socket, $method, $encoding) {
            if ($encoding || ($method == 'POST')) {
                $socket->write("Content-Length: " . $this->_getContentLength($encoding) . "\r\n");
                $socket->write("Content-Type: application/x-www-form-urlencoded\r\n");
            }
            foreach ($this->_headers as $header_line) {
                $socket->write($header_line . "\r\n");
            }
            if (count($this->_cookies) > 0) {
                $socket->write("Cookie: " . $this->_marshallCookies($this->_cookies) . "\r\n");
            }
            $socket->write("\r\n");
            if ($encoding) {
                $socket->write($encoding->asString());
            }
        }
        
        /**
         *    Calculates the length of the encoded content.
         *    @param SimpleFormEncoding $encoding   Content to send with
         *                                          request or false.
         */
        function _getContentLength($encoding) {
            if (! $encoding) {
                return 0;
            }
            return (integer)strlen($encoding->asString());
        }
        
        /**
         *    Adds a header line to the request.
         *    @param string $header_line    Text of header line.
         *    @access public
         */
        function addHeaderLine($header_line) {
            $this->_headers[] = $header_line;
        }
        
        /**
         *    Adds a cookie to the request.
         *    @param SimpleCookie $cookie   Additional cookie.
         *    @access public
         */
        function setCookie($cookie) {
            $this->_cookies[] = $cookie;
        }
        
        /**
         *    Serialises the cookie hash ready for
         *    transmission.
         *    @param hash $cookies     Parsed cookies.
         *    @return array            Cookies in header form.
         *    @access private
         */
        function _marshallCookies($cookies) {
            $cookie_pairs = array();
            foreach ($cookies as $cookie) {
                $cookie_pairs[] = $cookie->getName() . "=" . $cookie->getValue();
            }
            return implode(";", $cookie_pairs);
        }
        
        /**
         *    Wraps the socket in a response parser.
         *    @param SimpleSocket $socket   Responding socket.
         *    @return SimpleHttpResponse    Parsed response object.
         *    @access protected
         */
        function &_createResponse(&$socket) {
            return new SimpleHttpResponse(
                    $socket,
                    $this->_method,
                    $this->_route->getUrl(),
                    $this->_encoding);
        }
    }
    
    /**
     *    Collection of header lines in the response.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleHttpHeaders {
        var $_raw_headers;
        var $_response_code;
        var $_http_version;
        var $_mime_type;
        var $_location;
        var $_cookies;
        var $_authentication;
        var $_realm;
        
        /**
         *    Parses the incoming header block.
         *    @param string $headers     Header block.
         *    @access public
         */
        function SimpleHttpHeaders($headers) {
            $this->_raw_headers = $headers;
            $this->_response_code = false;
            $this->_http_version = false;
            $this->_mime_type = '';
            $this->_location = false;
            $this->_cookies = array();
            $this->_authentication = false;
            $this->_realm = false;
            foreach (split("\r\n", $headers) as $header_line) {
                $this->_parseHeaderLine($header_line);
            }
        }
        
        /**
         *    Accessor for parsed HTTP protocol version.
         *    @return integer           HTTP error code.
         *    @access public
         */
        function getHttpVersion() {
            return $this->_http_version;
        }
        
        /**
         *    Accessor for raw header block.
         *    @return string        All headers as raw string.
         *    @access public
         */
        function getRaw() {
            return $this->_raw_headers;
        }
        
        /**
         *    Accessor for parsed HTTP error code.
         *    @return integer           HTTP error code.
         *    @access public
         */
        function getResponseCode() {
            return (integer)$this->_response_code;
        }
        
        /**
         *    Returns the redirected URL or false if
         *    no redirection.
         *    @return string      URL or false for none.
         *    @access public
         */
        function getLocation() {
            return $this->_location;
        }
        
        /**
         *    Test to see if the response is a valid redirect.
         *    @return boolean       True if valid redirect.
         *    @access public
         */
        function isRedirect() {
            return in_array($this->_response_code, array(301, 302, 303, 307)) &&
                    (boolean)$this->getLocation();
        }
        
        /**
         *    Test to see if the response is an authentication
         *    challenge.
         *    @return boolean       True if challenge.
         *    @access public
         */
        function isChallenge() {
            return ($this->_response_code == 401) &&
                    (boolean)$this->_authentication &&
                    (boolean)$this->_realm;
        }
        
        /**
         *    Accessor for MIME type header information.
         *    @return string           MIME type.
         *    @access public
         */
        function getMimeType() {
            return $this->_mime_type;
        }
        
        /**
         *    Accessor for authentication type.
         *    @return string        Type.
         *    @access public
         */
        function getAuthentication() {
            return $this->_authentication;
        }
        
        /**
         *    Accessor for security realm.
         *    @return string        Realm.
         *    @access public
         */
        function getRealm() {
            return $this->_realm;
        }
        
        /**
         *    Accessor for any new cookies.
         *    @return array       List of new cookies.
         *    @access public
         */
        function getNewCookies() {
            return $this->_cookies;
        }

        /**
         *    Called on each header line to accumulate the held
         *    data within the class.
         *    @param string $header_line        One line of header.
         *    @access protected
         */
        function _parseHeaderLine($header_line) {
            if (preg_match('/HTTP\/(\d+\.\d+)\s+(.*?)\s/i', $header_line, $matches)) {
                $this->_http_version = $matches[1];
                $this->_response_code = $matches[2];
            }
            if (preg_match('/Content-type:\s*(.*)/i', $header_line, $matches)) {
                $this->_mime_type = trim($matches[1]);
            }
            if (preg_match('/Location:\s*(.*)/i', $header_line, $matches)) {
                $this->_location = trim($matches[1]);
            }
            if (preg_match('/Set-cookie:(.*)/i', $header_line, $matches)) {
                $this->_cookies[] = $this->_parseCookie($matches[1]);
            }
            if (preg_match('/WWW-Authenticate:\s+(\S+)\s+realm=\"(.*?)\"/i', $header_line, $matches)) {
                $this->_authentication = $matches[1];
                $this->_realm = trim($matches[2]);
            }
        }
        
        /**
         *    Parse the Set-cookie content.
         *    @param string $cookie_line    Text after "Set-cookie:"
         *    @return SimpleCookie          New cookie object.
         *    @access private
         */
        function _parseCookie($cookie_line) {
            $parts = split(";", $cookie_line);
            $cookie = array();
            preg_match('/\s*(.*?)\s*=(.*)/', array_shift($parts), $cookie);
            foreach ($parts as $part) {
                if (preg_match('/\s*(.*?)\s*=(.*)/', $part, $matches)) {
                    $cookie[$matches[1]] = trim($matches[2]);
                }
            }
            return new SimpleCookie(
                    $cookie[1],
                    trim($cookie[2]),
                    isset($cookie["path"]) ? $cookie["path"] : "",
                    isset($cookie["expires"]) ? $cookie["expires"] : false);
        }
    }
    
    /**
     *    Basic HTTP response.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleHttpResponse extends SimpleStickyError {
        var $_method;
        var $_url;
        var $_request_data;
        var $_sent;
        var $_content;
        var $_headers;
        
        /**
         *    Constructor. Reads and parses the incoming
         *    content and headers.
         *    @param SimpleSocket $socket   Network connection to fetch
         *                                  response text from.
         *    @param string $method         HTTP request method.
         *    @param SimpleUrl $url         Resource name.
         *    @param mixed $request_data    Record of content sent.
         *    @access public
         */
        function SimpleHttpResponse(&$socket, $method, $url, $request_data = '') {
            $this->SimpleStickyError();
            $this->_method = $method;
            $this->_url = $url;
            $this->_request_data = $request_data;
            $this->_sent = $socket->getSent();
            $this->_content = false;
            $raw = $this->_readAll($socket);
            if ($socket->isError()) {
                $this->_setError('Error reading socket [' . $socket->getError() . ']');
                return;
            }
            $this->_parse($raw);
        }
        
        /**
         *    Splits up the headers and the rest of the content.
         *    @param string $raw    Content to parse.
         *    @access private
         */
        function _parse($raw) {
            if (! $raw) {
                $this->_setError('Nothing fetched');
                $this->_headers = &new SimpleHttpHeaders('');
            } elseif (! strstr($raw, "\r\n\r\n")) {
                $this->_setError('Could not parse headers');
                $this->_headers = &new SimpleHttpHeaders($raw);
            } else {
                list($headers, $this->_content) = split("\r\n\r\n", $raw, 2);
                $this->_headers = &new SimpleHttpHeaders($headers);
            }
        }
        
        /**
         *    Original request method.
         *    @return string        GET, POST or HEAD.
         *    @access public
         */
        function getMethod() {
            return $this->_method;
        }
        
        /**
         *    Resource name.
         *    @return SimpleUrl        Current url.
         *    @access public
         */
        function getUrl() {
            return $this->_url;
        }
        
        /**
         *    Original request data.
         *    @return mixed              Sent content.
         *    @access public
         */
        function getRequestData() {
            return $this->_request_data;
        }
        
        /**
         *    Raw request that was sent down the wire.
         *    @return string        Bytes actually sent.
         *    @access public
         */
        function getSent() {
            return $this->_sent;
        }
        
        /**
         *    Accessor for the content after the last
         *    header line.
         *    @return string           All content.
         *    @access public
         */
        function getContent() {
            return $this->_content;
        }
        
        /**
         *    Accessor for header block. The response is the
         *    combination of this and the content.
         *    @return SimpleHeaders        Wrapped header block.
         *    @access public
         */
        function getHeaders() {
            return $this->_headers;
        }
        
        /**
         *    Accessor for any new cookies.
         *    @return array       List of new cookies.
         *    @access public
         */
        function getNewCookies() {
            return $this->_headers->getNewCookies();
        }
        
        /**
         *    Reads the whole of the socket output into a
         *    single string.
         *    @param SimpleSocket $socket  Unread socket.
         *    @return string               Raw output if successful
         *                                 else false.
         *    @access private
         */
        function _readAll(&$socket) {
            $all = '';
            while (! $this->_isLastPacket($next = $socket->read())) {
                $all .= $next;
            }
            return $all;
        }
        
        /**
         *    Test to see if the packet from the socket is the
         *    last one.
         *    @param string $packet    Chunk to interpret.
         *    @return boolean          True if empty or EOF.
         *    @access private
         */
        function _isLastPacket($packet) {
            if (is_string($packet)) {
                return $packet === '';
            }
            return ! $packet;
        }
    }
?>
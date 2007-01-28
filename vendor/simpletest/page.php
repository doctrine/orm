<?php
    /**
     *	Base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	WebTester
     *	@version	$Id: page.php,v 1.105 2005/02/04 05:32:16 lastcraft Exp $
     */

    /**#@+
     *	include other SimpleTest class files
     */
    require_once(dirname(__FILE__) . '/http.php');
    require_once(dirname(__FILE__) . '/parser.php');
    require_once(dirname(__FILE__) . '/tag.php');
    require_once(dirname(__FILE__) . '/form.php');
    /**#@-*/
    
    /**
     *    SAX event handler. Maintains a list of
     *    open tags and dispatches them as they close.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimplePageBuilder extends SimpleSaxListener {
        var $_tags;
        var $_page;
        
        /**
         *    Sets the builder up empty.
         *    @access public
         */
        function SimplePageBuilder() {
            $this->SimpleSaxListener();
        }
        
        /**
         *    Reads the raw content and send events
         *    into the page to be built.
         *    @param $response SimpleHttpResponse  Fetched response.
         *    @return SimplePage                   Newly parsed page.
         *    @access public
         */
        function parse($response) {
            $this->_tags = array();
            $this->_page = &$this->_createPage($response);
            $parser = &$this->_createParser($this);
            $parser->parse($response->getContent());
            $this->_page->acceptPageEnd();
            return $this->_page;
        }
        
        /**
         *    Creates an empty page.
         *    @return SimplePage        New unparsed page.
         *    @access protected
         */
        function &_createPage($response) {
            return new SimplePage($response);
        }
        
        /**
         *    Creates the parser used with the builder.
         *    @param $listener SimpleSaxListener   Target of parser.
         *    @return SimpleSaxParser              Parser to generate
         *                                         events for the builder.
         *    @access protected
         */
        function &_createParser(&$listener) {
            return new SimpleSaxParser($listener);
        }
        
        /**
         *    Make the keys lower case for case insensitive look-ups.
         *    @param hash $map   Hash to convert.
         *    @return hash       Unchanged values, but keys lower case.
         *    @access private
         */
        function _keysToLowerCase($map) {
            $lower = array();
            foreach ($map as $key => $value) {
                $lower[strtolower($key)] = $value;
            }
            return $lower;
        }
        
        /**
         *    Start of element event. Opens a new tag.
         *    @param string $name         Element name.
         *    @param hash $attributes     Attributes without content
         *                                are marked as true.
         *    @return boolean             False on parse error.
         *    @access public
         */
        function startElement($name, $attributes) {
            $tag = &$this->_createTag($name, $this->_keysToLowerCase($attributes));
            if ($name == 'form') {
                $this->_page->acceptFormStart($tag);
                return true;
            }            
            if ($name == 'frameset') {
                $this->_page->acceptFramesetStart($tag);
                return true;
            }            
            if ($name == 'frame') {
                $this->_page->acceptFrame($tag);
                return true;
            }            
            if ($tag->expectEndTag()) {
                $this->_openTag($tag);
                return true;
            }
            $this->_page->acceptTag($tag);
            return true;
        }
        
        /**
         *    End of element event.
         *    @param string $name        Element name.
         *    @return boolean            False on parse error.
         *    @access public
         */
        function endElement($name) {
            if ($name == 'form') {
                $this->_page->acceptFormEnd();
                return true;
            }            
            if ($name == 'frameset') {
                $this->_page->acceptFramesetEnd();
                return true;
            }            
            if (isset($this->_tags[$name]) && (count($this->_tags[$name]) > 0)) {
                $tag = array_pop($this->_tags[$name]);
                $this->_addContentTagToOpenTags($tag);
                $this->_page->acceptTag($tag);
                return true;
            }
            return true;
        }
        
        /**
         *    Unparsed, but relevant data. The data is added
         *    to every open tag.
         *    @param string $text        May include unparsed tags.
         *    @return boolean            False on parse error.
         *    @access public
         */
        function addContent($text) {
            foreach (array_keys($this->_tags) as $name) {
                for ($i = 0, $count = count($this->_tags[$name]); $i < $count; $i++) {
                    $this->_tags[$name][$i]->addContent($text);
                }
            }
            return true;
        }
        
        /**
         *    Parsed relevant data. The parsed tag is added
         *    to every open tag.
         *    @param SimpleTag $tag        May include unparsed tags.
         *    @access private
         */
        function _addContentTagToOpenTags(&$tag) {
            if ($tag->getTagName() != 'option') {
                return;
            }
            foreach (array_keys($this->_tags) as $name) {
                for ($i = 0, $count = count($this->_tags[$name]); $i < $count; $i++) {
                    $this->_tags[$name][$i]->addTag($tag);
                }
            }
        }
        
        /**
         *    Opens a tag for receiving content. Multiple tags
         *    will be receiving input at the same time.
         *    @param SimpleTag $tag        New content tag.
         *    @access private
         */
        function _openTag(&$tag) {
            $name = $tag->getTagName();
            if (! in_array($name, array_keys($this->_tags))) {
                $this->_tags[$name] = array();
            }
            array_push($this->_tags[$name], $tag);
        }
        
        /**
         *    Factory for the tag objects. Creates the
         *    appropriate tag object for the incoming tag name.
         *    @param string $name        HTML tag name.
         *    @param hash $attributes    Element attributes.
         *    @return SimpleTag          Tag object.
         *    @access protected
         */
        function &_createTag($name, $attributes) {
            if ($name == 'a') {
                return new SimpleAnchorTag($attributes);
            } elseif ($name == 'title') {
                return new SimpleTitleTag($attributes);
            } elseif ($name == 'input') {
                return $this->_createInputTag($attributes);
            } elseif ($name == 'button') {
                return new SimpleButtonTag($attributes);
            } elseif ($name == 'textarea') {
                return new SimpleTextAreaTag($attributes);
            } elseif ($name == 'select') {
                return $this->_createSelectionTag($attributes);
            } elseif ($name == 'option') {
                return new SimpleOptionTag($attributes);
            } elseif ($name == 'form') {
                return new SimpleFormTag($attributes);
            } elseif ($name == 'frame') {
                return new SimpleFrameTag($attributes);
            }
            return new SimpleTag($name, $attributes);
        }
        
        /**
         *    Factory for selection fields.
         *    @param hash $attributes    Element attributes.
         *    @return SimpleTag          Tag object.
         *    @access protected
         */
        function &_createSelectionTag($attributes) {
            if (isset($attributes['multiple'])) {
                return new MultipleSelectionTag($attributes);
            }
            return new SimpleSelectionTag($attributes);
        }
        
        /**
         *    Factory for input tags.
         *    @param hash $attributes    Element attributes.
         *    @return SimpleTag          Tag object.
         *    @access protected
         */
        function &_createInputTag($attributes) {
            if (! isset($attributes['type'])) {
                return new SimpleTextTag($attributes);
            }
            $type = strtolower($attributes['type']);
            if ($type == 'submit') {
                return new SimpleSubmitTag($attributes);
            } elseif ($type == 'image') {
                return new SimpleImageSubmitTag($attributes);
            } elseif ($type == 'checkbox') {
                return new SimpleCheckboxTag($attributes);
            } elseif ($type == 'radio') {
                return new SimpleRadioButtonTag($attributes);
            } else {
                return new SimpleTextTag($attributes);
            }
        }
    }
    
    /**
     *    A wrapper for a web page.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimplePage {
        var $_links;
        var $_title;
        var $_open_forms;
        var $_complete_forms;
        var $_frameset;
        var $_frames;
        var $_frameset_nesting_level;
        var $_transport_error;
        var $_raw;
        var $_text;
        var $_sent;
        var $_headers;
        var $_method;
        var $_url;
        var $_request_data;
        
        /**
         *    Parses a page ready to access it's contents.
         *    @param SimpleHttpResponse $response     Result of HTTP fetch.
         *    @access public
         */
        function SimplePage($response = false) {
            $this->_links = array();
            $this->_title = false;
            $this->_open_forms = array();
            $this->_complete_forms = array();
            $this->_frameset = false;
            $this->_frames = array();
            $this->_frameset_nesting_level = 0;
            $this->_text = false;
            if ($response) {
                $this->_extractResponse($response);
            } else {
                $this->_noResponse();
            }
        }
        
        /**
         *    Extracts all of the response information.
         *    @param SimpleHttpResponse $response    Response being parsed.
         *    @access private
         */
        function _extractResponse($response) {
            $this->_transport_error = $response->getError();
            $this->_raw = $response->getContent();
            $this->_sent = $response->getSent();
            $this->_headers = $response->getHeaders();
            $this->_method = $response->getMethod();
            $this->_url = $response->getUrl();
            $this->_request_data = $response->getRequestData();
        }
        
        /**
         *    Sets up a missing response.
         *    @access private
         */
        function _noResponse() {
            $this->_transport_error = 'No page fetched yet';
            $this->_raw = false;
            $this->_sent = false;
            $this->_headers = false;
            $this->_method = 'GET';
            $this->_url = false;
            $this->_request_data = false;
        }
        
        /**
         *    Original request as bytes sent down the wire.
         *    @return mixed              Sent content.
         *    @access public
         */
        function getRequest() {
            return $this->_sent;
        }
        
        /**
         *    Accessor for raw text of page.
         *    @return string        Raw unparsed content.
         *    @access public
         */
        function getRaw() {
            return $this->_raw;
        }
        
        /**
         *    Accessor for plain text of page as a text browser
         *    would see it.
         *    @return string        Plain text of page.
         *    @access public
         */
        function getText() {
            if (! $this->_text) {
                $this->_text = SimpleSaxParser::normalise($this->_raw);
            }
            return $this->_text;
        }
        
        /**
         *    Accessor for raw headers of page.
         *    @return string       Header block as text.
         *    @access public
         */
        function getHeaders() {
            if ($this->_headers) {
                return $this->_headers->getRaw();
            }
            return false;
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
         *    Original resource name.
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
         *    Accessor for last error.
         *    @return string        Error from last response.
         *    @access public
         */
        function getTransportError() {
            return $this->_transport_error;
        }
        
        /**
         *    Accessor for current MIME type.
         *    @return string    MIME type as string; e.g. 'text/html'
         *    @access public
         */
        function getMimeType() {
            if ($this->_headers) {
                return $this->_headers->getMimeType();
            }
            return false;
        }
        
        /**
         *    Accessor for HTTP response code.
         *    @return integer    HTTP response code received.
         *    @access public
         */
        function getResponseCode() {
            if ($this->_headers) {
                return $this->_headers->getResponseCode();
            }
            return false;
        }
        
        /**
         *    Accessor for last Authentication type. Only valid
         *    straight after a challenge (401).
         *    @return string    Description of challenge type.
         *    @access public
         */
        function getAuthentication() {
            if ($this->_headers) {
                return $this->_headers->getAuthentication();
            }
            return false;
        }
        
        /**
         *    Accessor for last Authentication realm. Only valid
         *    straight after a challenge (401).
         *    @return string    Name of security realm.
         *    @access public
         */
        function getRealm() {
            if ($this->_headers) {
                return $this->_headers->getRealm();
            }
            return false;
        }
        
        /**
         *    Accessor for current frame focus. Will be
         *    false as no frames.
         *    @return array    Always empty.
         *    @access public
         */
        function getFrameFocus() {
            return array();
        }
        
        /**
         *    Sets the focus by index. The integer index starts from 1.
         *    @param integer $choice    Chosen frame.
         *    @return boolean           Always false.
         *    @access public
         */
        function setFrameFocusByIndex($choice) {
            return false;
        }
        
        /**
         *    Sets the focus by name. Always fails for a leaf page.
         *    @param string $name    Chosen frame.
         *    @return boolean        False as no frames.
         *    @access public
         */
        function setFrameFocus($name) {
            return false;
        }
        
        /**
         *    Clears the frame focus. Does nothing for a leaf page.
         *    @access public
         */
        function clearFrameFocus() {
        }
        
        /**
         *    Adds a tag to the page.
         *    @param SimpleTag $tag        Tag to accept.
         *    @access public
         */
        function acceptTag(&$tag) {
            if ($tag->getTagName() == "a") {
                $this->_addLink($tag);
            } elseif ($tag->getTagName() == "title") {
                $this->_setTitle($tag);
            } elseif ($this->_isFormElement($tag->getTagName())) {
                for ($i = 0; $i < count($this->_open_forms); $i++) {
                    $this->_open_forms[$i]->addWidget($tag);
                }
            }
        }
        
        /**
         *    Tests to see if a tag is a possible form
         *    element.
         *    @param string $name     HTML element name.
         *    @return boolean         True if form element.
         *    @access private
         */
        function _isFormElement($name) {
            return in_array($name, array('input', 'button', 'textarea', 'select'));
        }
        
        /**
         *    Opens a form. New widgets go here.
         *    @param SimpleFormTag $tag      Tag to accept.
         *    @access public
         */
        function acceptFormStart(&$tag) {
            $this->_open_forms[] = &new SimpleForm($tag, $this->getUrl());
        }
        
        /**
         *    Closes the most recently opened form.
         *    @access public
         */
        function acceptFormEnd() {
            if (count($this->_open_forms)) {
                $this->_complete_forms[] = array_pop($this->_open_forms);
            }
        }
        
        /**
         *    Opens a frameset. A frameset may contain nested
         *    frameset tags.
         *    @param SimpleFramesetTag $tag      Tag to accept.
         *    @access public
         */
        function acceptFramesetStart(&$tag) {
            if (! $this->_isLoadingFrames()) {
                $this->_frameset = &$tag;
            }
            $this->_frameset_nesting_level++;
        }
        
        /**
         *    Closes the most recently opened frameset.
         *    @access public
         */
        function acceptFramesetEnd() {
            if ($this->_isLoadingFrames()) {
                $this->_frameset_nesting_level--;
            }
        }
        
        /**
         *    Takes a single frame tag and stashes it in
         *    the current frame set.
         *    @param SimpleFrameTag $tag      Tag to accept.
         *    @access public
         */
        function acceptFrame(&$tag) {
            if ($this->_isLoadingFrames()) {
                if ($tag->getAttribute('src')) {
                    $this->_frames[] = &$tag;
                }
            }
        }
        
        /**
         *    Test to see if in the middle of reading
         *    a frameset.
         *    @return boolean        True if inframeset.
         *    @access private
         */
        function _isLoadingFrames() {
            if (! $this->_frameset) {
                return false;
            }
            return ($this->_frameset_nesting_level > 0);
        }
        
        /**
         *    Test to see if link is an absolute one.
         *    @param string $url     Url to test.
         *    @return boolean        True if absolute.
         *    @access protected
         */
        function _linkIsAbsolute($url) {
            $parsed = new SimpleUrl($url);
            return (boolean)($parsed->getScheme() && $parsed->getHost());
        }
        
        /**
         *    Adds a link to the page.
         *    @param SimpleAnchorTag $tag      Link to accept.
         *    @access protected
         */
        function _addLink($tag) {
            $this->_links[] = $tag;
        }
        
        /**
         *    Marker for end of complete page. Any work in
         *    progress can now be closed.
         *    @access public
         */
        function acceptPageEnd() {
            while (count($this->_open_forms)) {
                $this->_complete_forms[] = array_pop($this->_open_forms);
            }
        }
        
        /**
         *    Test for the presence of a frameset.
         *    @return boolean        True if frameset.
         *    @access public
         */
        function hasFrames() {
            return (boolean)$this->_frameset;
        }
        
        /**
         *    Accessor for frame name and source URL for every frame that
         *    will need to be loaded. Immediate children only.
         *    @return boolean/array     False if no frameset or
         *                              otherwise a hash of frame URLs.
         *                              The key is either a numerical
         *                              base one index or the name attribute.
         *    @access public
         */
        function getFrameset() {
            if (! $this->_frameset) {
                return false;
            }
            $urls = array();
            for ($i = 0; $i < count($this->_frames); $i++) {
                $name = $this->_frames[$i]->getAttribute('name');
                $url = new SimpleUrl($this->_frames[$i]->getAttribute('src'));
                $urls[$name ? $name : $i + 1] = $url->makeAbsolute($this->getUrl());
            }
            return $urls;
        }
        
        /**
         *    Fetches a list of loaded frames.
         *    @return array/string    Just the URL for a single page.
         *    @access public
         */
        function getFrames() {
            $url = $this->getUrl();
            return $url->asString();
        }
        
        /**
         *    Accessor for a list of all fixed links.
         *    @return array   List of urls with scheme of
         *                    http or https and hostname.
         *    @access public
         */
        function getAbsoluteUrls() {
            $all = array();
            foreach ($this->_links as $link) {
                if ($this->_linkIsAbsolute($link->getHref())) {
                    $all[] = $link->getHref();
                }
            }
            return $all;
        }
        
        /**
         *    Accessor for a list of all relative links.
         *    @return array      List of urls without hostname.
         *    @access public
         */
        function getRelativeUrls() {
            $all = array();
            foreach ($this->_links as $link) {
                if (! $this->_linkIsAbsolute($link->getHref())) {
                    $all[] = $link->getHref();
                }
            }
            return $all;
        }
        
        /**
         *    Accessor for URLs by the link label. Label will match
         *    regardess of whitespace issues and case.
         *    @param string $label    Text of link.
         *    @return array           List of links with that label.
         *    @access public
         */
        function getUrlsByLabel($label) {
            $matches = array();
            foreach ($this->_links as $link) {
                if ($link->getText() == $label) {
                    $matches[] = $this->_getUrlFromLink($link);
                }
            }
            return $matches;
        }
        
        /**
         *    Accessor for a URL by the id attribute.
         *    @param string $id       Id attribute of link.
         *    @return SimpleUrl       URL with that id of false if none.
         *    @access public
         */
        function getUrlById($id) {
            foreach ($this->_links as $link) {
                if ($link->getAttribute('id') === (string)$id) {
                    return $this->_getUrlFromLink($link);
                }
            }
            return false;
        }
        
        /**
         *    Converts a link into a target URL.
         *    @param SimpleAnchor $link    Parsed link.
         *    @return SimpleUrl            URL with frame target if any.
         *    @access private
         */
        function _getUrlFromLink($link) {
            $url = $this->_makeAbsolute($link->getHref());
            if ($link->getAttribute('target')) {
                $url->setTarget($link->getAttribute('target'));
            }
            return $url;
        }
        
        /**
         *    Expands expandomatic URLs into fully qualified
         *    URLs.
         *    @param SimpleUrl $url        Relative URL.
         *    @return SimpleUrl            Absolute URL.
         *    @access protected
         */
        function _makeAbsolute($url) {
            if (! is_object($url)) {
                $url = new SimpleUrl($url);
            }
            return $url->makeAbsolute($this->getUrl());
        }
        
        /**
         *    Sets the title tag contents.
         *    @param SimpleTitleTag $tag    Title of page.
         *    @access protected
         */
        function _setTitle(&$tag) {
            $this->_title = &$tag;
        }
        
        /**
         *    Accessor for parsed title.
         *    @return string     Title or false if no title is present.
         *    @access public
         */
        function getTitle() {
            if ($this->_title) {
                return $this->_title->getText();
            }
            return false;
        }
        
        /**
         *    Finds a held form by button label. Will only
         *    search correctly built forms.
         *    @param string $label       Button label, default 'Submit'.
         *    @return SimpleForm         Form object containing the button.
         *    @access public
         */
        function &getFormBySubmitLabel($label) {
            for ($i = 0; $i < count($this->_complete_forms); $i++) {
                if ($this->_complete_forms[$i]->hasSubmitLabel($label)) {
                    return $this->_complete_forms[$i];
                }
            }
            return null;
        }
        
        /**
         *    Finds a held form by button label. Will only
         *    search correctly built forms.
         *    @param string $name        Button name attribute.
         *    @return SimpleForm         Form object containing the button.
         *    @access public
         */
        function &getFormBySubmitName($name) {
            for ($i = 0; $i < count($this->_complete_forms); $i++) {
                if ($this->_complete_forms[$i]->hasSubmitName($name)) {
                    return $this->_complete_forms[$i];
                }
            }
            return null;
        }
        
        /**
         *    Finds a held form by button id. Will only
         *    search correctly built forms.
         *    @param string $id          Button ID attribute.
         *    @return SimpleForm         Form object containing the button.
         *    @access public
         */
        function &getFormBySubmitId($id) {
            for ($i = 0; $i < count($this->_complete_forms); $i++) {
                if ($this->_complete_forms[$i]->hasSubmitId($id)) {
                    return $this->_complete_forms[$i];
                }
            }
            return null;
        }
        
        /**
         *    Finds a held form by image label. Will only
         *    search correctly built forms.
         *    @param string $label       Usually the alt attribute.
         *    @return SimpleForm         Form object containing the image.
         *    @access public
         */
        function &getFormByImageLabel($label) {
            for ($i = 0; $i < count($this->_complete_forms); $i++) {
                if ($this->_complete_forms[$i]->hasImageLabel($label)) {
                    return $this->_complete_forms[$i];
                }
            }
            return null;
        }
        
        /**
         *    Finds a held form by image button id. Will only
         *    search correctly built forms.
         *    @param string $name        Image name.
         *    @return SimpleForm         Form object containing the image.
         *    @access public
         */
        function &getFormByImageName($name) {
            for ($i = 0; $i < count($this->_complete_forms); $i++) {
                if ($this->_complete_forms[$i]->hasImageName($name)) {
                    return $this->_complete_forms[$i];
                }
            }
            return null;
        }
        
        /**
         *    Finds a held form by image button id. Will only
         *    search correctly built forms.
         *    @param string $id          Image ID attribute.
         *    @return SimpleForm         Form object containing the image.
         *    @access public
         */
        function &getFormByImageId($id) {
            for ($i = 0; $i < count($this->_complete_forms); $i++) {
                if ($this->_complete_forms[$i]->hasImageId($id)) {
                    return $this->_complete_forms[$i];
                }
            }
            return null;
        }
        
        /**
         *    Finds a held form by the form ID. A way of
         *    identifying a specific form when we have control
         *    of the HTML code.
         *    @param string $id     Form label.
         *    @return SimpleForm    Form object containing the matching ID.
         *    @access public
         */
        function &getFormById($id) {
            for ($i = 0; $i < count($this->_complete_forms); $i++) {
                if ($this->_complete_forms[$i]->getId() == $id) {
                    return $this->_complete_forms[$i];
                }
            }
            return null;
        }
        
        /**
         *    Sets a field on each form in which the field is
         *    available.
         *    @param string $name        Field name.
         *    @param string $value       Value to set field to.
         *    @return boolean            True if value is valid.
         *    @access public
         */
        function setField($name, $value) {
            $is_set = false;
            for ($i = 0; $i < count($this->_complete_forms); $i++) {
                if ($this->_complete_forms[$i]->setField($name, $value)) {
                    $is_set = true;
                }
            }
            return $is_set;
        }
         
        /**
         *    Sets a field on the form in which the unique field is
         *    available.
         *    @param string/integer $id  Field ID attribute.
         *    @param string $value       Value to set field to.
         *    @return boolean            True if value is valid.
         *    @access public
         */
        function setFieldById($id, $value) {
            for ($i = 0; $i < count($this->_complete_forms); $i++) {
                if ($this->_complete_forms[$i]->setFieldById($id, $value)) {
                    return true;
                }
            }
            return false;
        }
       
        /**
         *    Accessor for a form element value within a page.
         *    Finds the first match.
         *    @param string $name        Field name.
         *    @return string/boolean     A string if the field is
         *                               present, false if unchecked
         *                               and null if missing.
         *    @access public
         */
        function getField($name) {
            for ($i = 0; $i < count($this->_complete_forms); $i++) {
                $value = $this->_complete_forms[$i]->getValue($name);
                if (isset($value)) {
                    return $value;
                }
            }
            return null;
        }
         
        /**
         *    Accessor for a form element value within a page.
         *    Finds the first match.
         *    @param string/integer $id  Field ID attribute.
         *    @return string/boolean     A string if the field is
         *                               present, false if unchecked
         *                               and null if missing.
         *    @access public
         */
        function getFieldById($id) {
            for ($i = 0; $i < count($this->_complete_forms); $i++) {
                $value = $this->_complete_forms[$i]->getValueById($id);
                if (isset($value)) {
                    return $value;
                }
            }
            return null;
        }
    }
?>

<?php
    /**
     *	base include file for SimpleTest
     *	@package	SimpleTest
     *	@subpackage	WebTester
     *	@version	$Id: encoding.php,v 1.6 2005/01/02 23:43:23 lastcraft Exp $
     */

    /**
     *    Bundle of GET/POST parameters. Can include
     *    repeated parameters.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleFormEncoding {
        var $_request;
        var $_x;
        var $_y;
        
        /**
         *    Starts empty.
         *    @param array $query/SimpleQueryString  Hash of parameters.
         *                                           Multiple values are
         *                                           as lists on a single key.
         *    @access public
         */
        function SimpleFormEncoding($query = false) {
            if (! $query) {
                $query = array();
            }
            $this->_request = array();
            $this->setCoordinates();
            $this->merge($query);
        }
        
        /**
         *    Adds a parameter to the query.
         *    @param string $key            Key to add value to.
         *    @param string/array $value    New data.
         *    @access public
         */
        function add($key, $value) {
            if ($value === false) {
                return;
            }
            if (! isset($this->_request[$key])) {
                $this->_request[$key] = array();
            }
            if (is_array($value)) {
                foreach ($value as $item) {
                    $this->_request[$key][] = $item;
                }
            } else {
                $this->_request[$key][] = $value;
            }
        }
        
        /**
         *    Adds a set of parameters to this query.
         *    @param array/SimpleQueryString $query  Multiple values are
         *                                           as lists on a single key.
         *    @access public
         */
        function merge($query) {
            if (is_object($query)) {
                foreach ($query->getKeys() as $key) {
                    $this->add($key, $query->getValue($key));
                }
                if ($query->getX() !== false) {
                    $this->setCoordinates($query->getX(), $query->getY());
                }
            } elseif (is_array($query)) {
                foreach ($query as $key => $value) {
                    $this->add($key, $value);
                }
            }
        }
        
        /**
         *    Sets image coordinates. Set to false to clear
         *    them.
         *    @param integer $x    Horizontal position.
         *    @param integer $y    Vertical position.
         *    @access public
         */
        function setCoordinates($x = false, $y = false) {
            if (($x === false) || ($y === false)) {
                $this->_x = $this->_y = false;
                return;
            }
            $this->_x = (integer)$x;
            $this->_y = (integer)$y;
        }
        
        /**
         *    Accessor for horizontal image coordinate.
         *    @return integer        X value.
         *    @access public
         */
        function getX() {
            return $this->_x;
        }
         
        /**
         *    Accessor for vertical image coordinate.
         *    @return integer        Y value.
         *    @access public
         */
        function getY() {
            return $this->_y;
        }
        
        /**
         *    Accessor for single value.
         *    @return string/array    False if missing, string
         *                            if present and array if
         *                            multiple entries.
         *    @access public
         */
        function getValue($key) {
            if (! isset($this->_request[$key])) {
                return false;
            } elseif (count($this->_request[$key]) == 1) {
                return $this->_request[$key][0];
            } else {
                return $this->_request[$key];
            }
        }
        
        /**
         *    Accessor for key list.
         *    @return array        List of keys present.
         *    @access public
         */
        function getKeys() {
            return array_keys($this->_request);
        }
        
        /**
         *    Renders the query string as a URL encoded
         *    request part.
         *    @return string        Part of URL.
         *    @access public
         */
        function asString() {
            $statements = array();
            foreach ($this->_request as $key => $values) {
                foreach ($values as $value) {
                    $statements[] = "$key=" . urlencode($value);
                }
            }
            $coords = ($this->_x !== false) ? '?' . $this->_x . ',' . $this->_y : '';
            return implode('&', $statements) . $coords;
        }
    }
?>
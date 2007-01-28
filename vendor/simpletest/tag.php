<?php
    /**
     *	Base include file for SimpleTest.
     *	@package	SimpleTest
     *	@subpackage	WebTester
     *	@version	$Id: tag.php,v 1.73 2005/02/02 22:49:36 lastcraft Exp $
     */
     
    /**#@+
     * include SimpleTest files
     */
    require_once(dirname(__FILE__) . '/parser.php');
    /**#@-*/
   
    /**
     *    HTML or XML tag.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleTag {
        var $_name;
        var $_attributes;
        var $_content;
        
        /**
         *    Starts with a named tag with attributes only.
         *    @param string $name        Tag name.
         *    @param hash $attributes    Attribute names and
         *                               string values. Note that
         *                               the keys must have been
         *                               converted to lower case.
         */
        function SimpleTag($name, $attributes) {
            $this->_name = $name;
            $this->_attributes = $attributes;
            $this->_content = '';
        }
        
        /**
         *    Check to see if the tag can have both start and
         *    end tags with content in between.
         *    @return boolean        True if content allowed.
         *    @access public
         */
        function expectEndTag() {
            return true;
        }
        
        /**
         *    Appends string content to the current content.
         *    @param string $content        Additional text.
         *    @access public
         */
        function addContent($content) {
            $this->_content .= (string)$content;
        }
        
        /**
         *    Adds an enclosed tag to the content.
         *    @param SimpleTag $tag    New tag.
         *    @access public
         */
        function addTag(&$tag) {
        }
        
        /**
         *    Accessor for tag name.
         *    @return string       Name of tag.
         *    @access public
         */
        function getTagName() {
            return $this->_name;
        }
        
        /**
         *    List oflegal child elements.
         *    @return array        List of element names.
         *    @access public
         */
        function getChildElements() {
            return array();
        }
        
        /**
         *    Accessor for an attribute.
         *    @param string $label    Attribute name.
         *    @return string          Attribute value.
         *    @access public
         */
        function getAttribute($label) {
            $label = strtolower($label);
            if (! isset($this->_attributes[$label])) {
                return false;
            }
            if ($this->_attributes[$label] === '') {
                return true;
            }
            return (string)$this->_attributes[$label];
        }
        
        /**
         *    Sets an attribute.
         *    @param string $label    Attribute name.
         *    @return string $value   New attribute value.
         *    @access protected
         */
        function _setAttribute($label, $value) {
            $this->_attributes[strtolower($label)] = $value;
        }
        
        /**
         *    Accessor for the whole content so far.
         *    @return string       Content as big raw string.
         *    @access public
         */
        function getContent() {
            return $this->_content;
        }
        
        /**
         *    Accessor for content reduced to visible text. Acts
         *    like a text mode browser, normalising space and
         *    reducing images to their alt text.
         *    @return string       Content as plain text.
         *    @access public
         */
        function getText() {
            return SimpleSaxParser::normalise($this->_content);
        }
        
        /**
         *    Test to see if id attribute matches.
         *    @param string $id        ID to test against.
         *    @return boolean          True on match.
         *    @access public
         */
        function isId($id) {
            return ($this->getAttribute('id') == $id);
        }
    }
    
    /**
     *    Page title.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleTitleTag extends SimpleTag {
        
        /**
         *    Starts with a named tag with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleTitleTag($attributes) {
            $this->SimpleTag('title', $attributes);
        }
    }
    
    /**
     *    Link.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleAnchorTag extends SimpleTag {
        
        /**
         *    Starts with a named tag with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleAnchorTag($attributes) {
            $this->SimpleTag('a', $attributes);
        }
        
        /**
         *    Accessor for URL as string.
         *    @return string    Coerced as string.
         *    @access public
         */
        function getHref() {
            $url = $this->getAttribute('href');
            if (is_bool($url)) {
                $url = '';
            }
            return $url;
        }
    }
    
    /**
     *    Form element.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleWidget extends SimpleTag {
        var $_value;
        var $_is_set;
        
        /**
         *    Starts with a named tag with attributes only.
         *    @param string $name        Tag name.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleWidget($name, $attributes) {
            $this->SimpleTag($name, $attributes);
            $this->_value = false;
            $this->_is_set = false;
        }
        
        /**
         *    Accessor for name submitted as the key in
         *    GET/POST variables hash.
         *    @return string        Parsed value.
         *    @access public
         */
        function getName() {
            return $this->getAttribute('name');
        }
        
        /**
         *    Accessor for default value parsed with the tag.
         *    @return string        Parsed value.
         *    @access public
         */
        function getDefault() {
            $default = $this->getAttribute('value');
            if ($default === true) {
                $default = '';
            }
            if ($default === false) {
                $default = '';
            }
            return $default;
        }
        
        /**
         *    Accessor for currently set value or default if
         *    none.
         *    @return string      Value set by form or default
         *                        if none.
         *    @access public
         */
        function getValue() {
            if (! $this->_is_set) {
                return $this->getDefault();
            }
            return $this->_value;
        }
        
        /**
         *    Sets the current form element value.
         *    @param string $value       New value.
         *    @return boolean            True if allowed.
         *    @access public
         */
        function setValue($value) {
            $this->_value = $value;
            $this->_is_set = true;
            return true;
        }
        
        /**
         *    Resets the form element value back to the
         *    default.
         *    @access public
         */
        function resetValue() {
            $this->_is_set = false;
        }
    }
    
    /**
     *    Text, password and hidden field.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleTextTag extends SimpleWidget {
        
        /**
         *    Starts with a named tag with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleTextTag($attributes) {
            $this->SimpleWidget('input', $attributes);
            if ($this->getAttribute('value') === false) {
                $this->_setAttribute('value', '');
            }
        }
        
        /**
         *    Tag contains no content.
         *    @return boolean        False.
         *    @access public
         */
        function expectEndTag() {
            return false;
        }
        
        /**
         *    Sets the current form element value. Cannot
         *    change the value of a hidden field.
         *    @param string $value       New value.
         *    @return boolean            True if allowed.
         *    @access public
         */
        function setValue($value) {
            if ($this->getAttribute('type') == 'hidden') {
                return false;
            }
            return parent::setValue($value);
        }
    }
    
    /**
     *    Submit button as input tag.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleSubmitTag extends SimpleWidget {
        
        /**
         *    Starts with a named tag with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleSubmitTag($attributes) {
            $this->SimpleWidget('input', $attributes);
            if ($this->getAttribute('name') === false) {
                $this->_setAttribute('name', 'submit');
            }
            if ($this->getAttribute('value') === false) {
                $this->_setAttribute('value', 'Submit');
            }
        }
        
        /**
         *    Tag contains no end element.
         *    @return boolean        False.
         *    @access public
         */
        function expectEndTag() {
            return false;
        }
        
        /**
         *    Disables the setting of the button value.
         *    @param string $value       Ignored.
         *    @return boolean            True if allowed.
         *    @access public
         */
        function setValue($value) {
            return false;
        }
        
        /**
         *    Value of browser visible text.
         *    @return string        Visible label.
         *    @access public
         */
        function getLabel() {
            return $this->getValue();
        }
        
        /**
         *    Gets the values submitted as a form.
         *    @return array    Hash of name and values.
         *    @access public
         */
        function getSubmitValues() {
            return array($this->getName() => $this->getValue());
        }
    }
      
    /**
     *    Image button as input tag.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleImageSubmitTag extends SimpleWidget {
        
        /**
         *    Starts with a named tag with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleImageSubmitTag($attributes) {
            $this->SimpleWidget('input', $attributes);
        }
        
        /**
         *    Tag contains no end element.
         *    @return boolean        False.
         *    @access public
         */
        function expectEndTag() {
            return false;
        }
        
        /**
         *    Disables the setting of the button value.
         *    @param string $value       Ignored.
         *    @return boolean            True if allowed.
         *    @access public
         */
        function setValue($value) {
            return false;
        }
        
        /**
         *    Value of browser visible text.
         *    @return string        Visible label.
         *    @access public
         */
        function getLabel() {
            if ($this->getAttribute('title')) {
                return $this->getAttribute('title');
            }
            return $this->getAttribute('alt');
        }
        
        /**
         *    Gets the values submitted as a form.
         *    @return array    Hash of name and values.
         *    @access public
         */
        function getSubmitValues($x, $y) {
            return array(
                    $this->getName() . '.x' => $x,
                    $this->getName() . '.y' => $y);
        }
    }
      
    /**
     *    Submit button as button tag.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleButtonTag extends SimpleWidget {
        
        /**
         *    Starts with a named tag with attributes only.
         *    Defaults are very browser dependent.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleButtonTag($attributes) {
            $this->SimpleWidget('button', $attributes);
        }
        
        /**
         *    Check to see if the tag can have both start and
         *    end tags with content in between.
         *    @return boolean        True if content allowed.
         *    @access public
         */
        function expectEndTag() {
            return true;
        }
        
        /**
         *    Disables the setting of the button value.
         *    @param string $value       Ignored.
         *    @return boolean            True if allowed.
         *    @access public
         */
        function setValue($value) {
            return false;
        }
        
        /**
         *    Value of browser visible text.
         *    @return string        Visible label.
         *    @access public
         */
        function getLabel() {
            return $this->getContent();
        }
        
        /**
         *    Gets the values submitted as a form. Gone
         *    for the Mozilla defaults values.
         *    @return array    Hash of name and values.
         *    @access public
         */
        function getSubmitValues() {
            if ($this->getAttribute('name') === false) {
                return array();
            }
            if ($this->getAttribute('value') === false) {
                return array($this->getName() => '');
            }
            return array($this->getName() => $this->getValue());
        }
    }
  
    /**
     *    Content tag for text area.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleTextAreaTag extends SimpleWidget {
        
        /**
         *    Starts with a named tag with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleTextAreaTag($attributes) {
            $this->SimpleWidget('textarea', $attributes);
        }
        
        /**
         *    Accessor for starting value.
         *    @return string        Parsed value.
         *    @access public
         */
        function getDefault() {
            if ($this->_wrapIsEnabled()) {
                return wordwrap(
                        $this->getContent(),
                        (integer)$this->getAttribute('cols'),
                        "\n");
            }
            return $this->getContent();
        }
        
        /**
         *    Applies word wrapping if needed.
         *    @param string $value      New value.
         *    @return boolean            True if allowed.
         *    @access public
         */
        function setValue($value) {
            if ($this->_wrapIsEnabled()) {
                $value = wordwrap(
                        $value,
                        (integer)$this->getAttribute('cols'),
                        "\n");
            }
            return parent::setValue($value);
        }
        
        /**
         *    Test to see if text should be wrapped.
         *    @return boolean        True if wrapping on.
         *    @access private
         */
        function _wrapIsEnabled() {
            if ($this->getAttribute('cols')) {
                $wrap = $this->getAttribute('wrap');
                if (($wrap == 'physical') || ($wrap == 'hard')) {
                    return true;
                }
            }
            return false;
        }
    }
    
    /**
     *    Checkbox widget.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleCheckboxTag extends SimpleWidget {
        
        /**
         *    Starts with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleCheckboxTag($attributes) {
            $this->SimpleWidget('input', $attributes);
            if ($this->getAttribute('value') === false) {
                $this->_setAttribute('value', 'on');
            }
        }
        
        /**
         *    Tag contains no content.
         *    @return boolean        False.
         *    @access public
         */
        function expectEndTag() {
            return false;
        }
        
        /**
         *    The only allowed value in the one in the
         *    "value" attribute. The default for this
         *    attribute is "on".
         *    @param string $value      New value.
         *    @return boolean           True if allowed.
         *    @access public
         */
        function setValue($value) {
            if ($value === false) {
                return parent::setValue($value);
            }
            if ($value != $this->getAttribute('value')) {
                return false;
            }
            return parent::setValue($value);
        }
        
        /**
         *    Accessor for starting value. The default
         *    value is "on".
         *    @return string        Parsed value.
         *    @access public
         */
        function getDefault() {
            if ($this->getAttribute('checked')) {
                return $this->getAttribute('value');
            }
            return false;
        }
    }
    
    /**
     *    Drop down widget.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleSelectionTag extends SimpleWidget {
        var $_options;
        var $_choice;
        
        /**
         *    Starts with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleSelectionTag($attributes) {
            $this->SimpleWidget('select', $attributes);
            $this->_options = array();
            $this->_choice = false;
        }
        
        /**
         *    Adds an option tag to a selection field.
         *    @param SimpleOptionTag $tag     New option.
         *    @access public
         */
        function addTag(&$tag) {
            if ($tag->getTagName() == 'option') {
                $this->_options[] = &$tag;
            }
        }
        
        /**
         *    Text within the selection element is ignored.
         *    @param string $content        Ignored.
         *    @access public
         */
        function addContent($content) {
        }
        
        /**
         *    Scans options for defaults. If none, then
         *    the first option is selected.
         *    @return string        Selected field.
         *    @access public
         */
        function getDefault() {
            for ($i = 0, $count = count($this->_options); $i < $count; $i++) {
                if ($this->_options[$i]->getAttribute('selected')) {
                    return $this->_options[$i]->getDefault();
                }
            }
            if ($count > 0) {
                return $this->_options[0]->getDefault();
            }
            return '';
        }
        
        /**
         *    Can only set allowed values.
         *    @param string $value       New choice.
         *    @return boolean            True if allowed.
         *    @access public
         */
        function setValue($value) {
            for ($i = 0, $count = count($this->_options); $i < $count; $i++) {
                if (trim($this->_options[$i]->getContent()) == trim($value)) {
                    $this->_choice = $i;
                    return true;
                }
            }
            return false;
        }
        
        /**
         *    Accessor for current selection value.
         *    @return string      Value attribute or
         *                        content of opton.
         *    @access public
         */
        function getValue() {
            if ($this->_choice === false) {
                return $this->getDefault();
            }
            return $this->_options[$this->_choice]->getValue();
        }
    }
    
    /**
     *    Drop down widget.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class MultipleSelectionTag extends SimpleWidget {
        var $_options;
        var $_values;
        
        /**
         *    Starts with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function MultipleSelectionTag($attributes) {
            $this->SimpleWidget('select', $attributes);
            $this->_options = array();
            $this->_values = false;
        }
        
        /**
         *    Adds an option tag to a selection field.
         *    @param SimpleOptionTag $tag     New option.
         *    @access public
         */
        function addTag(&$tag) {
            if ($tag->getTagName() == 'option') {
                $this->_options[] = &$tag;
            }
        }
        
        /**
         *    Text within the selection element is ignored.
         *    @param string $content        Ignored.
         *    @access public
         */
        function addContent($content) {
        }
        
        /**
         *    Scans options for defaults to populate the
         *    value array().
         *    @return array        Selected fields.
         *    @access public
         */
        function getDefault() {
            $default = array();
            for ($i = 0, $count = count($this->_options); $i < $count; $i++) {
                if ($this->_options[$i]->getAttribute('selected')) {
                    $default[] = $this->_options[$i]->getDefault();
                }
            }
            return $default;
        }
        
        /**
         *    Can only set allowed values.
         *    @param array $values       New choices.
         *    @return boolean            True if allowed.
         *    @access public
         */
        function setValue($values) {
            foreach ($values as $value) {
                $is_option = false;
                for ($i = 0, $count = count($this->_options); $i < $count; $i++) {
                    if (trim($this->_options[$i]->getContent()) == trim($value)) {
                        $is_option = true;
                        break;
                    }
                }
                if (! $is_option) {
                    return false;
                }
            }
            $this->_values = $values;
            return true;
        }
        
        /**
         *    Accessor for current selection value.
         *    @return array      List of currently set options.
         *    @access public
         */
        function getValue() {
            if ($this->_values === false) {
                return $this->getDefault();
            }
            return $this->_values;
        }
    }
    
    /**
     *    Option for selection field.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleOptionTag extends SimpleWidget {
        
        /**
         *    Stashes the attributes.
         */
        function SimpleOptionTag($attributes) {
            $this->SimpleWidget('option', $attributes);
        }
        
        /**
         *    Does nothing.
         *    @param string $value      Ignored.
         *    @return boolean           Not allowed.
         *    @access public
         */
        function setValue($value) {
            return false;
        }
        
        /**
         *    Accessor for starting value. Will be set to
         *    the option label if no value exists.
         *    @return string        Parsed value.
         *    @access public
         */
        function getDefault() {
            if ($this->getAttribute('value') === false) {
                return $this->getContent();
            }
            return $this->getAttribute('value');
        }
    }
    
    /**
     *    Radio button.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleRadioButtonTag extends SimpleWidget {
        
        /**
         *    Stashes the attributes.
         *    @param array $attributes        Hash of attributes.
         */
        function SimpleRadioButtonTag($attributes) {
            $this->SimpleWidget('input', $attributes);
            if ($this->getAttribute('value') === false) {
                $this->_setAttribute('value', 'on');
            }
        }
        
        /**
         *    Tag contains no content.
         *    @return boolean        False.
         *    @access public
         */
        function expectEndTag() {
            return false;
        }
        
        /**
         *    The only allowed value in the one in the
         *    "value" attribute.
         *    @param string $value      New value.
         *    @return boolean           True if allowed.
         *    @access public
         */
        function setValue($value) {
            if ($value === false) {
                return parent::setValue($value);
            }
            if ($value != $this->getAttribute('value')) {
                return false;
            }
            return parent::setValue($value);
        }
        
        /**
         *    Accessor for starting value.
         *    @return string        Parsed value.
         *    @access public
         */
        function getDefault() {
            if ($this->getAttribute('checked')) {
                return $this->getAttribute('value');
            }
            return false;
        }
    }

    /**
     *    A group of tags with the same name within a form.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleCheckboxGroup {
        var $_widgets;
        
        /**
         *    Starts empty.
         *    @access public
         */
        function SimpleCheckboxGroup() {
            $this->_widgets = array();
        }

        /**
         *    Accessor for an attribute.
         *    @param string $label    Attribute name.
         *    @return boolean         Always false.
         *    @access public
         */
        function getAttribute($label) {
            return false;
        }
        
        /**
         *    Scans the checkboxes for one with the appropriate
         *    ID field.
         *    @param string $id        ID value to try.
         *    @return boolean          True if matched.
         *    @access public
         */
        function isId($id) {
            for ($i = 0, $count = count($this->_widgets); $i < $count; $i++) {
                if ($this->_widgets[$i]->isId($id)) {
                    return true;
                }
            }
            return false;
        }

        /**
         *    Adds a tag to the group.
         *    @param SimpleWidget $widget
         *    @access public
         */
        function addWidget(&$widget) {
            $this->_widgets[] = &$widget;
        }
        
        /**
         *    Fetches the name for the widget from the first
         *    member.
         *    @return string        Name of widget.
         *    @access public
         */
        function getName() {
            if (count($this->_widgets) > 0) {
                return $this->_widgets[0]->getName();
            }
        }
        
        /**
         *    Accessor for current selected widget or false
         *    if none.
         *    @return string/array     Widget values or false if none.
         *    @access public
         */
        function getValue() {
            $values = array();
            for ($i = 0, $count = count($this->_widgets); $i < $count; $i++) {
                if ($this->_widgets[$i]->getValue()) {
                    $values[] = $this->_widgets[$i]->getValue();
                }
            }
            return $this->_coerceValues($values);
        }
        
        /**
         *    Accessor for starting value that is active.
         *    @return string/array      Widget values or false if none.
         *    @access public
         */
        function getDefault() {
            $values = array();
            for ($i = 0, $count = count($this->_widgets); $i < $count; $i++) {
                if ($this->_widgets[$i]->getDefault()) {
                    $values[] = $this->_widgets[$i]->getDefault();
                }
            }
            return $this->_coerceValues($values);
        }
        
        /**
         *    Accessor for current set values.
         *    @param string/array/boolean $values   Either a single string, a
         *                                          hash or false for nothing set.
         *    @return boolean                       True if all values can be set.
         *    @access public
         */
        function setValue($values) {
            $values = $this->_makeArray($values);
            if (! $this->_valuesArePossible($values)) {
                return false;
            }
            for ($i = 0, $count = count($this->_widgets); $i < $count; $i++) {
                $possible = $this->_widgets[$i]->getAttribute('value');
                if (in_array($this->_widgets[$i]->getAttribute('value'), $values)) {
                    $this->_widgets[$i]->setValue($possible);
                } else {
                    $this->_widgets[$i]->setValue(false);
                }
            }
            return true;
        }
        
        /**
         *    Tests to see if a possible value set is legal.
         *    @param string/array/boolean $values   Either a single string, a
         *                                          hash or false for nothing set.
         *    @return boolean                       False if trying to set a
         *                                          missing value.
         *    @access private
         */
        function _valuesArePossible($values) {
            $matches = array();
            for ($i = 0, $count = count($this->_widgets); $i < $count; $i++) {
                $possible = $this->_widgets[$i]->getAttribute('value');
                if (in_array($possible, $values)) {
                    $matches[] = $possible;
                }
            }
            return ($values == $matches);
        }
        
        /**
         *    Converts the output to an appropriate format. This means
         *    that no values is false, a single value is just that
         *    value and only two or more are contained in an array.
         *    @param array $values           List of values of widgets.
         *    @return string/array/boolean   Expected format for a tag.
         *    @access private
         */
        function _coerceValues($values) {
            if (count($values) == 0) {
                return false;
            } elseif (count($values) == 1) {
                return $values[0];
            } else {
                return $values;
            }
        }
        
        /**
         *    Converts false or string into array. The opposite of
         *    the coercian method.
         *    @param string/array/boolean $value  A single item is converted
         *                                        to a one item list. False
         *                                        gives an empty list.
         *    @return array                       List of values, possibly empty.
         *    @access private
         */
        function _makeArray($value) {
            if ($value === false) {
                return array();
            }
            if (is_string($value)) {
                return array($value);
            }
            return $value;
        }
    }

    /**
     *    A group of tags with the same name within a form.
     *    Used for radio buttons.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleRadioGroup {
        var $_widgets;
        
        /**
         *    Starts empty.
         *    @access public
         */
        function SimpleRadioGroup() {
            $this->_widgets = array();
        }
        
        /**
         *    Accessor for an attribute.
         *    @param string $label    Attribute name.
         *    @return boolean         Always false.
         *    @access public
         */
        function getAttribute($label) {
            return false;
        }
        
        /**
         *    Scans the checkboxes for one with the appropriate
         *    ID field.
         *    @param string $id        ID value to try.
         *    @return boolean          True if matched.
         *    @access public
         */
        function isId($id) {
            for ($i = 0, $count = count($this->_widgets); $i < $count; $i++) {
                if ($this->_widgets[$i]->isId($id)) {
                    return true;
                }
            }
            return false;
        }
        
        /**
         *    Adds a tag to the group.
         *    @param SimpleWidget $widget
         *    @access public
         */
        function addWidget(&$widget) {
            $this->_widgets[] = &$widget;
        }
        
        /**
         *    Fetches the name for the widget from the first
         *    member.
         *    @return string        Name of widget.
         *    @access public
         */
        function getName() {
            if (count($this->_widgets) > 0) {
                return $this->_widgets[0]->getName();
            }
        }
        
        /**
         *    Each tag is tried in turn until one is
         *    successfully set. The others will be
         *    unchecked if successful.
         *    @param string $value      New value.
         *    @return boolean           True if any allowed.
         *    @access public
         */
        function setValue($value) {
            if (! $this->_valueIsPossible($value)) {
                return false;
            }
            $index = false;
            for ($i = 0, $count = count($this->_widgets); $i < $count; $i++) {
                if (! $this->_widgets[$i]->setValue($value)) {
                    $this->_widgets[$i]->setValue(false);
                }
            }
            return true;
        }
        
        /**
         *    Tests to see if a value is allowed.
         *    @param string    Attempted value.
         *    @return boolean  True if a valid value.
         *    @access private
         */
        function _valueIsPossible($value) {
            for ($i = 0, $count = count($this->_widgets); $i < $count; $i++) {
                if ($this->_widgets[$i]->getAttribute('value') == $value) {
                    return true;
                }
            }
            return false;
        }
        
        /**
         *    Accessor for current selected widget or false
         *    if none.
         *    @return string/boolean   Value attribute or
         *                             content of opton.
         *    @access public
         */
        function getValue() {
            for ($i = 0, $count = count($this->_widgets); $i < $count; $i++) {
                if ($this->_widgets[$i]->getValue()) {
                    return $this->_widgets[$i]->getValue();
                }
            }
            return false;
        }
        
        /**
         *    Accessor for starting value that is active.
         *    @return string/boolean      Value of first checked
         *                                widget or false if none.
         *    @access public
         */
        function getDefault() {
            for ($i = 0, $count = count($this->_widgets); $i < $count; $i++) {
                if ($this->_widgets[$i]->getDefault()) {
                    return $this->_widgets[$i]->getDefault();
                }
            }
            return false;
        }
    }
    
    /**
     *    Tag to aid parsing the form.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleFormTag extends SimpleTag {
        
        /**
         *    Starts with a named tag with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleFormTag($attributes) {
            $this->SimpleTag('form', $attributes);
        }
    }
    
    /**
     *    Tag to aid parsing the frames in a page.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleFrameTag extends SimpleTag {
        
        /**
         *    Starts with a named tag with attributes only.
         *    @param hash $attributes    Attribute names and
         *                               string values.
         */
        function SimpleFrameTag($attributes) {
            $this->SimpleTag('frame', $attributes);
        }
        
        /**
         *    Tag contains no content.
         *    @return boolean        False.
         *    @access public
         */
        function expectEndTag() {
            return false;
        }
    }
?>
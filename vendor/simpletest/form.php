<?php
    /**
     *	Base include file for SimpleTest.
     *	@package	SimpleTest
     *	@subpackage	WebTester
     *	@version	$Id: form.php,v 1.16 2005/02/22 02:17:04 lastcraft Exp $
     */
     
    /**#@+
     * include SimpleTest files
     */
    require_once(dirname(__FILE__) . '/tag.php');
    require_once(dirname(__FILE__) . '/encoding.php');
    /**#@-*/
    
    /**
     *    Used to extract form elements for testing against.
     *    Searches by name attribute.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleNameSelector {
        var $_name;
        
        /**
         *    Stashes the name for later comparison.
         *    @param string $name     Name attribute to match.
         */
        function SimpleNameSelector($name) {
            $this->_name = $name;
        }

        /**
         *    Comparison. Compares with name attribute of
         *    widget.
         *    @param SimpleWidget $widget    Control to compare.
         *    @access public
         */
        function isMatch($widget) {
            return ($widget->getName() == $this->_name);
        }
    }
    
    /**
     *    Used to extract form elements for testing against.
     *    Searches by visible label or alt text.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleLabelSelector {
        var $_label;
        
        /**
         *    Stashes the name for later comparison.
         *    @param string $label     Visible text to match.
         */
        function SimpleLabelSelector($label) {
            $this->_label = $label;
        }

        /**
         *    Comparison. Compares visible text of widget.
         *    @param SimpleWidget $widget    Control to compare.
         *    @access public
         */
        function isMatch($widget) {
            return (trim($widget->getLabel()) == trim($this->_label));
        }
    }
    
    /**
     *    Used to extract form elements for testing against.
     *    Searches dy id attribute.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleIdSelector {
        var $_id;
        
        /**
         *    Stashes the name for later comparison.
         *    @param string $id     ID atribute to match.
         */
        function SimpleIdSelector($id) {
            $this->_id = $id;
        }

        /**
         *    Comparison. Compares id attribute of widget.
         *    @param SimpleWidget $widget    Control to compare.
         *    @access public
         */
        function isMatch($widget) {
            return $widget->isId($this->_id);
        }
    }
   
    /**
     *    Form tag class to hold widget values.
	 *    @package SimpleTest
	 *    @subpackage WebTester
     */
    class SimpleForm {
        var $_method;
        var $_action;
        var $_default_target;
        var $_id;
        var $_buttons;
        var $_images;
        var $_widgets;
        var $_radios;
        var $_checkboxes;
        
        /**
         *    Starts with no held controls/widgets.
         *    @param SimpleTag $tag        Form tag to read.
         *    @param SimpleUrl $url        Location of holding page.
         */
        function SimpleForm($tag, $url) {
            $this->_method = $tag->getAttribute('method');
            $this->_action = $this->_createAction($tag->getAttribute('action'), $url);
            $this->_default_target = false;
            $this->_id = $tag->getAttribute('id');
            $this->_buttons = array();
            $this->_images = array();
            $this->_widgets = array();
            $this->_radios = array();
            $this->_checkboxes = array();
        }
        
        /**
         *    Sets the frame target within a frameset.
         *    @param string $frame        Name of frame.
         *    @access public
         */
        function setDefaultTarget($frame) {
            $this->_default_target = $frame;
        }
        
        /**
         *    Accessor for form action.
         *    @return string           Either get or post.
         *    @access public
         */
        function getMethod() {
            return ($this->_method ? strtolower($this->_method) : 'get');
        }
        
        /**
         *    Combined action attribute with current location
         *    to get an absolute form target.
         *    @param string $action    Action attribute from form tag.
         *    @param SimpleUrl $base   Page location.
         *    @return SimpleUrl        Absolute form target.
         */
        function _createAction($action, $base) {
            if ($action === false) {
                return $base;
            }
            if ($action === true) {
                $url = new SimpleUrl('');
            } else {
                $url = new SimpleUrl($action);
            }
            return $url->makeAbsolute($base);
        }
        
        /**
         *    Absolute URL of the target.
         *    @return SimpleUrl           URL target.
         *    @access public
         */
        function getAction() {
            $url = $this->_action;
            if ($this->_default_target && ! $url->getTarget()) {
                $url->setTarget($this->_default_target);
            }
            return $url;
        }
        
        /**
         *    ID field of form for unique identification.
         *    @return string           Unique tag ID.
         *    @access public
         */
        function getId() {
            return $this->_id;
        }
        
        /**
         *    Adds a tag contents to the form.
         *    @param SimpleWidget $tag        Input tag to add.
         *    @access public
         */
        function addWidget($tag) {
            if (strtolower($tag->getAttribute('type')) == 'submit') {
                $this->_buttons[] = &$tag;
            } elseif (strtolower($tag->getAttribute('type')) == 'image') {
                $this->_images[] = &$tag;
            } elseif ($tag->getName()) {
                $this->_setWidget($tag);
            }
        }
        
        /**
         *    Sets the widget into the form, grouping radio
         *    buttons if any.
         *    @param SimpleWidget $tag   Incoming form control.
         *    @access private
         */
        function _setWidget($tag) {
            if (strtolower($tag->getAttribute('type')) == 'radio') {
                $this->_addRadioButton($tag);
            } elseif (strtolower($tag->getAttribute('type')) == 'checkbox') {
                $this->_addCheckbox($tag);
            } else {
                $this->_widgets[] = &$tag;
            }
        }
        
        /**
         *    Adds a radio button, building a group if necessary.
         *    @param SimpleRadioButtonTag $tag   Incoming form control.
         *    @access private
         */
        function _addRadioButton($tag) {
            if (! isset($this->_radios[$tag->getName()])) {
                $this->_widgets[] = &new SimpleRadioGroup();
                $this->_radios[$tag->getName()] = count($this->_widgets) - 1;
            }
            $this->_widgets[$this->_radios[$tag->getName()]]->addWidget($tag);
        }
        
        /**
         *    Adds a checkbox, making it a group on a repeated name.
         *    @param SimpleCheckboxTag $tag   Incoming form control.
         *    @access private
         */
        function _addCheckbox($tag) {
            if (! isset($this->_checkboxes[$tag->getName()])) {
                $this->_widgets[] = &$tag;
                $this->_checkboxes[$tag->getName()] = count($this->_widgets) - 1;
            } else {
                $index = $this->_checkboxes[$tag->getName()];
                if (! SimpleTestCompatibility::isA($this->_widgets[$index], 'SimpleCheckboxGroup')) {
                    $previous = &$this->_widgets[$index];
                    $this->_widgets[$index] = &new SimpleCheckboxGroup();
                    $this->_widgets[$index]->addWidget($previous);
                }
                $this->_widgets[$index]->addWidget($tag);
            }
        }
        
        /**
         *    Extracts current value from form.
         *    @param SimpleSelector $selector   Criteria to apply.
         *    @return string/array              Value(s) as string or null
         *                                      if not set.
         *    @access public
         */
        function _getValueBySelector($selector) {
            for ($i = 0, $count = count($this->_widgets); $i < $count; $i++) {
                if ($selector->isMatch($this->_widgets[$i])) {
                    return $this->_widgets[$i]->getValue();
                }
            }
            foreach ($this->_buttons as $button) {
                if ($selector->isMatch($button)) {
                    return $button->getValue();
                }
            }
            return null;
        }
        
        /**
         *    Extracts current value from form.
         *    @param string $name        Keyed by widget name.
         *    @return string/array       Value(s) or null
         *                               if not set.
         *    @access public
         */
        function getValue($name) {
            return $this->_getValueBySelector(new SimpleNameSelector($name));
        }
        
        /**
         *    Extracts current value from form by the ID.
         *    @param string/integer $id  Keyed by widget ID attribute.
         *    @return string/array       Value(s) or null
         *                               if not set.
         *    @access public
         */
        function getValueById($id) {
            return $this->_getValueBySelector(new SimpleIdSelector($id));
        }
        
        /**
         *    Sets a widget value within the form.
         *    @param SimpleSelector $selector   Criteria to apply.
         *    @param string $value              Value to input into the widget.
         *    @return boolean                   True if value is legal, false
         *                                      otherwise. If the field is not
         *                                      present, nothing will be set.
         *    @access public
         */
        function _setFieldBySelector($selector, $value) {
            $success = false;
            for ($i = 0, $count = count($this->_widgets); $i < $count; $i++) {
                if ($selector->isMatch($this->_widgets[$i])) {
                    if ($this->_widgets[$i]->setValue($value)) {
                        $success = true;
                    }
                }
            }
            return $success;
        }
        
        /**
         *    Sets a widget value within the form.
         *    @param string $name     Name of widget tag.
         *    @param string $value    Value to input into the widget.
         *    @return boolean         True if value is legal, false
         *                            otherwise. If the field is not
         *                            present, nothing will be set.
         *    @access public
         */
        function setField($name, $value) {
            return $this->_setFieldBySelector(new SimpleNameSelector($name), $value);
        }
         
        /**
         *    Sets a widget value within the form by using the ID.
         *    @param string/integer $id   Name of widget tag.
         *    @param string $value        Value to input into the widget.
         *    @return boolean             True if value is legal, false
         *                                otherwise. If the field is not
         *                                present, nothing will be set.
         *    @access public
         */
        function setFieldById($id, $value) {
            return $this->_setFieldBySelector(new SimpleIdSelector($id), $value);
        }
       
        /**
         *    Creates the encoding for the current values in the
         *    form.
         *    @return SimpleFormEncoding    Request to submit.
         *    @access private
         */
        function _getEncoding() {
            $encoding = new SimpleFormEncoding();
            for ($i = 0, $count = count($this->_widgets); $i < $count; $i++) {
                $encoding->add(
                        $this->_widgets[$i]->getName(),
                        $this->_widgets[$i]->getValue());
            }
            return $encoding;
        }
        
        /**
         *    Test to see if a form has a submit button.
         *    @param SimpleSelector $selector   Criteria to apply.
         *    @return boolean                   True if present.
         *    @access private
         */
        function _hasSubmitBySelector($selector) {
            foreach ($this->_buttons as $button) {
                if ($selector->isMatch($button)) {
                    return true;
                }
            }
            return false;
        }
        
        /**
         *    Test to see if a form has a submit button with this
         *    name attribute.
         *    @param string $name        Name to look for.
         *    @return boolean            True if present.
         *    @access public
         */
        function hasSubmitName($name) {
            return $this->_hasSubmitBySelector(new SimpleNameSelector($name));
        }
        
        /**
         *    Test to see if a form has a submit button with this
         *    value attribute.
         *    @param string $label    Button label to search for.
         *    @return boolean         True if present.
         *    @access public
         */
        function hasSubmitLabel($label) {
            return $this->_hasSubmitBySelector(new SimpleLabelSelector($label));
        }
        
        /**
         *    Test to see if a form has a submit button with this
         *    ID attribute.
         *    @param string $id      Button ID attribute to search for.
         *    @return boolean        True if present.
         *    @access public
         */
        function hasSubmitId($id) {
            return $this->_hasSubmitBySelector(new SimpleIdSelector($id));
        }
        
        /**
         *    Test to see if a form has an image control.
         *    @param SimpleSelector $selector   Criteria to apply.
         *    @return boolean                   True if present.
         *    @access public
         */
        function _hasImageBySelector($selector) {
            foreach ($this->_images as $image) {
                if ($selector->isMatch($image)) {
                    return true;
                }
            }
            return false;
        }
        
        /**
         *    Test to see if a form has a submit button with this
         *    name attribute.
         *    @param string $label   Button alt attribute to search for
         *                           or nearest equivalent.
         *    @return boolean        True if present.
         *    @access public
         */
        function hasImageLabel($label) {
            return $this->_hasImageBySelector(new SimpleLabelSelector($label));
        }
        
        /**
         *    Test to see if a form has a submittable image with this
         *    field name.
         *    @param string $name    Image name to search for.
         *    @return boolean        True if present.
         *    @access public
         */
        function hasImageName($name) {
            return $this->_hasImageBySelector(new SimpleNameSelector($name));
        }
         
        /**
         *    Test to see if a form has a submittable image with this
         *    ID attribute.
         *    @param string $id      Button ID attribute to search for.
         *    @return boolean        True if present.
         *    @access public
         */
        function hasImageId($id) {
            return $this->_hasImageBySelector(new SimpleIdSelector($id));
        }
       
        /**
         *    Gets the submit values for a selected button.
         *    @param SimpleSelector $selector   Criteria to apply.
         *    @param hash $additional           Additional data for the form.
         *    @return SimpleEncoding            Submitted values or false
         *                                      if there is no such button
         *                                      in the form.
         *    @access public
         */
        function _submitButtonBySelector($selector, $additional) {
            foreach ($this->_buttons as $button) {
                if ($selector->isMatch($button)) {
                    $encoding = $this->_getEncoding();
                    $encoding->merge($button->getSubmitValues());
                    if ($additional) {
                        $encoding->merge($additional);
                    }
                    return $encoding;           
                }
            }
            return false;
        }
       
        /**
         *    Gets the submit values for a named button.
         *    @param string $name      Button label to search for.
         *    @param hash $additional  Additional data for the form.
         *    @return SimpleEncoding   Submitted values or false
         *                             if there is no such button in the
         *                             form.
         *    @access public
         */
        function submitButtonByName($name, $additional = false) {
            return $this->_submitButtonBySelector(
                    new SimpleNameSelector($name),
                    $additional);
        }
        
        /**
         *    Gets the submit values for a named button.
         *    @param string $label     Button label to search for.
         *    @param hash $additional  Additional data for the form.
         *    @return SimpleEncoding   Submitted values or false
         *                             if there is no such button in the
         *                             form.
         *    @access public
         */
        function submitButtonByLabel($label, $additional = false) {
            return $this->_submitButtonBySelector(
                    new SimpleLabelSelector($label),
                    $additional);
        }
        
        /**
         *    Gets the submit values for a button identified by the ID.
         *    @param string $id        Button ID attribute to search for.
         *    @param hash $additional  Additional data for the form.
         *    @return SimpleEncoding   Submitted values or false
         *                             if there is no such button in the
         *                             form.
         *    @access public
         */
        function submitButtonById($id, $additional = false) {
            return $this->_submitButtonBySelector(
                    new SimpleIdSelector($id),
                    $additional);
        }
         
        /**
         *    Gets the submit values for an image.
         *    @param SimpleSelector $selector   Criteria to apply.
         *    @param integer $x                 X-coordinate of click.
         *    @param integer $y                 Y-coordinate of click.
         *    @param hash $additional           Additional data for the form.
         *    @return SimpleEncoding            Submitted values or false
         *                                      if there is no such button in the
         *                                      form.
         *    @access public
         */
        function _submitImageBySelector($selector, $x, $y, $additional) {
            foreach ($this->_images as $image) {
                if ($selector->isMatch($image)) {
                    $encoding = $this->_getEncoding();
                    $encoding->merge($image->getSubmitValues($x, $y));
                    if ($additional) {
                        $encoding->merge($additional);
                    }
                    return $encoding;           
                }
            }
            return false;
        }
         
        /**
         *    Gets the submit values for an image identified by the alt
         *    tag or nearest equivalent.
         *    @param string $label     Button label to search for.
         *    @param integer $x        X-coordinate of click.
         *    @param integer $y        Y-coordinate of click.
         *    @param hash $additional  Additional data for the form.
         *    @return SimpleEncoding   Submitted values or false
         *                             if there is no such button in the
         *                             form.
         *    @access public
         */
        function submitImageByLabel($label, $x, $y, $additional = false) {
            return $this->_submitImageBySelector(
                    new SimpleLabelSelector($label),
                    $x,
                    $y,
                    $additional);
        }
         
        /**
         *    Gets the submit values for an image identified by the ID.
         *    @param string $name      Image name to search for.
         *    @param integer $x        X-coordinate of click.
         *    @param integer $y        Y-coordinate of click.
         *    @param hash $additional  Additional data for the form.
         *    @return SimpleEncoding   Submitted values or false
         *                             if there is no such button in the
         *                             form.
         *    @access public
         */
        function submitImageByName($name, $x, $y, $additional = false) {
            return $this->_submitImageBySelector(
                    new SimpleNameSelector($name),
                    $x,
                    $y,
                    $additional);
        }
          
        /**
         *    Gets the submit values for an image identified by the ID.
         *    @param string/integer $id  Button ID attribute to search for.
         *    @param integer $x          X-coordinate of click.
         *    @param integer $y          Y-coordinate of click.
         *    @param hash $additional    Additional data for the form.
         *    @return SimpleEncoding     Submitted values or false
         *                               if there is no such button in the
         *                               form.
         *    @access public
         */
        function submitImageById($id, $x, $y, $additional = false) {
            return $this->_submitImageBySelector(
                    new SimpleIdSelector($id),
                    $x,
                    $y,
                    $additional);
        }
      
        /**
         *    Simply submits the form without the submit button
         *    value. Used when there is only one button or it
         *    is unimportant.
         *    @return hash           Submitted values.
         *    @access public
         */
        function submit() {
            return $this->_getEncoding();
        }
    }
?>
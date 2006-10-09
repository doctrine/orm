<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.com>.
 */

/**
 * Doctrine_Validator_ErrorStack
 *
 * @author      Konsta Vesterinen
 * @author      Roman Borschel
 * @license     LGPL
 * @package     Doctrine
 */
class Doctrine_Validator_ErrorStack implements ArrayAccess, Countable, IteratorAggregate {
    
    /**
     * The errors of the error stack.
     *
     * @var array
     */
    protected $errors = array();

    /**
     * Constructor
     *
     */
    public function __construct()
    {}
    
    /**
     * Adds an error to the stack.
     *
     * @param string $invalidFieldName
     * @param string $errorType
     */
    public function add($invalidFieldName, $errorType = 'general') {
        $this->errors[$invalidFieldName][] = array('type' => $errorType);
    }
    
    /**
     * Removes all existing errors for the specified field from the stack.
     *
     * @param string $fieldName
     */
    public function remove($fieldName) {
        if (isset($this->errors[$fieldName])) {
            unset($this->errors[$fieldName]);
        }
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $name
     * @return unknown
     */
    public function get($name) {
        return $this[$name];
    }
    
    /** ArrayAccess implementation */
    
    /**
     * Gets all errors that occured for the specified field.
     *
     * @param string $offset
     * @return The array containing the errors or NULL if no errors were found.
     */
    public function offsetGet($offset) {
        return isset($this->errors[$offset]) ? $this->errors[$offset] : null;
    }
    
    /**
     * Enter description here...
     *
     * @param string $offset
     * @param mixed $value
     * @throws Doctrine_Validator_ErrorStack_Exception  Always thrown since this operation is not allowed. 
     */
    public function offsetSet($offset, $value) {
        throw new Doctrine_Validator_ErrorStack_Exception("Errors can only be added through
                Doctrine_Validator_ErrorStack::add()");
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $offset
     */
    public function offsetExists($offset) {
        return isset($this->errors[$offset]);
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $offset
     * @throws Doctrine_Validator_ErrorStack_Exception  Always thrown since this operation is not allowed. 
     */
    public function offsetUnset($offset) {
        throw new Doctrine_Validator_ErrorStack_Exception("Errors can only be removed
                through Doctrine_Validator_ErrorStack::remove()");
    }
    
    /**
     * Enter description here...
     *
     * @param unknown_type $stack
     */
    /*
    public function merge($stack) {
        if(is_array($stack)) {
            $this->errors = array_merge($this->errors, $stack);
        }
    }*/
    
    
    /** IteratorAggregate implementation */
    
    /**
     * Enter description here...
     *
     * @return unknown
     */
    public function getIterator() {
        return new ArrayIterator($this->errors);
    }
    
    
    /** Countable implementation */
    
    /**
     * Enter description here...
     *
     * @return unknown
     */
    public function count() {
        return count($this->errors);
    }
}

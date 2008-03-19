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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Configurable
 *
 *
 * @package     Doctrine
 * @subpackage  Configurable
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
abstract class Doctrine_Configurable
{
    /**
     * @var array $attributes               an array of containing all attributes
     */
    protected $_attributes = array();

    /**
     * @var Doctrine_Configurable $parent   the parent of this component
     */
    protected $parent;

    /**
     * @var array $_impl                    an array containing concrete implementations for class templates
     *                                      keys as template names and values as names of the concrete
     *                                      implementation classes
     */
    //protected $_impl = array();

    /**
     * @var array $_params                  an array of user defined parameters
     */
    //protected $_params = array();

    /**
     * setAttribute
     * sets a given attribute
     *
     * <code>
     * $manager->setAttribute(Doctrine::ATTR_PORTABILITY, Doctrine::PORTABILITY_ALL);
     *
     * // or
     *
     * $manager->setAttribute('portability', Doctrine::PORTABILITY_ALL);
     *
     * // or
     *
     * $manager->setAttribute('portability', 'all');
     * </code>
     *
     * @param mixed $attribute              either a Doctrine::ATTR_* integer constant or a string
     *                                      corresponding to a constant
     * @param mixed $value                  the value of the attribute
     * @see Doctrine::ATTR_* constants
     * @throws Doctrine_Exception           if the value is invalid
     * @return void
     */
    public function setAttribute($attribute, $value)
    {
        if (is_string($attribute)) {
            $upper = strtoupper($attribute);

            $const = 'Doctrine::ATTR_' . $upper;

            if (defined($const)) {
                $attribute = constant($const);
            } else {
                throw new Doctrine_Exception('Unknown attribute: "' . $attribute . '"');
            }
        }

        if (is_string($value) && isset($upper)) {
            $const = 'Doctrine::' . $upper . '_' . strtoupper($value);

            if (defined($const)) {
                $value = constant($const);
            } else {
                throw new Doctrine_Exception('Unknown attribute value: "' . $value . '"');
            }
        }

        switch ($attribute) {
            case Doctrine::ATTR_FETCHMODE: // deprecated
                throw new Doctrine_Exception('Deprecated attribute. See http://www.phpdoctrine.org/documentation/manual?chapter=configuration');
            case Doctrine::ATTR_LISTENER:
                $this->setEventListener($value);
                break;
            case Doctrine::ATTR_COLL_KEY: // class attribute
                if ( ! ($this instanceof Doctrine_ClassMetadata)) {
                    throw new Doctrine_Exception("This attribute can only be set at class level.");
                }
                if ($value !== null && ! $this->hasField($value)) {
                    throw new Doctrine_Exception("Couldn't set collection key attribute. No such field '$value'");
                }
                break;
            case Doctrine::ATTR_CACHE: // deprecated
            case Doctrine::ATTR_RESULT_CACHE:// manager/session attribute
            case Doctrine::ATTR_QUERY_CACHE: // manager/session attribute
                if ($value !== null) {
                    if ( ! ($value instanceof Doctrine_Cache_Interface)) {
                        throw new Doctrine_Exception('Cache driver should implement Doctrine_Cache_Interface');
                    }
                }
                break;
            case Doctrine::ATTR_VALIDATE: // manager/session attribute
            case Doctrine::ATTR_QUERY_LIMIT: // manager/session attribute
            case Doctrine::ATTR_QUOTE_IDENTIFIER: // manager/session attribute
            case Doctrine::ATTR_PORTABILITY: // manager/session attribute
            case Doctrine::ATTR_DEFAULT_TABLE_TYPE: // manager/session attribute
            case Doctrine::ATTR_EMULATE_DATABASE: // manager/session attribute
            case Doctrine::ATTR_USE_NATIVE_ENUM: // manager/session attribute
            case Doctrine::ATTR_DEFAULT_SEQUENCE: // ??
            case Doctrine::ATTR_EXPORT: // manager/session attribute
            case Doctrine::ATTR_DECIMAL_PLACES: // manager/session attribute
            case Doctrine::ATTR_LOAD_REFERENCES: // class attribute
            case Doctrine::ATTR_RECORD_LISTENER: // not an attribute
            case Doctrine::ATTR_THROW_EXCEPTIONS: // manager/session attribute
            case Doctrine::ATTR_DEFAULT_PARAM_NAMESPACE:
            case Doctrine::ATTR_MODEL_LOADING: // manager/session attribute

                break;
            case Doctrine::ATTR_SEQCOL_NAME: // class attribute
                if ( ! is_string($value)) {
                    throw new Doctrine_Exception('Sequence column name attribute only accepts string values');
                }
                break;
            case Doctrine::ATTR_FIELD_CASE: // manager/session attribute
                if ($value != 0 && $value != CASE_LOWER && $value != CASE_UPPER)
                    throw new Doctrine_Exception('Field case attribute should be either 0, CASE_LOWER or CASE_UPPER constant.');
                break;
            case Doctrine::ATTR_SEQNAME_FORMAT: // manager/session attribute
            case Doctrine::ATTR_IDXNAME_FORMAT: // manager/session attribute
            case Doctrine::ATTR_TBLNAME_FORMAT: // manager/session attribute
                if ($this instanceof Doctrine_ClassMetadata) {
                    throw new Doctrine_Exception('Sequence / index name format attributes cannot be set'
                                               . ' at class level (only at connection or global level).');
                }
                break;
            default:
                throw new Doctrine_Exception("Unknown attribute.");
        }

        $this->_attributes[$attribute] = $value;

    }

    /*public function getParams($namespace = null)
    {
    	if ($namespace == null) {
    	    $namespace = $this->getAttribute(Doctrine::ATTR_DEFAULT_PARAM_NAMESPACE);
    	}

    	if ( ! isset($this->_params[$namespace])) {
    	    return null;
    	}

        return $this->_params[$namespace];
    }*/

    /*public function getParamNamespaces()
    {
        return array_keys($this->_params);
    }*/

    /*public function setParam($name, $value, $namespace = null)
    {
    	if ($namespace == null) {
    	    $namespace = $this->getAttribute(Doctrine::ATTR_DEFAULT_PARAM_NAMESPACE);
    	}

    	$this->_params[$namespace][$name] = $value;

    	return $this;
    }*/

    /*public function getParam($name, $value, $namespace)
    {
    	if ($namespace == null) {
    	    $namespace = $this->getAttribute(Doctrine::ATTR_DEFAULT_PARAM_NAMESPACE);
    	}

        if ( ! isset($this->_params[$name])) {
            if (isset($this->parent)) {
                return $this->parent->getParam($name);
            }
            return null;
        }
        return $this->_params[$name];
    }*/

    /**
     * setImpl
     * binds given class to given template name
     *
     * this method is the base of Doctrine dependency injection
     *
     * @param string $template      name of the class template
     * @param string $class         name of the class to be bound
     * @return Doctrine_Configurable    this object
     */
    /*public function setImpl($template, $class)
    {
        $this->_impl[$template] = $class;

        return $this;
    }*/

    /**
     * getImpl
     * returns the implementation for given class
     *
     * @return string   name of the concrete implementation
     */
    /*public function getImpl($template)
    {
        if ( ! isset($this->_impl[$template])) {
            if (isset($this->parent)) {
                return $this->parent->getImpl($template);
            }
            return null;
        }
        return $this->_impl[$template];
    }*/


    /*public function hasImpl($template)
    {
        if ( ! isset($this->_impl[$template])) {
            if (isset($this->parent)) {
                return $this->parent->hasImpl($template);
            }
            return false;
        }
        return true;
    }*/

    /**
     * @param Doctrine_EventListener $listener
     * @return void
     */
    public function setEventListener($listener)
    {
        return $this->setListener($listener);
    }

    /**
     * addRecordListener
     *
     * @param Doctrine_EventListener_Interface|Doctrine_Overloadable $listener
     * @return mixed        this object
     */
    public function addRecordListener($listener, $name = null)
    {
        if ( ! isset($this->_attributes[Doctrine::ATTR_RECORD_LISTENER]) ||
             ! ($this->_attributes[Doctrine::ATTR_RECORD_LISTENER] instanceof Doctrine_Record_Listener_Chain)) {

            $this->_attributes[Doctrine::ATTR_RECORD_LISTENER] = new Doctrine_Record_Listener_Chain();
        }
        $this->_attributes[Doctrine::ATTR_RECORD_LISTENER]->add($listener, $name);

        return $this;
    }

    /**
     * getListener
     *
     * @return Doctrine_EventListener_Interface|Doctrine_Overloadable
     */
    public function getRecordListener()
    {
        if ( ! isset($this->_attributes[Doctrine::ATTR_RECORD_LISTENER])) {
            if (isset($this->parent)) {
                return $this->parent->getRecordListener();
            }
            $this->_attributes[Doctrine::ATTR_RECORD_LISTENER] = new Doctrine_Record_Listener();
        }
        return $this->_attributes[Doctrine::ATTR_RECORD_LISTENER];
    }

    /**
     * setListener
     *
     * @param Doctrine_EventListener_Interface|Doctrine_Overloadable $listener
     * @return Doctrine_Configurable        this object
     */
    public function setRecordListener($listener)
    {
        if ( ! ($listener instanceof Doctrine_Record_Listener_Interface)
            && ! ($listener instanceof Doctrine_Overloadable)
        ) {
            throw new Doctrine_Exception("Couldn't set eventlistener. Record listeners should implement either Doctrine_Record_Listener_Interface or Doctrine_Overloadable");
        }
        $this->_attributes[Doctrine::ATTR_RECORD_LISTENER] = $listener;

        return $this;
    }
    
    public function removeRecordListeners()
    {
        $this->_attributes[Doctrine::ATTR_RECORD_LISTENER] = null;
    }

    /**
     * addListener
     *
     * @param Doctrine_EventListener_Interface|Doctrine_Overloadable $listener
     * @return mixed        this object
     */
    public function addListener($listener, $name = null)
    {
        if ( ! isset($this->_attributes[Doctrine::ATTR_LISTENER]) ||
             ! ($this->_attributes[Doctrine::ATTR_LISTENER] instanceof Doctrine_EventListener_Chain)) {

            $this->_attributes[Doctrine::ATTR_LISTENER] = new Doctrine_EventListener_Chain();
        }
        $this->_attributes[Doctrine::ATTR_LISTENER]->add($listener, $name);

        return $this;
    }

    /**
     * getListener
     *
     * @return Doctrine_EventListener_Interface|Doctrine_Overloadable
     */
    public function getListener()
    {
        if ( ! isset($this->_attributes[Doctrine::ATTR_LISTENER])) {
            if (isset($this->parent)) {
                return $this->parent->getListener();
            }
            return null;
        }
        return $this->_attributes[Doctrine::ATTR_LISTENER];
    }

    /**
     * setListener
     *
     * @param Doctrine_EventListener_Interface|Doctrine_Overloadable $listener
     * @return Doctrine_Configurable        this object
     */
    public function setListener($listener)
    {
        if ( ! ($listener instanceof Doctrine_EventListener_Interface)
                && ! ($listener instanceof Doctrine_Overloadable)) {
            throw new Doctrine_EventListener_Exception("Couldn't set eventlistener. EventListeners should implement either Doctrine_EventListener_Interface or Doctrine_Overloadable");
        }
        $this->_attributes[Doctrine::ATTR_LISTENER] = $listener;

        return $this;
    }

    /**
     * returns the value of an attribute
     *
     * @param integer $attribute
     * @return mixed
     */
    public function getAttribute($attribute)
    {
        if (is_string($attribute)) {
            $upper = strtoupper($attribute);

            $const = 'Doctrine::ATTR_' . $upper; 

            if (defined($const)) {
                $attribute = constant($const);
            } else {
                throw new Doctrine_Exception('Unknown attribute: "' . $attribute . '"');
            }
        }

        $attribute = (int) $attribute;

        if ($attribute < 0) {
            throw new Doctrine_Exception('Unknown attribute.');
        }

        if (isset($this->_attributes[$attribute])) {
            return $this->_attributes[$attribute];
        }

        if (isset($this->parent)) {
            return $this->parent->getAttribute($attribute);
        }
        return null;
    }

    /**
     * getAttributes
     * returns all attributes as an array
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->_attributes;
    }

    /**
     * Sets a parent for this configurable component
     * the parent must be a configurable component itself.
     *
     * @param Doctrine_Configurable $component
     * @return void
     */
    public function setConfigurableParent(Doctrine_Configurable $component)
    {
        $this->parent = $component;
    }

    /**
     * getParent
     * Returns the parent of this component.
     *
     * @return Doctrine_Configurable
     */
    public function getParent()
    {
        return $this->parent;
    }
}

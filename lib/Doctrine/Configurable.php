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
 * Doctrine_Configurable
 * the base for Doctrine_Table, Doctrine_Manager and Doctrine_Connection
 *
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
abstract class Doctrine_Configurable {

    /**
     * @var array $attributes               an array of containing all attributes
     */
    private $attributes = array();
    /**
     * @var $parent                         the parents of this component
     */
    private $parent;
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
     * </code>
     *
     * @param mixed $attribute              either a Doctrine::ATTR_* integer constant or a string
     *                                      corresponding to a constant
     * @param mixed $value                  the value of the attribute
     * @see Doctrine::ATTR_* constants
     * @throws Doctrine_Exception           if the value is invalid
     * @return void
     */
    public function setAttribute($attribute,$value) {
        switch($attribute):
            case Doctrine::ATTR_BATCH_SIZE:
                if($value < 0)
                    throw new Doctrine_Exception("Batch size should be greater than or equal to zero");
            break;

            case Doctrine::ATTR_FETCHMODE:
                 if($value < 0)
                    throw new Doctrine_Exception("Unknown fetchmode. See Doctrine::FETCH_* constants.");
            break;
            case Doctrine::ATTR_LISTENER:
                $this->setEventListener($value);
            break;
            case Doctrine::ATTR_LOCKMODE:
                if($this instanceof Doctrine_Connection) {
                    if($this->transaction->getState() != Doctrine_Connection_Transaction::STATE_OPEN)
                        throw new Doctrine_Exception("Couldn't set lockmode. There are transactions open.");

                } elseif($this instanceof Doctrine_Manager) {
                    foreach($this as $connection) {
                        if($connection->transaction->getState() != Doctrine_Connection_Transaction::STATE_OPEN)
                            throw new Doctrine_Exception("Couldn't set lockmode. There are transactions open.");
                    }
                } else {
                    throw new Doctrine_Exception("Lockmode attribute can only be set at the global or connection level.");
                }
            break;
            case Doctrine::ATTR_CREATE_TABLES:
                $value = (bool) $value;
            break;
            case Doctrine::ATTR_ACCESSORS:
                $accessors = array('none','get','set','both');

               // if( ! in_array($value,$accessors))
               //     throw new Doctrine_Exception();

            break;
            case Doctrine::ATTR_COLL_LIMIT:
                if($value < 1) {
                    throw new Doctrine_Exception("Collection limit should be a value greater than or equal to 1.");
                }
            break;
            case Doctrine::ATTR_COLL_KEY:
                if( ! ($this instanceof Doctrine_Table)) 
                    throw new Doctrine_Exception("This attribute can only be set at table level.");

                if( ! $this->hasColumn($value)) 
                    throw new Doctrine_Exception("Couldn't set collection key attribute. No such column '$value'");
                    

            break;
            case Doctrine::ATTR_VLD:
            case Doctrine::ATTR_AUTO_LENGTH_VLD:
            case Doctrine::ATTR_AUTO_TYPE_VLD:
            case Doctrine::ATTR_QUERY_LIMIT:

            break;
            case Doctrine::ATTR_CACHE:
                if($value != Doctrine::CACHE_SQLITE && $value != Doctrine::CACHE_NONE)
                    throw new Doctrine_Exception("Unknown cache container. See Doctrine::CACHE_* constants for availible containers.");
            break;
            default:
                throw new Doctrine_Exception("Unknown attribute.");
        endswitch;

        $this->attributes[$attribute] = $value;

    }
    /**
     * @param Doctrine_EventListener $listener
     * @return void
     */
    public function setEventListener($listener) {
        return $this->setListener($listener);
    }
    /**
     * addListener
     *
     * @param Doctrine_Db_EventListener_Interface|Doctrine_Overloadable $listener
     * @return Doctrine_Db
     */
    public function addListener($listener, $name = null) {
        if( ! ($this->attributes[Doctrine::ATTR_LISTENER] instanceof Doctrine_EventListener_Chain))
            $this->attributes[Doctrine::ATTR_LISTENER] = new Doctrine_EventListener_Chain();

        $this->attributes[Doctrine::ATTR_LISTENER]->add($listener, $name);
        
        return $this;
    }
    /**
     * getListener
     * 
     * @return Doctrine_Db_EventListener_Interface|Doctrine_Overloadable
     */
    public function getListener() {
        if( ! isset($this->attributes[Doctrine::ATTR_LISTENER])) {
            if(isset($this->parent))
                return $this->parent->getListener();
                
            return null;
        }
        return $this->attributes[Doctrine::ATTR_LISTENER];
    }
    /**
     * setListener
     *
     * @param Doctrine_Db_EventListener_Interface|Doctrine_Overloadable $listener
     * @return Doctrine_Db
     */
    public function setListener($listener) {
        if( ! ($listener instanceof Doctrine_EventListener_Interface) &&
            ! ($listener instanceof Doctrine_Overloadable))
            throw new Doctrine_Exception("Couldn't set eventlistener. EventListeners should implement either Doctrine_EventListener_Interface or Doctrine_Overloadable");

        $this->attributes[Doctrine::ATTR_LISTENER] = $listener;

        return $this;
    }
    /**
     * returns the value of an attribute
     *
     * @param integer $attribute
     * @return mixed
     */
    public function getAttribute($attribute) {
        $attribute = (int) $attribute;

        if($attribute < 1 || $attribute > 21)
            throw new Doctrine_Exception('Unknown attribute.');

        if( ! isset($this->attributes[$attribute])) {
            if(isset($this->parent))
                return $this->parent->getAttribute($attribute);

            return null;
        }
        return $this->attributes[$attribute];
    }
    /**
     * getAttributes
     * returns all attributes as an array
     *
     * @return array
     */
    public function getAttributes() {
        return $this->attributes;
    }
    /**
     * sets a parent for this configurable component
     * the parent must be configurable component itself
     *
     * @param Doctrine_Configurable $component
     * @return void
     */
    public function setParent(Doctrine_Configurable $component) {
        $this->parent = $component;
    }
    /**
     * getParent
     * returns the parent of this component
     *
     * @return Doctrine_Configurable
     */
    public function getParent() {
        return $this->parent;
    }
}


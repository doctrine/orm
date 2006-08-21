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
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
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
     * sets a given attribute
     *
     * @throws Doctrine_Exception           if the value is invalid
     * @param integer $attribute
     * @param mixed $value
     * @return void
     */
    final public function setAttribute($attribute,$value) {
        switch($attribute):
            case Doctrine::ATTR_BATCH_SIZE:
                if($value < 0)
                    throw new Doctrine_Exception("Batch size should be greater than or equal to zero");
            break;
            case Doctrine::ATTR_CACHE_DIR:
                if(substr(trim($value),0,6) == "%ROOT%") {
                    $dir   = dirname(__FILE__);
                    $value = $dir.substr($value,6);
                }

                if(! is_dir($value) && ! file_exists($value))
                    mkdir($value,0777);
            break;
            case Doctrine::ATTR_CACHE_TTL:
                if($value < 1)
                    throw new Doctrine_Exception("Cache TimeToLive should be greater than or equal to 1");
            break;
            case Doctrine::ATTR_CACHE_SIZE:
                if($value < 1)
                    throw new Doctrine_Exception("Cache size should be greater than or equal to 1");
            break;
            case Doctrine::ATTR_CACHE_SLAM:
                if($value < 0 || $value > 1) 
                    throw new Doctrine_Exception("Cache slam defense should be a floating point number between 0 and 1");
            break;
            case Doctrine::ATTR_FETCHMODE:
                 if($value < 0)
                    throw new Doctrine_Exception("Unknown fetchmode. See Doctrine::FETCH_* constants.");
            break;
            case Doctrine::ATTR_LISTENER:
                $this->setEventListener($value);
            break;
            case Doctrine::ATTR_PK_COLUMNS:
                if( ! is_array($value)) 
                    throw new Doctrine_Exception("The value of Doctrine::ATTR_PK_COLUMNS attribute must be an array");
            break;
            case Doctrine::ATTR_PK_TYPE:
                if($value != Doctrine::INCREMENT_KEY && $value != Doctrine::UNIQUE_KEY)
                    throw new Doctrine_Exception("The value of Doctrine::ATTR_PK_TYPE attribute must be either Doctrine::INCREMENT_KEY or Doctrine::UNIQUE_KEY");

            break;
            case Doctrine::ATTR_LOCKMODE:
                if($this instanceof Doctrine_Connection) {
                    if($this->getState() != Doctrine_Connection::STATE_OPEN)
                        throw new Doctrine_Exception("Couldn't set lockmode. There are transactions open.");

                } elseif($this instanceof Doctrine_Manager) {
                    foreach($this as $connection) {
                        if($connection->getState() != Doctrine_Connection::STATE_OPEN)
                            throw new Doctrine_Exception("Couldn't set lockmode. There are transactions open.");
                    }
                } else {
                    throw new Doctrine_Exception("Lockmode attribute can only be set at the global or connection level.");
                }
            break;
            case Doctrine::ATTR_CREATE_TABLES:
                $value = (bool) $value;
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
    final public function setEventListener($listener) {
        $i = Doctrine::ATTR_LISTENER;
        if( ! ($listener instanceof Doctrine_EventListener) && 
            ! ($listener instanceof Doctrine_EventListener_Chain))
            throw new Doctrine_Exception("EventListener must extend Doctrine_EventListener or Doctrine_EventListener_Chain");

        $this->attributes[$i] = $listener;
    }
    /**
     * returns the value of an attribute
     *
     * @param integer $attribute
     * @return mixed
     */
    final public function getAttribute($attribute) {
        $attribute = (int) $attribute;

        if($attribute < 1 || $attribute > 16)
            throw new InvalidKeyException();

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
    final public function getAttributes() {
        return $this->attributes;
    }
    /**
     * sets a parent for this configurable component
     * the parent must be configurable component itself
     *
     * @param Doctrine_Configurable $component
     * @return void
     */
    final public function setParent(Doctrine_Configurable $component) {
        $this->parent = $component;
    }
    /**
     * getParent
     * returns the parent of this component
     *
     * @return Doctrine_Configurable
     */
    final public function getParent() {
        return $this->parent;
    }
}
?>

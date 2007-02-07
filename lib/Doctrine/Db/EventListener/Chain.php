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
Doctrine::autoload('Doctrine_Access');

/**
 * Doctrine_Db_EventListener
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Db_EventListener_Chain extends Doctrine_Access implements Doctrine_Overloadable
{
    private $listeners = array();
    /**
     * add
     * adds a listener to the chain of listeners
     *
     * @param object $listener
     * @param string $name
     * @return void
     */
    public function add($listener, $name = null)
    {
        if ( ! ($listener instanceof Doctrine_Db_EventListener_Interface)
            && ! ($listener instanceof Doctrine_Overloadable)
        ) {
            throw new Doctrine_Db_Exception("Couldn't add eventlistener. EventListeners should implement either Doctrine_Db_EventListener_Interface or Doctrine_Overloadable");
        }
        if ($name === null) {
            $this->listeners[] = $listener;
        } else {
            $this->listeners[$name] = $listener;
        }
    }

    public function get($name)
    {
        if ( ! isset($this->listeners[$name])) {
            throw new Doctrine_Db_Exception("Unknown listener $name");
        }
        return $this->listeners[$name];
    }

    public function set($name, $listener)
    {
        if ( ! ($listener instanceof Doctrine_Db_EventListener_Interface)
            && ! ($listener instanceof Doctrine_Overloadable)
        ) {
            throw new Doctrine_Db_Exception("Couldn't set eventlistener. EventListeners should implement either Doctrine_Db_EventListener_Interface or Doctrine_Overloadable");
        }
        $this->listeners[$name] = $listener;
    }
    /**
     * method overloader
     * delegates the event listening to the listener chain
     *
     * if listener returns a value that is not null it will be assigned as the
     * chain return value
     *
     * @return mixed
     */
    public function __call($m, $a) 
    {
    	$return = null;

        foreach ($this->listeners as $listener) {
            $tmp = $listener->$m($a[0]);
        
            if ($tmp !== null) {
                $return = $tmp;
            }
        }
        return $return;
    }
}

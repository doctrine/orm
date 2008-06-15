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

#namespace Doctrine::Common;

class Doctrine_EventManager
{
    private $_listeners = array();


    public function dispatchEvent($event) {
        $argIsCallback = is_string($event);
        $callback = $argIsCallback ? $event : $event->getType();

        if (isset($this->_listeners[$callback])) {
            $event = $argIsCallback ? new Doctrine_Event($event) : $event;
            foreach ($this->_listeners[$callback] as $listener) {
                $listener->$callback($event);
            }
        }

        return ! $event->getDefaultPrevented();
    }


    public function getListeners($callback = null) {
        return $callback ? $this->_listeners[$callback] : $this->_listeners;
    }


    public function hasListeners($callback) {
        return isset($this->_listeners[$callback]);
    }


    public function addEventListener($callbacks, $listener) {
        // TODO: maybe check for duplicate registrations?
        if ( ! is_array($callbacks)) {
            $callbacks = array($callbacks);
        }

        foreach ($callbacks as $callback) {
            $this->_listeners[$callback] = $listener;
        }
    }
}

?>
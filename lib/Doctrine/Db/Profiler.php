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
Doctrine::autoload('Doctrine_Overloadable');
/**
 * Doctrine_Db_Profiler
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Db_Profiler implements Doctrine_Overloadable {
    /**
     * @param array $listeners      an array containing all availible listeners
     */
    private $listeners  = array('query',
                                'prepare',
                                'commit',
                                'rollback',
                                'begintransaction',
                                'exec',
                                'execute',
                                );
    /**
     * @param array $events         an array containing all listened events
     */
    private $events     = array();
    /**
     * method overloader
     * this method is used for invoking different listeners, for the full
     * list of availible listeners, see Doctrine_Db_EventListener
     *
     * @param string $m     the name of the method
     * @param array $a      method arguments
     * @see Doctrine_Db_EventListener
     * @return void
     */
    public function __call($m, $a) {
        // first argument should be an instance of Doctrine_Db_Event
        if ( ! ($a[0] instanceof Doctrine_Db_Event)) {
            throw new Doctrine_Db_Profiler_Exception("Couldn't listen event. Event should be an instance of Doctrine_Db_Event.");
        }

        // event methods should start with 'on'
        if (substr($m, 0, 2) !== 'on') {
            throw new Doctrine_Db_Profiler_Exception("Couldn't invoke listener $m.");
        }
        if (substr($m, 2, 3) === 'Pre' && in_array(strtolower(substr($m, 3)), $this->listeners)) {
            // pre-event listener found
            $a[0]->start();
        } else {
            // after-event listener found
            $a[0]->end();
        }

        $this->events[] = $a[0];
    }

    /**
     * Get the Doctrine_Db_Event object for the last query that was run, regardless if it has
     * ended or not. If the event has not ended, it's end time will be Null.
     *
     * @return Doctrine_Db_Event
     */
    public function lastEvent() {
        if (empty($this->events)) {
            return false;
        }

        end($this->events);
        return current($this->events);
    }
}

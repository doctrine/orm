<?php
/*
 *  $Id: Log.php 3155 2007-11-14 13:13:23Z ppetermann $
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
 * @package     Doctrine
 * @subpackage  Log
 * @author      Jonathan H. Wage <jwage@mac.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision: 3155 $
 */
class Doctrine_Log
{
    const EMERG   = 0;  // Emergency: system is unusable
    const ALERT   = 1;  // Alert: action must be taken immediately
    const CRIT    = 2;  // Critical: critical conditions
    const ERR     = 3;  // Error: error conditions
    const WARN    = 4;  // Warning: warning conditions
    const NOTICE  = 5;  // Notice: normal but significant condition
    const INFO    = 6;  // Informational: informational messages
    const DEBUG   = 7;  // Debug: debug messages

    /**
     * @var array of priorities where the keys are the
     * priority numbers and the values are the priority names
     */
    protected $_priorities = array();

    /**
     * @var array of Doctrine_Log_Writer_Abstract
     */
    protected $_writers = array();

    /**
     * @var array of Doctrine_Log_Filter_Interface
     */
    protected $_filters = array();

    /**
     * @var array of extra log event
     */
    protected $_extras = array();

    /**
     * Class constructor.  Create a new logger
     *
     * @param Doctrine_Log_Writer_Abstract|null  $writer  default writer
     */
    public function __construct($writer = null)
    {
        $r = new ReflectionClass($this);
        $this->_priorities = array_flip($r->getConstants());

        if ($writer !== null) {
            $this->addWriter($writer);
        }
    }

    /**
     * Class destructor.  Shutdown log writers
     *
     * @return void
     */
    public function __destruct()
    {
        foreach($this->_writers as $writer) {
            $writer->shutdown();
        }
    }

    /**
     * Undefined method handler allows a shortcut:
     *   $log->priorityName('message')
     *     instead of
     *   $log->log('message', Doctrine_Log::PRIORITY_NAME)
     *
     * @param  string  $method  priority name
     * @param  string  $params  message to log
     * @return void
     * @throws Doctrine_Log_Exception
     */
    public function __call($method, $params)
    {
        $priority = strtoupper($method);
        if (($priority = array_search($priority, $this->_priorities)) !== false) {
            $this->log(array_shift($params), $priority);
        } else {
            throw new Doctrine_Log_Exception('Bad log priority');
        }
    }

    /**
     * Log a message at a priority
     *
     * @param  string   $message   Message to log
     * @param  integer  $priority  Priority of message
     * @return void
     * @throws Doctrine_Log_Exception
     */
    public function log($message, $priority)
    {
        // sanity checks
        if (empty($this->_writers)) {
            throw new Doctrine_Log_Exception('No writers were added');
        }

        if (! isset($this->_priorities[$priority])) {
            throw new Doctrine_Log_Exception('Bad log priority');
        }

        // pack into event required by filters and writers
        $event = array_merge(array('timestamp'    => date('c'),
                                    'message'      => $message,
                                    'priority'     => $priority,
                                    'priorityName' => $this->_priorities[$priority]),
                              $this->_extras);

        // abort if rejected by the global filters
        foreach ($this->_filters as $filter) {
            if (! $filter->accept($event)) {
                return;
            }
        }

        // send to each writer
        foreach ($this->_writers as $writer) {
            $writer->write($event);
        }
    }

    /**
     * Add a custom priority
     *
     * @param  string   $name      Name of priority
     * @param  integer  $priority  Numeric priority
     * @throws Doctrine_Log_InvalidArgumentException
     */
    public function addPriority($name, $priority)
    {
        // Priority names must be uppercase for predictability.
        $name = strtoupper($name);

        if (isset($this->_priorities[$priority])
            || array_search($name, $this->_priorities)) {
            throw new Doctrine_Log_Exception('Existing priorities cannot be overwritten');
        }

        $this->_priorities[$priority] = $name;
    }

    /**
     * Add a filter that will be applied before all log writers.
     * Before a message will be received by any of the writers, it
     * must be accepted by all filters added with this method.
     *
     * @param  Doctrine_Log_Filter_Interface  $filter
     * @return void
     */
    public function addFilter($filter)
    {
        if (is_integer($filter)) {
            $filter = new Doctrine_Log_Filter_Priority($filter);
        }

        $this->_filters[] = $filter;
    }

    /**
     * Add a writer.  A writer is responsible for taking a log
     * message and writing it out to storage.
     *
     * @param  Doctrine_Log_Writer_Abstract $writer
     * @return void
     */
    public function addWriter($writer)
    {
        $this->_writers[] = $writer;
    }

    /**
     * Set an extra item to pass to the log writers.
     *
     * @param  $name    Name of the field
     * @param  $value   Value of the field
     * @return void
     */
    public function setEventItem($name, $value) {
        $this->_extras = array_merge($this->_extras, array($name => $value));
    }

}

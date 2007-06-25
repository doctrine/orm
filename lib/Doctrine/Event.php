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
 * Doctrine_Event
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Event
{
    /**
     * EVENT CODE CONSTANTS
     */
    const QUERY     = 1;
    const EXEC      = 2;
    const EXECUTE   = 3;
    const PREPARE   = 4;
    const BEGIN     = 5;
    const COMMIT    = 6;
    const ROLLBACK  = 7;
    const CONNECT   = 8;
    const FETCH     = 9;
    const FETCHALL  = 10;
    
    const DELETE    = 11;
    const SAVE      = 12;
    const UPDATE    = 13;
    const INSERT    = 14;
    /**
     * @var mixed $_invoker             the handler which invoked this event
     */
    protected $_invoker;
    /**
     * @var string $_query              the sql query associated with this event (if any)
     */
    protected $_query;
    /**
     * @var string $_params             the parameters associated with the query (if any)
     */
    protected $_params;
    /**
     * @see Doctrine_Event constants
     * @var integer $_code              the event code
     */
    protected $_code;
    /**
     * @var integer $_startedMicrotime  the time point in which this event was started
     */
    protected $_startedMicrotime;
    /**
     * @var integer $_endedMicrotime    the time point in which this event was ended
     */
    protected $_endedMicrotime;
    /**
     * @var array $_options             an array of options
     */
    protected $_options = array();
    /**
     * constructor
     *
     * @param Doctrine_Db $invoker      the handler which invoked this event
     * @param integer $code             the event code
     * @param string $query             the sql query associated with this event (if any)
     */
    public function __construct($invoker, $code, $query = null, $params = array())
    {
        $this->_invoker = $invoker;
        $this->_code    = $code;
        $this->_query   = $query;
        $this->_params  = $params;
    }
    /**
     * getQuery
     *
     * @return string       returns the query associated with this event (if any)
     */
    public function getQuery()
    {
        return $this->_query;
    }
    /**
     * getName
     * returns the name of this event
     *
     * @return string       the name of this event
     */
    public function getName() 
    {
        switch ($this->_code) {
            case self::QUERY:
                return 'query';
            case self::EXEC:
                return 'exec';
            case self::EXECUTE:
                return 'execute';
            case self::PREPARE:
                return 'prepare';
            case self::BEGIN:
                return 'begin';
            case self::COMMIT:
                return 'commit';
            case self::ROLLBACK:
                return 'rollback';
            case self::CONNECT:
                return 'connect';
        }
    }
    /**
     * getCode
     *
     * @return integer      returns the code associated with this event
     */
    public function getCode()
    {
        return $this->_code;
    }
    /**
     * getOption
     * returns the value of an option
     *
     * @param string $option    the name of the option
     * @return mixed
     */
    public function getOption($option)
    {
        if ( ! isset($this->_options[$option])) {
            return null;
        }
        
        return $this->_options[$option];
    }
    /**
     * skipOperation
     * skips the next operation
     * an alias for setOption('skipOperation', true)
     *
     * @return Doctrine_Event   this object
     */
    public function skipOperation()
    {
        return $this->setOption('skipOperation', true);
    }
    /**
     * setOption
     * sets the value of an option
     *
     * @param string $option    the name of the option
     * @param mixed $value      the value of the given option
     * @return Doctrine_Event   this object
     */
    public function setOption($option, $value)
    {
        $this->_options[$option] = $value;

        return $this;
    }
    /**
     * start
     * starts the internal timer of this event
     *
     * @return Doctrine_Event   this object
     */
    public function start()
    {
        $this->_startedMicrotime = microtime(true);
    }
    /**
     * hasEnded
     * whether or not this event has ended
     *
     * @return boolean
     */
    public function hasEnded()
    {
        return ($this->_endedMicrotime != null);
    }
    /**
     * end
     * ends the internal timer of this event
     *
     * @return Doctrine_Event   this object
     */
    public function end()
    {
        $this->_endedMicrotime = microtime(true);
        
        return $this;
    }
    /**
     * getInvoker
     * returns the handler that invoked this event
     *
     * @return Doctrine_Db   the handler that invoked this event
     */
    public function getInvoker()
    {
        return $this->_invoker;
    }
    /**
     * getParams
     * returns the parameters of the query
     *
     * @return array   parameters of the query
     */
    public function getParams()
    {
        return $this->_params;
    }
    /**
     * Get the elapsed time (in microseconds) that the event ran.  If the event has
     * not yet ended, return false.
     *
     * @return mixed
     */
    public function getElapsedSecs()
    {
        if (is_null($this->_endedMicrotime)) {
            return false;
        }
        return ($this->_endedMicrotime - $this->_startedMicrotime);
    }
}

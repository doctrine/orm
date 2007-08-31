<?php
/*
 *  $Id: Cache.php 1857 2007-06-26 22:30:23Z subzero2000 $
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
Doctrine::autoload('Doctrine_EventListener');
/**
 * Doctrine_Cache
 *
 * @package     Doctrine
 * @subpackage  Doctrine_Cache
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1857 $
 */
class Doctrine_Cache extends Doctrine_EventListener implements Countable, IteratorAggregate
{
    /**
     * @var array $_options                         an array of general caching options
     */
    protected $_options = array('size'                  => 1000,
                                'lifeTime'              => 3600,
                                'addStatsPropability'   => 0.25,
                                'savePropability'       => 0.10,
                                'cleanPropability'      => 0.01,
                                'statsFile'             => '../data/stats.cache',
                                );
    /**
     * @var array $_queries                         query stack
     */
    protected $_queries = array();
    /**
     * @var Doctrine_Cache_Interface $_driver       the cache driver object
     */
    protected $_driver;
    /**
     * @var array $data                             current cache data array
     */
    protected $_data = array();
    /**
     * @var boolean $success                        the success of last operation
     */
    protected $_success = false;
    /**
     * constructor
     *
     * @param Doctrine_Cache_Interface|string $driver       cache driver name or a driver object
     * @param array $options                                cache driver options
     */
    public function __construct($driver, $options = array())
    {
    	if (is_object($driver)) {
    	   if ( ! ($driver instanceof Doctrine_Cache_Interface)) {
    	       throw new Doctrine_Cache_Exception('Driver should implement Doctrine_Cache_Interface.');
    	   }
    	   
    	   $this->_driver = $driver;
    	   $this->_driver->setOptions($options);
        } else {
            $class = 'Doctrine_Cache_' . ucwords(strtolower($driver));
    
            if ( ! class_exists($class)) {
                throw new Doctrine_Cache_Exception('Cache driver ' . $driver . ' could not be found.');
            }
    
            $this->_driver = new $class($options);
        }
    }
    /**
     * getDriver
     * returns the current cache driver
     *
     * @return Doctrine_Cache_Driver
     */
    public function getDriver()
    {
        return $this->_driver;
    }
    /**
     * setOption
     *
     * @param mixed $option     the option name
     * @param mixed $value      option value
     * @return boolean          TRUE on success, FALSE on failure
     */
    public function setOption($option, $value)
    {
    	// sanity check (we need this since we are using isset() instead of array_key_exists())
    	if ($value === null) {
            throw new Doctrine_Cache_Exception('Null values not accepted for options.');
    	}

    	if (isset($this->_options[$option])) {
            $this->_options[$option] = $value;
            return true;
        }
        return false;
    }
    /**
     * getOption
     * 
     * @param mixed $option     the option name
     * @return mixed            option value
     */
    public function getOption($option)
    {
        if ( ! isset($this->_options[$option])) {
            throw new Doctrine_Cache_Exception('Unknown option ' . $option);
        }

        return $this->_options[$option];
    }
    /**
     * add
     * adds a query to internal query stack
     *
     * @param string|array $query           sql query string
     * @param string $namespace             connection namespace
     * @return void
     */
    public function add($query, $namespace = null)
    {
    	if (isset($namespace)) {
            $this->_queries[$namespace][] = $query;
        } else {
            $this->_queries[] = $query;
        }
    }
    /**
     * getQueries
     *
     * @param string $namespace     optional query namespace
     * @return array                an array of sql query strings
     */
    public function getAll($namespace = null)
    {
        if (isset($namespace)) {
            if( ! isset($this->_queries[$namespace])) {
                return array();
            }

            return $this->_queries[$namespace];
        }
        
        return $this->_queries;
    }
    /**
     * pop
     *
     * pops a query from the stack
     * @return string
     */
    public function pop()
    {
        return array_pop($this->_queries);
    }
    /**
     * reset
     *
     * removes all queries from the query stack
     * @return void
     */
    public function reset()
    {
        $this->_queries = array();
    }
    /**
     * count
     *
     * @return integer          the number of queries in the stack
     */
    public function count() 
    {
        return count($this->_queries);
    }
    /**
     * getIterator
     *
     * @return ArrayIterator    an iterator that iterates through the query stack
     */
    public function getIterator()
    {
        return new ArrayIterator($this->_queries);
    }
    /**
     * @return boolean          whether or not the last cache operation was successful
     */
    public function isSuccessful() 
    {
        return $this->_success;
    }
    /**
     * save
     *
     * @return boolean
     */
    public function clean()
    {
        $rand = (mt_rand() / mt_getrandmax());

    	if ($rand <= $this->_options['cleanPropability']) {
            $queries = $this->readStats();

            $stats   = array();
    
            foreach ($queries as $query) {
                if (isset($stats[$query])) {
                    $stats[$query]++;
                } else {
                    $stats[$query] = 1;
                }
            }
            sort($stats);
    
            $i = $this->_options['size'];
    
            while ($i--) {
                $element = next($stats);
                $query   = key($stats);

                $hash = md5($query);

                $this->_driver->delete($hash);
            }
        }
    }
    /**
     * readStats
     *
     * @return array
     */
    public function readStats() 
    {
    	if ($this->_options['statsFile'] !== false) {
    	   $content = file_get_contents($this->_options['statsFile']);
           
           $e = explode("\n", $content);
           
           return array_map('unserialize', $e);
    	}
    	return array();
    }
    /**
     * appendStats
     *
     * adds all queries to stats file
     * @return void
     */
    public function appendStats()
    {
    	if ($this->_options['statsFile'] !== false) {

            if ( ! file_exists($this->_options['statsFile'])) {
                throw new Doctrine_Cache_Exception("Couldn't save cache statistics. Cache statistics file doesn't exists!");
            }
            
            $rand = (mt_rand() / mt_getrandmax());

            if ($rand <= $this->_options['addStatsPropability']) {
                file_put_contents($this->_options['statsFile'], implode("\n", array_map('serialize', $this->_queries)));
            }
        }
    }
    /**
     * preQuery
     * listens on the Doctrine_Event preQuery event
     *
     * adds the issued query to internal query stack
     * and checks if cached element exists
     *
     * @return boolean
     */
    public function preQuery(Doctrine_Event $event)
    {
        $query = $event->getQuery();

        $data  = false;
        // only process SELECT statements
        if (strtoupper(substr(ltrim($query), 0, 6)) == 'SELECT') {

            $this->add($query, $event->getInvoker()->getName());

            $data = $this->_driver->fetch(md5(serialize($query)));

            $this->success = ($data) ? true : false;

            if ( ! $data) {
                $rand = (mt_rand() / mt_getrandmax());

                if ($rand < $this->_options['savePropability']) {
                    $stmt = $event->getInvoker()->getAdapter()->query($query);

                    $data = $stmt->fetchAll(Doctrine::FETCH_ASSOC);

                    $this->success = true;

                    $this->_driver->save(md5(serialize($query)), $data);
                }
            }
            if ($this->success)
            {
                $this->_data = $data;
                return true;
            }
        }
        return false;
    }
    /**
     * preFetch
     * listens the preFetch event of Doctrine_Connection_Statement
     *
     * advances the internal pointer of cached data and returns 
     * the current element
     *
     * @return array
     */
    public function preFetch(Doctrine_Event $event)
    {
        $ret = current($this->_data);
    	next($this->_data);
        return $ret;
    }
    /**
     * preFetch
     * listens the preFetchAll event of Doctrine_Connection_Statement
     *
     * returns the current cache data array
     *
     * @return array
     */
    public function preFetchAll(Doctrine_Event $event)
    {
        return $this->_data;
    }
    /**
     * preExecute
     * listens the preExecute event of Doctrine_Connection_Statement
     *
     * adds the issued query to internal query stack
     * and checks if cached element exists
     *
     * @return boolean
     */
    public function preExecute(Doctrine_Event $event)
    {
        $query = $event->getQuery();

        $data  = false;

        // only process SELECT statements
        if (strtoupper(substr(ltrim($query), 0, 6)) == 'SELECT') {

            $this->add($query, $event->getInvoker()->getDbh()->getName());

            $data = $this->_driver->fetch(md5(serialize(array($query, $event->getParams()))));

            $this->success = ($data) ? true : false;

            if ( ! $data) {
                $rand = (mt_rand() / mt_getrandmax());

                if ($rand <= $this->_options['savePropability']) {

                    $stmt = $event->getInvoker()->getStatement();

                    $stmt->execute($event->getParams());

                    $data = $stmt->fetchAll(Doctrine::FETCH_ASSOC);

                    $this->success = true;

                    $this->_driver->save(md5(serialize(array($query, $event->getParams()))), $data);
                }
            }
            if ($this->success)
            {
                $this->_data = $data;
                return true;
            }
        }
        return false;
    }
}

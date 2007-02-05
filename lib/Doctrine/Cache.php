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
 * Doctrine_Cache
 *
 * @package     Doctrine
 * @subpackage  Doctrine_Cache
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Cache extends Doctrine_Db_EventListener implements Countable, IteratorAggregate
{
    protected $_options = array('size'        => 1000,
                                'lifeTime'    => 3600,
                                'statsFile'   => 'tmp/doctrine.cache.stats',
                                );

    protected $_queries = array();

    protected $_driver;
    
    protected $_data;
    
    public function __construct($driverName, $options = array()) 
    {
    	$class = 'Doctrine_Cache_' . ucwords(strtolower($driverName));

        if ( ! class_exists($class)) {
            throw new Doctrine_Cache_Exception('Cache driver ' . $driverName . ' could not be found.');
        }

        $this->_driver = new $class($options);
    }
    
    
    public function getDriver() 
    {
        return $this->_driver;
    }
    /**
     * addQuery
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
     * save
     *
     * @return boolean
     */
    public function processAll()
    {
        $content = file_get_contents($this->_statsFile);
        $queries = explode("\n", $content);

        $stats   = array();

        foreach ($queries as $query) {
            if (is_array($query)) {
                $query = $query[0];
            }
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
            $conn    = Doctrine_Manager::getConnection($element[1]);
            $data    = $conn->fetchAll($query);
            $this->_driver->save(serialize($data), $query, $this->_options['lifetime']);
        }
    }
    /**
     * flush
     *
     * adds all queries to stats file
     * @return void
     */
    public function flush()
    {
        file_put_contents($this->_statsFile, implode("\n", $this->queries));
    }
    

    public function onPreQuery(Doctrine_Db_Event $event)
    { 
        $query = $event->getQuery();
        
        // only process SELECT statements
        if (substr(trim(strtoupper($query)), 0, 6) == 'SELECT') {

            $this->add($query, $event->getInvoker()->getName());
            
            $data = $this->_driver->fetch(md5($query));

            $this->_data = $data;
            
            return true;
        }
        
        return false;
    }

    public function onQuery(Doctrine_Db_Event $event)
    {

    }

    public function onPreFetchAll(Doctrine_Db_Event $event)
    {
        return $this->_data;
    }
    public function onPrePrepare(Doctrine_Db_Event $event)
    {

    }
    public function onPrepare(Doctrine_Db_Event $event)
    { 
    
    }

    public function onPreExec(Doctrine_Db_Event $event)
    { 
    
    }
    public function onExec(Doctrine_Db_Event $event)
    {
    
    }

    public function onPreExecute(Doctrine_Db_Event $event)
    { 
        $query = $event->getQuery();
        
        // only process SELECT statements
        if (substr(trim(strtoupper($query)), 0, 6) == 'SELECT') {

            $this->add($query, $event->getInvoker()->getDbh()->getName());
            
            $data = $this->_driver->fetch(md5(serialize(array($query, $event->getParams()))));

            $this->_data = $data;
            
            return true;
        }
        
        return false;
    }
    public function onExecute(Doctrine_Db_Event $event)
    {
    }
}

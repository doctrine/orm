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
 * Doctrine_DB_Profiler
 *
 * @author      Konsta Vesterinen
 * @license     LGPL
 * @package     Doctrine
 */
class Doctrine_DB_Profiler extends Doctrine_DB_EventListener {
    public function onPreQuery(Doctrine_DB2 $dbh, $statement, array $args) {
        $this->queryStart($statement);
    }
    public function onQuery(Doctrine_DB2 $dbh, $statement, array $args, $queryId) {
        $this->queryEnd($queryId);
    }

    public function onPrePrepare(Doctrine_DB2 $dbh, $statement, array $args) {
        $this->prepareTimes[$dbh->getQuerySequence()] = microtime(true);
    }
    public function onPrepare(Doctrine_DB2 $dbh, $statement, array $args, $queryId) { 
        $this->prepareTimes[$queryId] = (microtime(true) - $this->prepareTimes[$queryId]);
    }

    public function onPreCommit(Doctrine_DB2 $dbh) { }
    public function onCommit(Doctrine_DB2 $dbh) { }

    public function onPreRollBack(Doctrine_DB2 $dbh) { }
    public function onRollBack(Doctrine_DB2 $dbh) { }

    public function onPreBeginTransaction(Doctrine_DB2 $dbh) { }
    public function onBeginTransaction(Doctrine_DB2 $dbh) { }

    public function onPreExecute(Doctrine_DB_Statement $stmt, array $params) {
        $this->queryStart($stmt->getQuery(), $stmt->getQuerySequence());
    }
    public function onExecute(Doctrine_DB_Statement $stmt, array $params) { 
        $this->queryEnd($stmt->getQuerySequence());
    }
    /**
     * Array of Zend_Db_Profiler_Query objects.
     *
     * @var Zend_Db_Profiler_Query
     */
    protected $_queryProfiles = array();


    protected $_prepareTimes  = array();
    /**
     * Stores the number of seconds to filter.  NULL if filtering by time is
     * disabled.  If an integer is stored here, profiles whose elapsed time
     * is less than this value in seconds will be unset from
     * the self::$_queryProfiles array.
     *
     * @var integer
     */
    protected $_filterElapsedSecs = null;

    /**
     * Logical OR of any of the filter constants.  NULL if filtering by query
     * type is disable.  If an integer is stored here, it is the logical OR of
     * any of the query type constants.  When the query ends, if it is not
     * one of the types specified, it will be unset from the
     * self::$_queryProfiles array.
     *
     * @var integer
     */
    protected $_filterTypes = null;


    /**
     * Start a query.  Creates a new query profile object (Zend_Db_Profiler_Query)
     * and returns the "query profiler handle".  Run the query, then call
     * queryEnd() and pass it this handle to make the query as ended and
     * record the time.  If the profiler is not enabled, this takes no
     * action and immediately runs.
     *
     * @param string $queryText     SQL statement
     * @param int $queryType        Type of query, one of the Zend_Db_Profiler::* constants
     * @return mixed
     */
    public function queryStart($queryText, $querySequence = -1) {
        $prepareTime = (isset($this->prepareTimes[$querySequence])) ? $this->prepareTimes[$querySequence] : null;

        $this->_queryProfiles[] = new Doctrine_DB_Profiler_Query($queryText, $prepareTime);
    }
    /**
     * Ends a query.  Pass it the handle that was returned by queryStart().
     * This will mark the query as ended and save the time.
     *
     * @param integer $queryId
     * @throws Zend_Db_Profiler_Exception
     * @return boolean
     */
    public function queryEnd($queryId = null) {


        // Check for a valid query handle.
        if($queryId === null) 
            $qp = end($this->_queryProfiles);
        else
            $qp = $this->_queryProfiles[$queryId];


        if($qp === null || $qp->hasEnded()) {
            throw new Zend_Db_Profiler_Exception('Query with profiler handle "'
                                          . $queryId .'" has already ended.');
        }

        // End the query profile so that the elapsed time can be calculated.
        $qp->end();
    }


    /**
     * Get a profile for a query.  Pass it the same handle that was returned
     * by queryStart() and it will return a Zend_Db_Profiler_Query object.
     *
     * @param int $queryId
     * @throws Zend_Db_Profiler_Exception
     * @return Zend_Db_Profiler_Query
     */
    public function getQueryProfile($queryId)
    {
        if (!array_key_exists($queryId, $this->_queryProfiles)) {
            throw new Zend_Db_Profiler_Exception("Query handle \"$queryId\" not found in profiler log.");
        }

        return $this->_queryProfiles[$queryId];
    }


    /**
     * Get an array of query profiles (Zend_Db_Profiler_Query objects).  If $queryType
     * is set to one of the Zend_Db_Profiler::* constants then only queries of that
     * type will be returned.  Normally, queries that have not yet ended will
     * not be returned unless $showUnfinished is set to True.  If no
     * queries were found, False is returned.
     *
     * @param string $queryType
     * @param bool $showUnfinished
     * @return mixed
     */
    public function getQueryProfiles($queryType=null, $showUnfinished=false)
    {
        $queryProfiles = array();
        foreach ($this->_queryProfiles as $key=>$qp) {
            /* @var $qp Zend_Db_Profiler_Query */
            if ($queryType===null) {
                $condition=true;
            } else {
                $condition=($qp->getQueryType() & $queryType);
            }

            if (($qp->hasEnded() || $showUnfinished) && $condition) {
                $queryProfiles[$key] = $qp;
            }
        }

        if (empty($queryProfiles)) {
            $queryProfiles = false;
        }
        return $queryProfiles;
    }


    /**
     * Get the total elapsed time (in seconds) of all of the profiled queries.
     * Only queries that have ended will be counted.  If $queryType is set to
     * one of the Zend_Db_Profiler::* constants, the elapsed time will be calculated
     * only for queries of that type.
     *
     * @param int $queryType
     * @return int
     */
    public function getTotalElapsedSecs($queryType = null)
    {
        $elapsedSecs = 0;
        foreach ($this->_queryProfiles as $key=>$qp) {
            /* @var $qp Zend_Db_Profiler_Query */
            is_null($queryType)? $condition=true : $condition=($qp->getQueryType() & $queryType);
            if (($qp->hasEnded()) && $condition) {
                $elapsedSecs += $qp->getElapsedSecs();
            }
        }
        return $elapsedSecs;
    }


    /**
     * Get the total number of queries that have been profiled.  Only queries that have ended will
     * be counted.  If $queryType is set to one of the Zend_Db_Profiler::* constants, only queries of
     * that type will be counted.
     *
     * @param int $queryType
     * @return int
     */
    public function getTotalNumQueries($queryType = null)
    {
        if (is_null($queryType)) {
            return sizeof($this->_queryProfiles);
        }

        $numQueries = 0;
        foreach ($this->_queryProfiles as $qp) {
            /* @var $qp Zend_Db_Profiler_Query */
            is_null($queryType)? $condition=true : $condition=($qp->getQueryType() & $queryType);
            if ($qp->hasEnded() && $condition) {
                $numQueries++;
            }
        }
        return $numQueries;
    }

    public function pop() {
        return array_pop($this->_queryProfiles);
    }
    /**
     * Get the Zend_Db_Profiler_Query object for the last query that was run, regardless if it has
     * ended or not.  If the query has not ended, it's end time will be Null.
     *
     * @return Zend_Db_Profiler_Query
     */
    public function lastQuery() {
        if (empty($this->_queryProfiles)) {
            return false;
        }

        end($this->_queryProfiles);
        return current($this->_queryProfiles);
    }
}

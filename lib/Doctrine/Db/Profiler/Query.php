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
 * Doctrine_Db_Profiler_Query
 *
 * @author      Konsta Vesterinen
 * @license     LGPL
 * @package     Doctrine
 */
class Doctrine_Db_Profiler_Query {
    /**
     * @var string      SQL query string or user comment, set by $query argument in constructor.
     */
    protected $query ='';
    /**
     * @var integer     One of the Zend_Db_Profiler constants for query type, set by $queryType argument in constructor.
     */
    protected $queryType = 0;


    protected $prepareTime;

    /**
     * @var float|null  Unix timestamp with microseconds when instantiated.
     */
    protected $startedMicrotime;

    /**
     * Unix timestamp with microseconds when self::queryEnd() was called.
     *
     * @var null|integer
     */
    protected $endedMicrotime;


    /**
     * Class constructor.  A query is about to be started, save the query text ($query) and its
     * type (one of the Zend_Db_Profiler::* constants).
     *
     * @param string $query
     * @param int $queryType
     */
    public function __construct($query, $prepareTime = null) {
        $this->query = $query;
        if($prepareTime !== null) {
            $this->prepareTime = $prepareTime;
        } else {
            $this->startedMicrotime = microtime(true);
        }
    }
    public function start() {
        $this->startedMicrotime = microtime(true);                       	
    }
    /**
     * The query has ended.  Record the time so that the elapsed time can be determined later.
     *
     * @return bool
     */
    public function end() {
        $this->endedMicrotime = microtime(true);
        return true;
    }

    public function getPrepareTime() {
        return $this->prepareTime;
    }

    /**
     * Has this query ended?
     *
     * @return bool
     */
    public function hasEnded() {
        return ($this->endedMicrotime != null);
    }


    /**
     * Get the original SQL text of the query.
     *
     * @return string
     */
    public function getQuery() {
        return $this->query;
    }


    /**
     * Get the type of this query (one of the Zend_Db_Profiler::* constants)
     *
     * @return int
     */
    public function getQueryType() {
        return $this->queryType;
    }
    /**
     * Get the elapsed time (in seconds) that the query ran.  If the query has
     * not yet ended, return false.
     *
     * @return mixed
     */
    public function getElapsedSecs() {
        if (is_null($this->endedMicrotime) && ! $this->prepareTime) {
            return false;
        }

        return ($this->prepareTime + ($this->endedMicrotime - $this->startedMicrotime));
    }
}


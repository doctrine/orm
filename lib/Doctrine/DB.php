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
class Doctrine_DB extends PDO implements Countable, IteratorAggregate {
    /**
     * default DSN
     */
    const DSN = "mysql://root:dc34@localhost/test";
    /**
     * executed queries
     */
    private $queries    = array();
    /**
     * execution times of the executed queries
     */
    private $exectimes  = array();

    /**
     * constructor
     * @param string $dsn
     * @param string $username
     * @param string $password
     */
    public function __construct($dsn,$username,$password) {
        parent::__construct($dsn,$username,$password);
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array("Doctrine_DBStatement",array($this)));
    }
    
    
    public static function getConn($dsn,$username = null, $password = null) {
        static $instance;
        
        if( ! isset($instance)) {
            $instance = new Doctrine_DB($dsn,$username,$password);
        }
        return $instance;
    }

    /**
     * @param string $dsn           PEAR::DB like DSN
     * format:                      schema://user:password@address/dbname
     */
    public static function getConnection($dsn = null) {
        static $instance = array();
        $md5 = md5($dsn);

        if( ! isset($instance[$md5])) {
            if( ! isset($dsn)) {
                $a = parse_url(self::DSN);
            } else {
                $a = parse_url($dsn);
            }
            $e = array();

            $e[0] = $a["scheme"].":host=".$a["host"].";dbname=".substr($a["path"],1);
            $e[1] = $a["user"];
            $e[2] = $a["pass"];

            $instance[$md5] = new Doctrine_DB($e[0],$e[1],$e[2]);
        }
        return $instance[$md5];
    }
    /**
     * @param string $query         query to be executed
     */
    public function query($query) {
        try {
            $this->queries[] = $query;
            $time = microtime();

            $stmt = parent::query($query);

            $this->exectimes[] = (microtime() - $time);
            return $stmt;
        } catch(PDOException $e) {
            throw $e;
        }
    }
    /**
     * @param string $query         query to be prepared
     */
    public function prepare($query) {
        $this->queries[] = $query;

        return parent::prepare($query);
    }
    /**
     * @param string $time          exectime of the last executed query
     * @return void
     */
    public function addExecTime($time) {
        $this->exectimes[] = $time;
    }
    
    public function getExecTimes() {
        return $this->exectimes;
    }
    /**
     * @return array                an array of executed queries
     */
    public function getQueries() {
        return $this->queries;
    }
    /**
     * @return ArrayIterator
     */
    public function getIterator() {
        return new ArrayIterator($this->queries);
    }
    /**
     * returns the number of executed queries
     * @return integer
     */
    public function count() {
        return count($this->queries);
    }

}


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
 * Doctrine_DB
 * A thin layer on top of PDO
 *
 * @author      Konsta Vesterinen
 * @license     LGPL
 * @package     Doctrine
 */
class Doctrine_DB implements Countable, IteratorAggregate {
    /**
     * default DSN
     */
    const DSN = "mysql://root:dc34@localhost/test";
    /**
     * @var array $instances        all the instances of this class
     */
    protected static $instances   = array();
    /**
     * @var array $queries          all the executed queries
     */
    protected $queries            = array();
    /**
     * @var array $exectimes        execution times of the executed queries
     */
    protected $exectimes          = array();
    /**
     * @var array $isConnected      whether or not a connection has been established
     */
    protected $isConnected        = false;
    /**
     * @var string $dsn             data source name
     */
    protected $dsn;
    /**
     * @var string $username        database username
     */
    protected $username;
    /**
     * @var string $password        database password
     */
    protected $password;
    /** 
     * @var PDO $dbh                the database handler
     */

    /**
     * constructor
     *
     * @param string $dsn           data source name
     * @param string $username      database username
     * @param string $password      database password
     */
    public function __construct($dsn,$username,$password) {
        $this->dsn      = $dsn;
        $this->username = $username;
        $this->password = $password;
    }
    /**
     * getDSN
     *
     * @return string
     */
    public function getDSN() {
        return $this->dsn;
    }
    /** 
     * getUsername
     */
    public function getUsername() {
        return $this->username;
    }
    /**
     * getPassword
     */
    public function getPassword() {
        return $this->password;
    }

    /** 
     * connect
     * connects into database
     *
     * @return boolean
     */
    public function connect() {
        if($this->isConnected) 
            return false;

        $this->dbh = new PDO($this->dsn,$this->username,$this->password);
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->dbh->setAttribute(PDO::ATTR_STATEMENT_CLASS, array("Doctrine_DBStatement",array($this)));
        
        return true;
    }

    /**
     * getConnection
     *
     * @param string $dsn           PEAR::DB like DSN
     * format:                      schema://user:password@address/dbname
     *
     * @return
     */
    public static function getConnection($dsn = null, $username = null, $password = null) {
        $md5 = md5($dsn);

        if(isset($username)) {
            self::$instances[$md5] = new Doctrine_DB($dsn, $username, $password);
        }

        if( ! isset(self::$instances[$md5])) {
            if( ! isset($dsn)) {
                $a = self::parseDSN(self::DSN);
            } else {
                $a = self::parseDSN($dsn);
            }
            $e = array();

            $dsn      = $a["phptype"].":host=".$a["hostspec"].";dbname=".$a["database"];
            $username = isset($a["username"])?$a['username']:null;
            $password = isset($a["password"])?$a['password']:null;

            self::$instances[$md5] = new Doctrine_DB($dsn,$username,$password);
        }
        return self::$instances[$md5];
    }
    /**
     * clear
     * clears all instances from the memory
     *
     * @return void
     */
    public static function clear() {
        self::$instances = array();
    }

    /**
     * errorCode
     * Fetch the SQLSTATE associated with the last operation on the database handle
     *
     * @return integer
     */
    public function errorCode() { 
        $this->connect();
        
        return $this->dbh->errorCode();
    }
    /**
     * errorInfo
     * Fetch extended error information associated with the last operation on the database handle
     *
     * @return array
     */
    public function errorInfo() {
        $this->connect();

        return $this->dbh->errorInfo();
    }
    /**
     *
     *
     * @param string $statement
     */
    public function prepare ($statement) {
        $this->connect();
        $this->queries[] = $query;
        return $this->dbh->prepare($statement);
    }
    /**
     * query
     *
     * @param string $statement
     * @return Doctrine_DB_Statement|boolean
     */
    public function query($statement, $fetchMode = null, $arg = null, $arg2 = null) {
        $this->connect();
        
        $this->queries[] = $query;
        $time = microtime();

        $stmt = $this->dbh->query($query, $fetchMode, $arg, $arg2);

        $this->exectimes[] = (microtime() - $time);

        return $stmt;
    }
    /**
     * quote
     * quotes a string for use in a query
     *
     * @param string $input
     * @return string
     */
    public function quote($input) {
        $this->connect();
        
        return $this->dbh->quote($input);
    }
    /**
     * exec
     * executes an SQL statement and returns the number of affected rows
     *
     * @param string $statement
     * @return integer
     */
    public function exec($statement) {
        $this->connect();
        
        return $this->dbh->exec($statement);
    }
    /**
     * lastInsertId
     *
     *
     */
    public function lastInsertId() {
        $this->connect();

        return $this->dbh->lastInsertId();
    }
    /**
     * begins a transaction
     *
     * @return boolean
     */
    public function beginTransaction() {
        $this->connect();

        return $this->dbh->beginTransaction();
    }
    /**
     * commits a transaction
     *
     * @return boolean
     */
    public function commit() {
        $this->connect();
        
        return $this->dbh->commit();
    }
    /**
     * rollBack
     *
     * @return boolean
     */
    public function rollBack() {
        $this->connect();
        
        $this->dbh->rollBack();
    }
    /**
     * getAttribute
     * retrieves a database connection attribute
     *
     * @param integer $attribute
     * @return mixed
     */
    public function getAttribute($attribute) {
        $this->connect();
        
        $this->dbh->getAttribute($attribute);
    }
    /**
     * returns an array of available PDO drivers
     */
    public static function getAvailableDrivers() {
        return PDO::getAvailibleDrivers();
    }
    /**
     * setAttribute
     * sets an attribute
     *
     * @param integer $attribute
     * @param mixed $value
     * @return boolean
     */
    public function setAttribute($attribute, $value) {
        $this->connect();
        
        $this->dbh->setAttribute($attribute, $value);
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
     * getQueries
     * returns an array of executed queries
     *
     * @return array
     */
    public function getQueries() {
        return $this->queries;
    }
    /**
     * getIterator
     *
     * @return ArrayIterator
     */
    public function getIterator() {
        return new ArrayIterator($this->queries);
    }
    /**
     * count
     * returns the number of executed queries
     *
     * @return integer
     */
    public function count() {
        return count($this->queries);
    }
    /**
     * parseDSN
     * Parse a data source name.
     *
     * Additional keys can be added by appending a URI query string to the
     * end of the DSN.
     *
     * The format of the supplied DSN is in its fullest form:
     * <code>
     *  phptype(dbsyntax)://username:password@protocol+hostspec/database?option=8&another=true
     * </code>
     *
     * Most variations are allowed:
     * <code>
     *  phptype://username:password@protocol+hostspec:110//usr/db_file.db?mode=0644
     *  phptype://username:password@hostspec/database_name
     *  phptype://username:password@hostspec
     *  phptype://username@hostspec
     *  phptype://hostspec/database
     *  phptype://hostspec
     *  phptype(dbsyntax)
     *  phptype
     * </code>
     *
     * @param   string  Data Source Name to be parsed
     *
     * @return  array   an associative array with the following keys:
     *  + phptype:  Database backend used in PHP (mysql, odbc etc.)
     *  + dbsyntax: Database used with regards to SQL syntax etc.
     *  + protocol: Communication protocol to use (tcp, unix etc.)
     *  + hostspec: Host specification (hostname[:port])
     *  + database: Database to use on the DBMS server
     *  + username: User name for login
     *  + password: Password for login
     *
     * @access  public
     * @author  Tomas V.V.Cox <cox@idecnet.com>
     */
    public static function parseDSN($dsn) {
        // Find phptype and dbsyntax
        if (($pos = strpos($dsn, '://')) !== false) {
            $str = substr($dsn, 0, $pos);
            $dsn = substr($dsn, $pos + 3);
        } else {
            $str = $dsn;
            $dsn = null;
        }

        // Get phptype and dbsyntax
        // $str => phptype(dbsyntax)
        if (preg_match('|^(.+?)\((.*?)\)$|', $str, $arr)) {
            $parsed['phptype']  = $arr[1];
            $parsed['dbsyntax'] = !$arr[2] ? $arr[1] : $arr[2];
        } else {
            $parsed['phptype']  = $str;
            $parsed['dbsyntax'] = $str;
        }

        if (!count($dsn)) {
            return $parsed;
        }

        // Get (if found): username and password
        // $dsn => username:password@protocol+hostspec/database
        if (($at = strrpos($dsn,'@')) !== false) {
            $str = substr($dsn, 0, $at);
            $dsn = substr($dsn, $at + 1);
            if (($pos = strpos($str, ':')) !== false) {
                $parsed['username'] = rawurldecode(substr($str, 0, $pos));
                $parsed['password'] = rawurldecode(substr($str, $pos + 1));
            } else {
                $parsed['username'] = rawurldecode($str);
            }
        }

        // Find protocol and hostspec

        // $dsn => proto(proto_opts)/database
        if (preg_match('|^([^(]+)\((.*?)\)/?(.*?)$|', $dsn, $match)) {
            $proto       = $match[1];
            $proto_opts  = $match[2] ? $match[2] : false;
            $dsn         = $match[3];

        // $dsn => protocol+hostspec/database (old format)
        } else {
            if (strpos($dsn, '+') !== false) {
                list($proto, $dsn) = explode('+', $dsn, 2);
            }
            if (strpos($dsn, '/') !== false) {
                list($proto_opts, $dsn) = explode('/', $dsn, 2);
            } else {
                $proto_opts = $dsn;
                $dsn = null;
            }
        }

        // process the different protocol options
        $parsed['protocol'] = (!empty($proto)) ? $proto : 'tcp';
        $proto_opts = rawurldecode($proto_opts);
        if (strpos($proto_opts, ':') !== false) {
            list($proto_opts, $parsed['port']) = explode(':', $proto_opts);
        }
        if ($parsed['protocol'] == 'tcp') {
            $parsed['hostspec'] = $proto_opts;
        } elseif ($parsed['protocol'] == 'unix') {
            $parsed['socket'] = $proto_opts;
        }

        // Get dabase if any
        // $dsn => database
        if ($dsn) {
            // /database
            if (($pos = strpos($dsn, '?')) === false) {
                $parsed['database'] = $dsn;
            // /database?param1=value1&param2=value2
            } else {
                $parsed['database'] = substr($dsn, 0, $pos);
                $dsn = substr($dsn, $pos + 1);
                if (strpos($dsn, '&') !== false) {
                    $opts = explode('&', $dsn);
                } else { // database?param1=value1
                    $opts = array($dsn);
                }
                foreach ($opts as $opt) {
                    list($key, $value) = explode('=', $opt);
                    if (!isset($parsed[$key])) {
                        // don't allow params overwrite
                        $parsed[$key] = rawurldecode($value);
                    }
                }
            }
        }

        return $parsed;
    }
    
    /**
     * Here is my version of parseDSN. It is a bit leaner than the one above, but you can choose either one.
     * This one relies on the built in functionality more than replicating it in userland code so it should
     * be more efficient. Not completely compatible with the parser above, but it is easy to add in 
     * the phptype/dbsyntax and protocol/hostspec parts if need be.
     *
     * @author Elliot Anderson <elliot.a@gmail.com>
     * 
     * @param 	string	$dsn
     * @return 	array 	Parsed contents of DSN
     */
    function parseDSNnew ( $dsn )
	{
		$parts	= parse_url ( $dsn );
		$parsed	= array ( );
		
		if ( count ( $parts ) == 0 ) return false;
			
		if ( isset ( $parts ['scheme'] ) )
			$parsed ['phptype']  = 
			$parsed ['dbsyntax'] = $parts ['scheme'];
		
		if ( isset ( $parts ['host'] ) )
		{
			if ( strpos ( $parts ['host'], '+' ) )
			{
				$tmp = explode ( '+', $parts ['host'] );
				
				$parsed ['protocol'] = $tmp [ 0 ];
				$parsed ['hostspec'] = $tmp [ 1 ];
			} 
			else
			{
				$parsed ['hostspec'] = $parts ['host'];
			}
		}
		
		if ( isset ( $parts ['path'] ) ) $parsed ['database'] = substr ( $parts ['path'], 1 );
		
		if ( isset ( $parts ['user'] ) ) $parsed ['username'] = $parts ['user'];
		if ( isset ( $parts ['pass'] ) ) $parsed ['password'] = $parts ['pass'];
		
		if ( isset ( $parts ['query'] ) ) parse_str ( $parts ['query'], $parsed ['options'] );
		
		return $parsed;
	}
}


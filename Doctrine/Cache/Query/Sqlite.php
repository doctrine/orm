<?php
class Doctrine_Cache_Query_Sqlite implements Countable {
    /**
     * doctrine cache
     */
    const CACHE_TABLE = 'doctrine_query_cache';
    /**
     * @var Doctrine_Session $session       the table object this cache container belongs to
     */
    private $table;
    /**
     * @var PDO $dbh                    database handler
     */
    private $dbh;
    /**
     * constructor
     * 
     * Doctrine_Table $table
     */
    public function __construct(Doctrine_Session $session) {
        $this->session = $session;
        $dir = $session->getAttribute(Doctrine::ATTR_CACHE_DIR);

        if( ! is_dir($dir))
            mkdir($dir, 0777);

        $this->path = $dir.DIRECTORY_SEPARATOR;
        $this->dbh  = new PDO("sqlite::memory:");


        try {
            if($this->session->getAttribute(Doctrine::ATTR_CREATE_TABLES) === true)
            {
                $columns = array();
                $columns['query_md5']       = array('string', 32, 'notnull');
                $columns['query_result']    = array('array', 100000, 'notnull');
                $columns['expires']         = array('integer', 11, 'notnull');

                $dataDict = new Doctrine_DataDict($this->dbh);
                $dataDict->createTable(self::CACHE_TABLE, $columns);
            }
        } catch(PDOException $e) {

        }
    }
    /**
     * store
     * stores a query in cache
     *
     * @param string $query
     * @param array $result
     * @param integer $lifespan
     * @return void
     */
    public function store($query, array $result, $lifespan) {
        $sql    = "INSERT INTO ".self::CACHE_TABLE." (query_md5, query_result, expires) VALUES (?,?,?)";
        $stmt   = $this->dbh->prepare($sql);
        $params = array(md5($query), serialize($result), (time() + $lifespan));
        $stmt->execute($params);
    }
    /**
     * fetch
     *
     * @param string $md5
     * @return array
     */
    public function fetch($md5) {
        $sql    = "SELECT query_result, expires FROM ".self::CACHE_TABLE." WHERE query_md5 = ?";
        $stmt   = $this->dbh->prepare($sql);
        $params = array($md5);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return unserialize($result['query_result']);
    }
    /**
     * deleteAll
     * returns the number of deleted rows
     *
     * @return integer
     */
    public function deleteAll() {
        $sql    = "DELETE FROM ".self::CACHE_TABLE;
        $stmt   = $this->dbh->query($sql);
        return $stmt->rowCount();
    }
    /**
     * delete
     * returns whether or not the given 
     * query was succesfully deleted
     *
     * @param string $md5
     * @return boolean
     */
    public function delete($md5) {
        $sql    = "DELETE FROM ".self::CACHE_TABLE." WHERE query_md5 = ?";
        $stmt   = $this->dbh->prepare($sql);
        $params = array($md5);
        $stmt->execute($params);
        return $stmt->rowCount();
    }
    /**
     * count
     *
     * @return integer
     */
    public function count() {
        $stmt = $this->dbh->query("SELECT COUNT(*) FROM ".self::CACHE_TABLE);
        $data = $stmt->fetch(PDO::FETCH_NUM);

        // table has three columns so we have to divide the count by two
        return $data[0];
    }
}
?>

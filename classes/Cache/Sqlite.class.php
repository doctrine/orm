<?php
require_once(Doctrine::getPath().DIRECTORY_SEPARATOR."Cache.class.php");

class Doctrine_Cache_Sqlite {
    /**
     * STATS_FILE constant
     * the name of the statistics file
     */
    const STATS_FILE = "stats.cache";
    /**
     * SELECT constant
     * used as a base for SQL SELECT queries
     */
    const SELECT     = "SELECT object FROM %s WHERE id %s";
    /**
     * INSERT constant
     * used as a base for SQL INSERT queries
     */
    const INSERT     = "INSERT INTO %s (id, object) VALUES (?, ?)";
    /**
     * DELETE constant
     * used as a base for SQL DELETE queries
     */
    const DELETE     = "DELETE FROM %s WHERE id %s";
    /**
     * @var Doctrine_Table $table
     */
    private $table;
    /**
     * @var PDO $dbh
     */
    private $dbh;
    /**
     * @var array $fetched          an array of fetched primary keys
     */
    private $fetched = array();

    public function __construct(Doctrine_Table $table) {
        $this->table = $table;
        $dir = $this->table->getSession()->getAttribute(Doctrine::ATTR_CACHE_DIR);


        if( ! is_dir($dir))
            mkdir($dir, 0777);

        $this->path = $dir.DIRECTORY_SEPARATOR;

        $this->dbh  = new PDO("sqlite:".$this->path."data.cache");
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->dbh->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
        
        try {
            $this->dbh->query("CREATE TABLE ".$this->table->getTableName()." (id INTEGER, object TEXT)");
        } catch(PDOException $e) {

        }
        /**
         * create stats file
         */
        if( ! file_exists($this->path.self::STATS_FILE))
            touch($this->path.self::STATS_FILE);

    }
    /*
     * stores a Doctrine_Record into cache
     * @param Doctrine_Record $record           record to be stored
     * @return boolean                          whether or not storing was successful
     */
    public function store(Doctrine_Record $record) {
        if($record->getState() != Doctrine_Record::STATE_CLEAN)
            return false;
            
        $clone = clone $record;
        $id    = $clone->getID();
        
        $stmt  = $this->dbh->query(sprintf(self::INSERT,$this->table->getTableName()));
        $stmt->execute(array($id, serialize($clone)));
        
        return true;
    }
    /**
     * fetches a Doctrine_Record from the cache
     * @param integer $id
     * @return mixed        false on failure, Doctrine_Record on success
     */
    public function fetch($id) {
        $stmt  = $this->dbh->query(sprintf(self::SELECT,$this->table->getTableName(),"= ?"));
        $stmt->execute(array($id));
        $data = $stmt->fetch(PDO::FETCH_NUM);

        if($data === false) 
            throw new InvalidKeyException();
            
        $this->fetched[] = $id;
        
        $record = unserialize($data[0]);

        if(is_string($record)) {
            $this->delete($id);
            throw new InvalidKeyException();
        }

        return $record;
    }
    /**
     * fetches multiple records from the cache
     * @param array $keys
     * @return mixed        false on failure, an array of Doctrine_Record objects on success
     */
    public function fetchMultiple(array $keys) {
        $count = (count($keys)-1);
        $sql   = sprintf(self::SELECT,$this->table->getTableName(),"IN (".str_repeat("?, ",$count)."?)");
        $stmt  = $this->dbh->query($sql);
        $stmt->execute($keys);

        while($data = $stmt->fetch(PDO::FETCH_NUM)) {
            $array[] = unserialize($data[0]);
        }

        $this->fetched = array_merge($this->fetched, $keys);

        if( ! isset($array))
            return false;

        return $array;
    }
    /**
     * deletes all records from cache
     * @return void
     */
    public function deleteAll() {
        $stmt = $this->dbh->query("DELETE FROM ".$this->table->getTableName());
        return $stmt->rowCount();
    }
    /**
     * @return void
     */
    public function delete($id) {
        $stmt  = $this->dbh->query(sprintf(self::DELETE,$this->table->getTableName(),"= ?"));
        $stmt->execute(array($id));
        
        if($stmt->rowCount() > 0)
            return true;

        return false;
    }
    /**
     * count
     * @return integer
     */
    public function count() {
        $stmt = $this->dbh->query("SELECT COUNT(*) FROM ".$this->table->getTableName());
        $data = $stmt->fetch(PDO::FETCH_NUM);

        // table has two columns so we have to divide the count by two
        return ($data[0] / 2);
    }
    /**
     * @param array $keys
     * @return integer
     */
    public function deleteMultiple(array $keys) {
        if(empty($keys))
            return 0;

        $count = (count($keys)-1);
        $sql   = sprintf(self::DELETE,$this->table->getTableName(),"IN (".str_repeat("?, ",$count)."?)");
        $stmt  = $this->dbh->query($sql);
        $stmt->execute($keys);

        return $stmt->rowCount();
    }
    /**
     * getStats
     * @return array            an array of fetch statistics, keys as primary keys
     *                          and values as fetch times
     */
    public function getStats() {
        $f = file_get_contents($this->path.self::STATS_FILE);

        // every cache file starts with a ":"

        $f = substr(trim($f),1);
        $e = explode(":",$f);
        return array_count_values($e);
    }
    /**
     * clean
     * @return void
     */
    public function clean() {
        $stats = $this->getStats();

        asort($stats);
        $size  = $this->table->getAttribute(Doctrine::ATTR_CACHE_SIZE);

        $count = count($stats);

        if($count <= $size)
            return 0;

        $e     = $count - $size;

        $keys = array();
        foreach($stats as $id => $count) {
            if( ! $e--)
                break;

            $keys[] = $id;
        }
        return $this->deleteMultiple($keys);
    }
    /**
     * saves statistics
     * @return boolean
     */
    public function saveStats() {
        if( ! empty($this->fetched)) {
            $fp    = fopen($this->path.self::STATS_FILE,"a");
            fwrite($fp,":".implode(":",$this->fetched));
            fclose($fp);
            $this->fetched = array();
            return true;
        }
        return false;
    }
    /**
     * autoClean
     * $ttl is the number of page loads between each cache cleaning
     * the default is 100 page loads
     *
     * this means that the average number of page loads between
     * each cache clean is 100 page loads (= 100 constructed Doctrine_Managers)
     * @return boolean
     */
    public function autoClean() {
        $ttl = $this->table->getAttribute(Doctrine::ATTR_CACHE_TTL);

        $l1 = (mt_rand(1,$ttl) / $ttl);
        $l2 = (1 - 1/$ttl);

        if($l1 > $l2) {
            $this->clean();
            return true;
        }
        return false;
    }

    /**
     * destructor
     * the purpose of this destructor is to save all the fetched
     * primary keys into the cache stats and to clean cache if necessary
     *
     */
    public function __destruct() {
        $this->saveStats();
        $this->autoClean();
    }
}
?>

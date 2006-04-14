<?php
require_once(Doctrine::getPath().DIRECTORY_SEPARATOR."iCache.class.php");
/**
 * Doctrine_CacheFile
 * @author      Konsta Vesterinen
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 * @version     1.0 alpha
 */
class Doctrine_Cache_File implements Countable {
    const STATS_FILE = "stats.cache";
    /**
     * @var string $path            path for the cache files
     */
    private $path;

    /**
     * @var array $fetched          an array of fetched primary keys
     */
    private $fetched = array();
    /**
     * @var Doctrine_Table $objTable
     */
    private $objTable;
    /**
     * constructor
     * @param Doctrine_Table $objTable
     */
    public function __construct(Doctrine_Table $objTable) {
        $this->objTable = $objTable;

        $name  = $this->getTable()->getTableName();

        $manager = Doctrine_Manager::getInstance();

        $dir   = $manager->getAttribute(Doctrine::ATTR_CACHE_DIR);

        if( ! is_dir($dir))
            mkdir($dir, 0777);

        if( ! is_dir($dir.DIRECTORY_SEPARATOR.$name))
            mkdir($dir.DIRECTORY_SEPARATOR.$name, 0777);

        $this->path = $dir.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR;

        /**
         * create stats file
         */
        if( ! file_exists($this->path.self::STATS_FILE))
            touch($this->path.self::STATS_FILE);


    }
    /**
     * @return Doctrine_Table
     */
    public function getTable() {
        return $this->objTable;
    }
    /**
     * @return integer          number of cache files
     */
    public function count() {
        $c = -1;
        foreach(glob($this->path."*.cache") as $file) {
            $c++;
        }
        return $c;
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
     * store                    store a Doctrine_Record into file cache
     * @param Doctrine_Record $record          data access object to be stored
     * @return boolean          whether or not storing was successful
     */
    public function store(Doctrine_Record $record) {
        if($record->getState() != Doctrine_Record::STATE_CLEAN)
            return false;


        $file = $this->path.$record->getID().".cache";

        if(file_exists($file))
            return false;

        $clone = clone $record;
        $id    = $clone->getID();

        $fp   = fopen($file,"w+");
        fwrite($fp,serialize($clone));
        fclose($fp);
        


        $this->fetched[] = $id;

        return true;
    }
    /**
     * clean
     * @return void
     */
    public function clean() {
        $stats = $this->getStats();

        arsort($stats);
        $size  = $this->objTable->getAttribute(Doctrine::ATTR_CACHE_SIZE);

        $count = count($stats);
        $i = 1;

        $preserve = array();
        foreach($stats as $id => $count) {
            if($i > $size)
                break;

            $preserve[$id] = true;
            $i++;
        }

        foreach(glob($this->path."*.cache") as $file) {
            $e = explode(".",basename($file));
            $c = count($e);
            $id = $e[($c - 2)];

            if( ! isset($preserve[$id]))
                @unlink($this->path.$id.".cache");
        }

        $fp = fopen($this->path.self::STATS_FILE,"w+");
        fwrite($fp,"");
        fclose($fp);
    }
    /**
     * @param integer $id       primary key of the DAO
     * @return string           filename and path
     */
    public function getFileName($id) {
        return $this->path.$id.".cache";
    }
    /**
     * @return array            an array of fetched primary keys
     */
    public function getFetched() {
        return $this->fetched;
    }
    /**
     * fetch                    fetch a Doctrine_Record from the file cache
     * @param integer $id
     */
    public function fetch($id) {
        $name = $this->getTable()->getComponentName();
        $file = $this->path.$id.".cache";

        if( ! file_exists($file))
            throw new InvalidKeyException();

        $data = file_get_contents($file);

        $record  = unserialize($data);

        if( ! ($record instanceof Doctrine_Record)) {
            // broken file, delete silently
            $this->delete($id);
            throw new InvalidKeyException();
        }

        $this->fetched[] = $id;

        return $record;
    }
    /**
     * exists                   check the existence of a cache file
     * @param integer $id       primary key of the cached DAO
     * @return boolean          whether or not a cache file exists
     */
    public function exists($id) {
        $name = $this->getTable()->getComponentName();
        $file = $this->path.$id.".cache";
        return file_exists($file);
    }
    /**
     * deleteAll
     * @return void
     */
    public function deleteAll() {
        foreach(glob($this->path."*.cache") as $file) {
            @unlink($file);
        }
        $fp = fopen($this->path.self::STATS_FILE,"w+");
        fwrite($fp,"");
        fclose($fp);
    }
    /**
     * delete                   delete a cache file
     * @param integer $id       primary key of the cached DAO
     */
    public function delete($id) {
        $file = $this->path.$id.".cache";

        if( ! file_exists($file))
            return false;

        @unlink($file);
        return true;
    }
    /**
     * deleteMultiple           delete multiple cache files
     * @param array $ids        an array containing cache file ids
     * @return integer          the number of files deleted
     */
    public function deleteMultiple(array $ids) {
        $deleted = 0;
        foreach($ids as $id) {
            if($this->delete($id)) $deleted++;
        }
        return $deleted;
    }
    /**
     * destructor
     * the purpose of this destructor is to save all the fetched 
     * primary keys into the cache stats
     */
    public function __destruct() {
        if( ! empty($this->fetched)) {
            $fp    = fopen($this->path.self::STATS_FILE,"a");
            fwrite($fp,":".implode(":",$this->fetched));
            fclose($fp);
        }
        /**
         *
         * cache auto-cleaning algorithm
         * $ttl is the number of page loads between each cache cleaning
         * the default is 100 page loads
         *
         * this means that the average number of page loads between
         * each cache clean is 100 page loads (= 100 constructed Doctrine_Managers)
         *
         */
        $ttl = $this->objTable->getAttribute(Doctrine::ATTR_CACHE_TTL);
        $l1 = (mt_rand(1,$ttl) / $ttl);
        $l2 = (1 - 1/$ttl);

        if($l1 > $l2)
            $this->clean();

    }
}
?>

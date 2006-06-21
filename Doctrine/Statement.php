<?php
require_once("Access.php");
/**
 * Doctrine_Statement
 *
 * Doctrine_Statement is a wrapper for PDOStatement with DQL support
 *
 * @package     Doctrine ORM
 * @url         www.phpdoctrine.com
 * @license     LGPL
 */
class Doctrine_Statement extends Doctrine_Access {
    /**
     * @var Doctrine_Query $query
     */
    private $query;
    /**
     * @var PDOStatement $stmt
     */
    private $stmt;
    /**
     * @var array $reserved
     */
    private $reserved = array();

    /**
     * constructor
     *
     * @param Doctrine_Query $query
     * @param PDOStatement $stmt
     */
    public function __construct(Doctrine_Query $query, PDOStatement $stmt) {
        $this->query = $query;
        $this->stmt  = $stmt;
    }
    public function set($name, $value) { }
    public function get($name) { }
    /**
     * getCollection
     * returns Doctrine_Collection object
     *
     * @parma string $name              component name
     * @param integer $index
     * @return Doctrine_Collection
     */
    private function getCollection($name) {
        $table = $this->session->getTable($name);
        switch($this->fetchModes[$name]):
            case Doctrine::FETCH_BATCH:
                $coll = new Doctrine_Collection_Batch($table);
            break;
            case Doctrine::FETCH_LAZY:
                $coll = new Doctrine_Collection_Lazy($table);
            break;
            case Doctrine::FETCH_OFFSET:
                $coll = new Doctrine_Collection_Offset($table);
            break;
            case Doctrine::FETCH_IMMEDIATE:
                $coll = new Doctrine_Collection_Immediate($table);
            break;
            case Doctrine::FETCH_LAZY_OFFSET:
                $coll = new Doctrine_Collection_LazyOffset($table);
            break;
        endswitch;

        $coll->populate($this);
        return $coll;
    }
    /**
     * execute
     * executes the dql query, populates all collections
     * and returns the root collection
     *
     * @param array $params
     * @return Doctrine_Collection
     */
    public function execute($params = array()) {
        switch(count($this->tables)):
            case 0:
                throw new DQLException();
            break;
            case 1:
                $query = $this->getQuery();

                $keys  = array_keys($this->tables);
    
                $name  = $this->tables[$keys[0]]->getComponentName();
                $stmt  = $this->session->execute($query,$params);

                while($data = $stmt->fetch(PDO::FETCH_ASSOC)):
                    foreach($data as $key => $value):
                        $e = explode("__",$key);
                        if(count($e) > 1) {
                            $data[$e[1]] = $value;
                        } else {
                            $data[$e[0]] = $value;
                        }
                        unset($data[$key]);
                    endforeach;
                    $this->data[$name][] = $data;
                endwhile;

                return $this->getCollection($keys[0]);
            break;
            default:
                $query = $this->getQuery();

                $keys  = array_keys($this->tables);
                $root  = $keys[0];
                $stmt  = $this->session->execute($query,$params);
                
                $previd = array();

                $coll = $this->getCollection($root);

                $array = $this->parseData($stmt);

                foreach($array as $data):

                    /**
                     * remove duplicated data rows and map data into objects
                     */
                    foreach($data as $key => $row):
                        if(empty($row))
                            continue;

                        $key  = ucwords($key);
                        $name = $this->tables[$key]->getComponentName();

                        if( ! isset($previd[$name]))
                            $previd[$name] = array();


                        if($previd[$name] !== $row) {
                            $this->tables[$name]->setData($row);
                            $record = $this->tables[$name]->getRecord();

                            if($name == $root) {
                                $this->tables[$name]->setData($row);
                                $record = $this->tables[$name]->getRecord();
                                $coll->add($record);
                            } else {
                                $last = $coll->getLast();

                                if( ! $last->hasReference($name)) {
                                    $last->initReference($this->getCollection($name),$this->connectors[$name]);
                                }
                                $last->addReference($record);
                            }
                        }

                        $previd[$name] = $row;
                    endforeach;
                endforeach;

                return $coll;
        endswitch;
    }
    /**
     * parseData
     * parses the data returned by PDOStatement
     *
     * @param PDOStatement $stmt
     * @return array
     */
    public function parseData(PDOStatement $stmt) {
        $array = array();
        while($data = $stmt->fetch(PDO::FETCH_ASSOC)):
            /**
             * parse the data into two-dimensional array
             */
            foreach($data as $key => $value):
                $e = explode("__",$key);

                if(count($e) > 1) {
                    $data[$e[0]][$e[1]] = $value;
                } else {
                    $data[0][$e[0]] = $value;
                }
                unset($data[$key]);
            endforeach;
            $array[] = $data;
        endwhile;
        $stmt->closeCursor();
        return $array;
    }
}
?>

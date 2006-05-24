<?php
require_once("Common.class.php");
/**
 * mysql driver
 */
class Doctrine_Session_Mysql extends Doctrine_Session_Common {

    /**
     * the constructor
     * @param PDO $pdo  -- database handle
     */
    public function __construct(Doctrine_Manager $manager,PDO $pdo) {
        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        parent::__construct($manager,$pdo);
    }
    /**
     * deletes all data access object from the collection
     * @param Doctrine_Collection $coll
     */
     /**
    public function deleteCollection(Doctrine_Collection $coll) {

        $a   = $coll->getTable()->getCompositePaths();
        $a   = array_merge(array($coll->getTable()->getComponentName()),$a);

        $graph = new Doctrine_DQL_Parser($this);
        foreach($coll as $k=>$record) {
            switch($record->getState()):
                case Doctrine_Record::STATE_DIRTY:
                case Doctrine_Record::STATE_CLEAN:
                    $ids[] = $record->getID();
                break;
            endswitch;
        }
        if(empty($ids))
            return array();

        $graph->parseQuery("FROM ".implode(", ",$a)." WHERE ".$coll->getTable()->getTableName().".id IN(".implode(", ",$ids).")");

        $query = $graph->buildDelete();

        $this->getDBH()->query($query);
        return $ids;
    }
    */

    /**
     * returns maximum identifier values
     *
     * @param array $names          an array of component names
     * @return array
     */
    public function getMaximumValues2(array $names) { 
        $values = array();
        foreach($names as $name) {
            $table     = $this->tables[$name];
            $keys      = $table->getPrimaryKeys();
            $tablename = $table->getTableName();

            if(count($keys) == 1 && $keys[0] == $table->getIdentifier()) {
                // record uses auto_increment column

                $sql[]    = "SELECT MAX(".$tablename.".".$table->getIdentifier().") as $tablename FROM ".$tablename;
                $values[$tablename] = 0;
                $array[] = $tablename;
            }
        }
        $sql    = implode(" UNION ",$sql);
        $stmt   = $this->getDBH()->query($sql);
        $data   = $stmt->fetchAll(PDO::FETCH_NUM);

        foreach($data as $k => $v) {
            $values[$array[$k]] = $v[0];
        }
        return $values;
    }
    /**
     * bulkInsert
     * inserts all the objects in the pending insert list into database
     * TODO: THIS IS NOT WORKING YET AS THERE ARE BUGS IN COMPONENTS USING SELF-REFERENCENCING
     *
     * @return boolean
     */
     
     /**
    public function bulkInsert() {
        if(empty($this->insert))
            return false;

        foreach($this->insert as $name => $inserts) {
            if( ! isset($inserts[0]))
                continue;

            $record    = $inserts[0];
            $table     = $record->getTable();
            $seq       = $table->getSequenceName();
            $keys      = $table->getPrimaryKeys();

            $marks = array();
            $params = array();
            foreach($inserts as $k => $record) {
                $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onPreSave($record);
                // listen the onPreInsert event
                $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onPreInsert($record);

                $array = $record->getPrepared();

                if(isset($this->validator)) {
                    if( ! $this->validator->validateRecord($record)) {
                        continue;
                    }
                }

                $key = implode(", ",array_keys($array));
                if( ! isset($params[$key]))
                    $params[$key] = array();

                $marks[$key][] = "(".substr(str_repeat("?, ",count($array)),0,-2).")";
                $params[$key] = array_merge($params[$key], array_values($array));


                // listen the onInsert event
                $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onInsert($record);

                $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onSave($record);
            }

            if( ! empty($marks)) {
                foreach($marks as $key => $list) {
                    $query = "INSERT INTO ".$table->getTableName()." (".$key.") VALUES ".implode(", ", $list);
                    $stmt  = $this->getDBH()->prepare($query);
                    $stmt->execute($params[$key]);
                }
            }
            if(count($keys) == 1 && $keys[0] == $table->getIdentifier()) {

                // record uses auto_increment column

                $sql  = "SELECT MAX(".$table->getIdentifier().") FROM ".$record->getTable()->getTableName();
                $stmt = $this->getDBH()->query($sql);
                $data = $stmt->fetch(PDO::FETCH_NUM);
                $id   = $data[0];
                $stmt->closeCursor();
                
                foreach(array_reverse($inserts) as $record) {

                    $record->setID((int) $id);
                    $id--;
                }
            }
        }

        $this->insert = array();
        return true;
    }
    */

}
?>

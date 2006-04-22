<?php
/**
 * mysql driver
 */
class Doctrine_Session_Mysql extends Doctrine_Session_Common {
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

            if(count($keys) == 1 && $keys[0] == "id") {
                // record uses auto_increment column

                $sql[]    = "SELECT MAX(".$tablename.".id) as $tablename FROM ".$tablename;
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
     *
     * @return boolean
     */
    public function bulkInsert() {
        if(empty($this->insert))
            return false;

        foreach($this->insert as $name => $inserts) {
            if( ! isset($inserts[0]))
                continue;

            $record    = $inserts[0];
            $table     = $record->getTable();
            $seq       = $table->getSequenceName();
            $increment = false;
            $id        = null;
            $keys      = $table->getPrimaryKeys();
            if(count($keys) == 1 && $keys[0] == "id") {

                // record uses auto_increment column

                $sql  = "SELECT MAX(id) FROM ".$record->getTable()->getTableName();
                $stmt = $this->getDBH()->query($sql);
                $data = $stmt->fetch(PDO::FETCH_NUM);
                $id   = $data[0];
                $stmt->closeCursor();
                $increment = true;
            }


            $marks = array();
            $params = array();
            foreach($inserts as $k => $record) {
                $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onPreSave($record);
                // listen the onPreInsert event
                $record->getTable()->getAttribute(Doctrine::ATTR_LISTENER)->onPreInsert($record);


                if($increment) {
                    // record uses auto_increment column
                    $id++;
                }


                $array = $record->getModified();

                foreach($record->getTable()->getInheritanceMap() as $k=>$v):
                    $array[$k] = $v;
                endforeach;

                foreach($array as $k => $value) {
                    if($value instanceof Doctrine_Record) {
                        $array[$k] = $value->getID();
                        $record->set($k,$value->getID());
                    }
                }
                
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

                $record->setID($id);

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
        }

        $this->insert = array();
        return true;
    }

}
?>

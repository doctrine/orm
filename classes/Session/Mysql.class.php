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
}
?>

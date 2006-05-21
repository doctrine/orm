<?php
class Doctrine_Lib {
    /**
     * @param integer $state                the state of record
     * @see Doctrine_Record::STATE_* constants
     * @return string                       string representation of given state
     */
    public static function getRecordStateAsString($state) {
        switch($state):
            case Doctrine_Record::STATE_PROXY:
                return "proxy";
            break;
            case Doctrine_Record::STATE_CLEAN:
                return "persistent clean";
            break;
            case Doctrine_Record::STATE_DIRTY:
                return "persistent dirty";
            break;
            case Doctrine_Record::STATE_TDIRTY:
                return "transient dirty";
            break;
            case Doctrine_Record::STATE_TCLEAN:
                return "transient clean";
            break;
        endswitch;
    }
    /**
     * returns a string representation of Doctrine_Record object
     * @param Doctrine_Record $record
     * @return string
     */
    public function getRecordAsString(Doctrine_Record $record) {
        $r[] = "<pre>";
        $r[] = "Component  : ".$record->getTable()->getComponentName();
        $r[] = "ID         : ".$record->getID();
        $r[] = "References : ".count($record->getReferences());
        $r[] = "State      : ".Doctrine_Lib::getRecordStateAsString($record->getState());
        $r[] = "OID        : ".$record->getOID();
        $r[] = "</pre>";
        return implode("\n",$r)."<br />";
    }
    /**
     * getStateAsString
     * returns a given session state as string
     * @param integer $state        session state
     */
    public static function getSessionStateAsString($state) {
        switch($state):
            case Doctrine_Session::STATE_OPEN:
                return "open";
            break;
            case Doctrine_Session::STATE_CLOSED:
                return "closed";
            break;
            case Doctrine_Session::STATE_BUSY:
                return "busy";
            break;
            case Doctrine_Session::STATE_ACTIVE:
                return "active";
            break;
        endswitch;
    }
    /**
     * returns a string representation of Doctrine_Session object
     * @param Doctrine_Session $session
     * @return string
     */
    public function getSessionAsString(Doctrine_Session $session) {
        $r[] = "<pre>";
        $r[] = "Doctrine_Session object";
        $r[] = "State               : ".Doctrine_Lib::getSessionStateAsString($session->getState());
        $r[] = "Open Transactions   : ".$session->getTransactionLevel();
        $r[] = "Open Factories      : ".$session->count();
        $sum = 0;
        $rsum = 0;
        $csum = 0;
        foreach($session->getTables() as $objTable) {
            if($objTable->getCache() instanceof Doctrine_Cache_File) {
                $sum += array_sum($objTable->getCache()->getStats());
                $rsum += $objTable->getRepository()->count();
                $csum += $objTable->getCache()->count();
            }
        }
        $r[] = "Cache Hits          : ".$sum." hits ";
        $r[] = "Cache               : ".$csum." objects ";

        $r[] = "Repositories        : ".$rsum." objects ";
        $queries = false;
        if($session->getDBH() instanceof Doctrine_DB) {
            $handler = "Doctrine Database Handler";
            $queries = count($session->getDBH()->getQueries());
            $sum     = array_sum($session->getDBH()->getExecTimes());
        } elseif($session->getDBH() instanceof PDO) {
            $handler = "PHP Native PDO Driver";
        } else
            $handler = "Unknown Database Handler";

        $r[] = "DB Handler          : ".$handler;
        if($queries) {
            $r[] = "Executed Queries    : ".$queries;
            $r[] = "Sum of Exec Times   : ".$sum;
        }

        $r[] = "</pre>";
        return implode("\n",$r)."<br>";
    }
    /**
     * returns a string representation of Doctrine_Table object
     * @param Doctrine_Table $table
     * @return string
     */
    public function getTableAsString(Doctrine_Table $table) {
        $r[] = "<pre>";
        $r[] = "Component   : ".$this->getComponentName();
        $r[] = "Table       : ".$this->getTableName();
        $r[] = "Repository  : ".$this->getRepository()->count()." objects";
        if($table->getCache() instanceof Doctrine_Cache_File) {
            $r[] = "Cache       : ".$this->getCache()->count()." objects";
            $r[] = "Cache hits  : ".array_sum($this->getCache()->getStats())." hits";
        }
        $r[] = "</pre>";
        return implode("\n",$r)."<br>";
    }
    /**
     * returns a string representation of Doctrine_Collection object
     * @param Doctrine_Collection $collection
     * @return string
     */
    public function getCollectionAsString(Doctrine_Collection $collection) {
        $r[] = "<pre>";
        $r[] = get_class($collection);

        foreach($collection as $key => $record) {
            $r[] = "Key : ".$key." ID : ".$record->getID();
        }
        $r[] = "</pre>";
        return implode("\n",$r);
    }
}
?>

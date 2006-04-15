<?php
/**
 * standard session, the parent of pgsql, mysql and sqlite
 */
class Doctrine_Session_Common extends Doctrine_Session {
    public function modifyLimitQuery($query,$limit = null,$offset = null) {
        if(isset($limit))
            $query .= " LIMIT ".$limit;
        else 
            $query .= " LIMIT 99999999999999";

        if(isset($offset))
            $query .= " OFFSET ".$offset;

        return $query;
    }
}
?>

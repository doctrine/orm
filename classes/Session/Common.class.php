<?php
/**
 * standard session, the parent of pgsql, mysql and sqlite
 */
class Doctrine_Session_Common extends Doctrine_Session {
    public function modifyLimitQuery($query,$limit = null,$offset = null) {
        if(isset($limit) && isset($offset)) {
            $query .= " LIMIT ".$limit." OFFSET ".$offset;
        } elseif(isset($limit) && ! isset($offset)) {
            $query .= " LIMIT ".$limit;
        } elseif( ! isset($limit) && isset($offset)) {
            $query .= " LIMIT 999999999999 OFFSET ".$offset;
        }

        return $query;
    }
}
?>

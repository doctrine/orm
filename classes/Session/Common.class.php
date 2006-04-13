<?php
/**
 * standard session, the parent of pgsql, mysql and sqlite
 */
class Doctrine_Session_Common extends Doctrine_Session {
    public function modifyLimitQuery($query,$limit,$offset) {
        return $query." LIMIT ".$limit." OFFSET ".$offset;
    }
}
?>

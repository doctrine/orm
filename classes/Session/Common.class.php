<?php
require_once(Doctrine::getPath().DIRECTORY_SEPARATOR."Session.class.php");
/**
 * standard session, the parent of pgsql, mysql and sqlite
 */
class Doctrine_Session_Common extends Doctrine_Session {
    public function modifyLimitQuery($query,$limit = false,$offset = false) {
        if($limit && $offset) {
            $query .= " LIMIT ".$limit." OFFSET ".$offset;
        } elseif($limit && ! $offset) {
            $query .= " LIMIT ".$limit;
        } elseif( ! $limit && $offset) {
            $query .= " LIMIT 999999999999 OFFSET ".$offset;
        }

        return $query;
    }
}
?>

<?php
Doctrine::autoload('Doctrine_Connection');
/**
 * standard connection, the parent of pgsql, mysql and sqlite
 * @package     Doctrine
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Connection_Common extends Doctrine_Connection
{
    /**
     * Adds an driver-specific LIMIT clause to the query
     *
     * @param string $query
     * @param mixed $limit
     * @param mixed $offset
     */
    public function modifyLimitQuery($query,$limit = false,$offset = false,$isManip=false)
    {
        if ($limit && $offset) {
            $query .= " LIMIT ".$limit." OFFSET ".$offset;
        } elseif ($limit && ! $offset) {
            $query .= " LIMIT ".$limit;
        } elseif ( ! $limit && $offset) {
            $query .= " LIMIT 999999999999 OFFSET ".$offset;
        }

        return $query;
    }
}

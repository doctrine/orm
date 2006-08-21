<?php
/**
 * mssql driver
 */
class Doctrine_Connection_Mssql extends Doctrine_Connection {
    /**
     * returns the next value in the given sequence
     * @param string $sequence
     * @return integer
     */
    public function getNextID($sequence) {
        $this->query("INSERT INTO $sequence (vapor) VALUES (0)");
        $stmt = $this->query("SELECT @@IDENTITY FROM $sequence");
        $data = $stmt->fetch(PDO::FETCH_NUM);
        return $data[0];
    }
    /**
     * Adds an adapter-specific LIMIT clause to the SELECT statement.
     * [ borrowed from Zend Framework ]
     *
     * @param string $query
     * @param mixed $limit
     * @param mixed $offset
     * @link http://lists.bestpractical.com/pipermail/rt-devel/2005-June/007339.html
     * @return string
     */
    public function modifyLimitQuery($query, $limit, $offset) {
        if ($limit) {

            // we need the starting SELECT clause for later
            $select = 'SELECT ';
            if (preg_match('/^[[:space:]*SELECT[[:space:]]*DISTINCT/i', $query, $matches) == 1)
                $select .= 'DISTINCT ';

            $length = strlen($select);

            // is there an offset?
            if (! $offset) {
                // no offset, it's a simple TOP count
                return "$select TOP $count" . substr($query, $length);
            }

            // the total of the count **and** the offset, combined.
            // this will be used in the "internal" portion of the
            // hacked-up statement.
            $total = $count + $offset;

            // build the "real" order for the external portion.
            $order = implode(',', $parts['order']);

            // build a "reverse" order for the internal portion.
            $reverse = $order;
            $reverse = str_ireplace(" ASC",  " \xFF", $reverse);
            $reverse = str_ireplace(" DESC", " ASC",  $reverse);
            $reverse = str_ireplace(" \xFF", " DESC", $reverse);

            // create a main statement that replaces the SELECT
            // with a SELECT TOP
            $main = "\n$select TOP $total" . substr($query, $length) . "\n";

            // build the hacked-up statement.
            // do we really need the "as" aliases here?
            $query = "SELECT * FROM ("
                 . "SELECT TOP $count * FROM ($main) AS select_limit_rev ORDER BY $reverse"
                 . ") AS select_limit ORDER BY $order";

        }

        return $query;
    }
}
?>

<?php
/*
 *  $Id$
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.com>.
 */
Doctrine::autoload('Doctrine_Connection_Module');
/**
 * Doctrine_Cache_Query_Sqlite
 *
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 */
class Doctrine_Cache_Query_Sqlite extends Doctrine_Connection_Module implements Countable
{
    /**
     * doctrine cache
     */
    const CACHE_TABLE = 'doctrine_query_cache';
    /**
     * constructor
     *
     * @param Doctrine_Connection|null $conn
     */
    public function __construct($conn = null)
    {
        parent::__construct($conn);

        $dir = 'cache';

        $this->path = $dir . DIRECTORY_SEPARATOR;

        try {
            if ($this->session->getAttribute(Doctrine::ATTR_CREATE_TABLES) === true) {
                $columns = array();

                $columns['query_md5']       = array('type'      => 'string',
                                                    'length'    => 32,
                                                    'notnull'   => true);
                $columns['query_result']    = array('type'      => 'array',
                                                    'length'    => 100000,
                                                    'notnull'   => true);
                $columns['expires']         = array('type'      => 'integer',
                                                    'length'    => 11,
                                                    'notnull'   => true);

                $this->conn->createTable(self::CACHE_TABLE, $columns);
            }
        } catch(PDOException $e) {

        }
    }
    /**
     * store
     * stores a query in cache
     *
     * @param string $query
     * @param array $result
     * @param integer $lifespan
     * @return void
     */
    public function store($query, array $result, $lifespan)
    {
        $sql    = 'INSERT INTO ' . self::CACHE_TABLE . ' (query_md5, query_result, expires) VALUES (?,?,?)';
        $params = array(md5($query), serialize($result), (time() + $lifespan));

        $this->conn->execute($sql, $params);
    }
    /**
     * fetch
     *
     * @param string $md5
     * @return array
     */
    public function fetch($md5)
    {
        $sql    = 'SELECT query_result, expires FROM ' . self::CACHE_TABLE . ' WHERE query_md5 = ?';

        $result = $this->conn->fetchAssoc($sql, array($md5));
        return unserialize($result['query_result']);
    }
    /**
     * deleteAll
     * returns the number of deleted rows
     *
     * @return integer
     */
    public function deleteAll()
    {
        $sql    = 'DELETE FROM '.self::CACHE_TABLE;
        return $this->conn->exec($sql);
    }
    /**
     * deleteExpired
     * returns the number of deleted rows
     *
     * @return integer
     */
    public function deleteExpired()
    {
        $sql    = 'DELETE FROM ' . self::CACHE_TABLE . ' WHERE expired < ?';
        $stmt   = $this->dbh->prepare($sql);

        $stmt->execute(array(time()));
    }
    /**
     * delete
     * returns whether or not the given
     * query was succesfully deleted
     *
     * @param string $md5
     * @return boolean          whether or not the row was successfully deleted
     */
    public function delete($md5)
    {
        $sql    = 'DELETE FROM ' . self::CACHE_TABLE . ' WHERE query_md5 = ?';

        return (bool) $this->conn->exec($sql, array($md5));
    }
    /**
     * count
     *
     * @return integer
     */
    public function count()
    {
        $stmt = $this->dbh->query('SELECT COUNT(*) FROM ' . self::CACHE_TABLE);
        $data = $stmt->fetch(PDO::FETCH_NUM);

        // table has three columns so we have to divide the count by two
        return $data[0];
    }
}

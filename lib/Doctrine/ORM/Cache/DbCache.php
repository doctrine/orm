<?php
/*
 *  $Id: Db.php 3931 2008-03-05 11:24:33Z romanb $
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
 * <http://www.doctrine-project.org>.
 */

namespace Doctrine\ORM\Cache;

/**
 * Doctrine_Cache_Db
 *
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @link        www.doctrine-project.org
 * @since       1.0
 * @version     $Revision: 3931 $
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @todo Needs some maintenance. Any takers?
 */
class DbCache implements Cache, \Countable
{
    private $_options = array();

    /**
     * {@inheritdoc}
     */
    public function __construct($options) 
    {
        if ( ! isset($options['connection']) || 
             ! ($options['connection'] instanceof Doctrine_DBAL_Connection)) {

            throw \Doctrine\Common\DoctrineException::updateMe('Connection option not set.');
        }
        
        if ( ! isset($options['tableName']) ||
             ! is_string($options['tableName'])) {
             
             throw \Doctrine\Common\DoctrineException::updateMe('Table name option not set.');
        }

        $this->_options = $options;
    }

    /**
     * getConnection
     * returns the connection object associated with this cache driver
     *
     * @return Doctrine_Connection      connection object
     */
    public function getConnection() 
    {
        return $this->_options['connection'];
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($id)
    {
        $sql = 'SELECT data, expire FROM ' . $this->_options['tableName']
             . ' WHERE id = ?';

        if ($testCacheValidity) {
            $sql .= ' AND (expire=0 OR expire > ' . time() . ')';
        }

        $result = $this->getConnection()->fetchAssoc($sql, array($id));
        
        if ( ! isset($result[0])) {
            return false;
        }
        
        return unserialize($result[0]['data']);
    }

    /**
     * {@inheritdoc}
     */
    public function contains($id) 
    {
        $sql = 'SELECT expire FROM ' . $this->_options['tableName']
             . ' WHERE id = ? AND (expire=0 OR expire > ' . time() . ')';

        return $this->getConnection()->fetchOne($sql, array($id));
    }

    /**
     * {@inheritdoc}
     */
    public function save($data, $id, $lifeTime = false)
    {
        $sql = 'INSERT INTO ' . $this->_options['tableName']
             . ' (id, data, expire) VALUES (?, ?, ?)';
        
        if ($lifeTime) {
            $expire = time() + $lifeTime;
        } else {
            $expire = 0;
        }
        
        $params = array($id, serialize($data), $expire);

        return (bool) $this->getConnection()->exec($sql, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id) 
    {
        $sql = 'DELETE FROM ' . $this->_options['tableName'] . ' WHERE id = ?';

        return (bool) $this->getConnection()->exec($sql, array($id));
    }

    /**
     * Removes all cache records
     *
     * $return bool true on success, false on failure
     */
    public function deleteAll()
    {
        $sql = 'DELETE FROM ' . $this->_options['tableName'];
        
        return (bool) $this->getConnection()->exec($sql);
    }

    /**
     * count
     * returns the number of cached elements
     *
     * @return integer
     */
    public function count()
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->_options['tableName'];
        
        return (int) $this->getConnection()->fetchOne($sql);
    }

    /**
     * Creates the cache table.
     */
    public function createTable()
    {
        $name = $this->_options['tableName'];
        
        $fields = array(
            'id' => array(
                'type'   => 'string',
                'length' => 255
            ),
            'data' => array(
                'type'    => 'blob'
            ),
            'expire' => array(
                'type'    => 'timestamp'
            )
        );
        
        $options = array(
            'primary' => array('id')
        );
        
        $this->getConnection()->export->createTable($name, $fields, $options);
    }
}
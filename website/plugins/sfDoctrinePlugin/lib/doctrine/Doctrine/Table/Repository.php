<?php
/*
 *  $Id: Repository.php 1080 2007-02-10 18:17:08Z romanb $
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
/**
 * Doctrine_Repository
 * each record is added into Doctrine_Repository at the same time they are created,
 * loaded from the database or retrieved from the cache
 *
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @package     Doctrine
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1080 $
 */
class Doctrine_Table_Repository implements Countable, IteratorAggregate
{
    /**
     * @var object Doctrine_Table $table
     */
    private $table;
    /**
     * @var array $registry
     * an array of all records
     * keys representing record object identifiers
     */
    private $registry = array();
    /**
     * constructor
     *
     * @param Doctrine_Table $table
     */
    public function __construct(Doctrine_Table $table)
    {
        $this->table = $table;
    }
    /**
     * getTable
     *
     * @return object Doctrine_Table
     */
    public function getTable()
    {
        return $this->table;
    }
    /**
     * add
     *
     * @param Doctrine_Record $record       record to be added into registry
     * @return boolean
     */
    public function add(Doctrine_Record $record)
    {
        $oid = $record->getOID();

        if (isset($this->registry[$oid])) {
            return false;
        }
        $this->registry[$oid] = $record;

        return true;
    }
    /**
     * get
     * @param integer $oid
     * @throws Doctrine_Table_Repository_Exception
     */
    public function get($oid)
    {
        if ( ! isset($this->registry[$oid])) {
            throw new Doctrine_Table_Repository_Exception("Unknown object identifier");
        }
        return $this->registry[$oid];
    }
    /**
     * count
     * Doctrine_Registry implements interface Countable
     * @return integer                      the number of records this registry has
     */
    public function count()
    {
        return count($this->registry);
    }
    /**
     * @param integer $oid                  object identifier
     * @return boolean                      whether ot not the operation was successful
     */
    public function evict($oid)
    {
        if ( ! isset($this->registry[$oid])) {
            return false;
        }
        unset($this->registry[$oid]);
        return true;
    }
    /**
     * @return integer                      number of records evicted
     */
    public function evictAll()
    {
        $evicted = 0;
        foreach ($this->registry as $oid=>$record) {
            if ($this->evict($oid)) {
                $evicted++;
            }
        }
        return $evicted;
    }
    /**
     * getIterator
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->registry);
    }
    /**
     * contains
     * @param integer $oid                  object identifier
     */
    public function contains($oid)
    {
        return isset($this->registry[$oid]);
    }
    /**
     * loadAll
     * @return void
     */
    public function loadAll()
    {
        $this->table->findAll();
    }
}

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

/**
 * Doctrine_Cache_TestCase
 *
 * @package     Doctrine
 * @subpackage  Doctrine_Cache
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Cache_TestCase extends Doctrine_UnitTestCase
{
    protected $cache;

    public function prepareTables()
    { }
    public function prepareData()
    { }
    /**
    public function testAdapterQueryAddsQueriesToCacheStack()
    {
        $this->dbh->query('SELECT * FROM user');

        $this->assertEqual($this->cache->getAll(), array('main' => array('SELECT * FROM user')));
    }
    */
    public function testAdapterQueryChecksCache()
    {
    	$query = 'SELECT * FROM user';

        $resultSet = array(array('name' => 'John'), array('name' => 'Arnold'));

    	$this->cache->getDriver()->save(md5($query), $resultSet);

        $count = $this->dbh->getAdapter()->count();

        $stmt = $this->dbh->query($query);
        $data = $stmt->fetchAll(Doctrine::FETCH_ASSOC);

        $this->assertEqual($data, $resultSet);
        $this->assertEqual($this->dbh->getAdapter()->count(), $count);
    }
    public function testAdapterStatementExecuteChecksCache()
    {
    	$query  = 'SELECT * FROM user WHERE id = ?';
        $params = array(1);
        $resultSet = array(array('name' => 'John'), array('name' => 'Arnold'));

    	$this->cache->getDriver()->save(md5(serialize(array($query, $params))), $resultSet);

        $count = $this->dbh->getAdapter()->count();

        $stmt = $this->dbh->prepare($query);
        $stmt->execute($params);
        $data = $stmt->fetchAll(Doctrine::FETCH_ASSOC);

        $this->assertEqual($data, $resultSet);
        $this->assertEqual($this->dbh->getAdapter()->count(), $count);
    }
    public function testFetchAdvancesCacheDataPointer()
    {
        $query  = 'SELECT * FROM user WHERE id = ?';
        $count = $this->dbh->getAdapter()->count();
        $params = array(1);
        $stmt = $this->dbh->prepare($query);
        $stmt->execute($params);

        $row1 = $stmt->fetch();
        $row2 = $stmt->fetch();

        $this->assertEqual($row1, array('name' => 'John'));
        $this->assertEqual($row2, array('name' => 'Arnold'));

        $this->assertEqual($this->dbh->getAdapter()->count(), $count);
    }

    public function testAdapterStatementExecuteAddsQueriesToCacheStack()
    {
        $stmt = $this->dbh->prepare('SELECT * FROM user');

        $stmt->execute();

        $this->assertEqual($this->cache->getAll(), array('main' => array('SELECT * FROM user')));
    }
    public function testAdapterStatementFetchCallsCacheFetch()
    {
        $stmt = $this->dbh->prepare('SELECT * FROM user');

        $stmt->execute();

        $a = $stmt->fetchAll();
    }

    public function setUp()
    {
        parent::setUp();

    	if ( ! isset($this->cache)) {
            $this->cache = new Doctrine_Cache('Array');
            $this->cache->setOption('cacheFile', false);
            $this->dbh->setAdapter(new Doctrine_Adapter_Mock());
            $this->dbh->addListener($this->cache);
        }

        $this->cache->reset();
    }
}

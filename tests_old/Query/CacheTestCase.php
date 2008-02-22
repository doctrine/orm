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
 * <http://www.phpdoctrine.org>.
 */

/**
 * Doctrine_Query_Cache_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Query_Cache_TestCase extends Doctrine_UnitTestCase 
{

    public function testQueryCacheAddsQueryIntoCache()
    {
        $cache = new Doctrine_Cache_Array();
        $q = new Doctrine_Query();
        $q->select('u.name')->from('User u')->leftJoin('u.Phonenumber p')->where('u.name = ?', 'walhala')
                ->useQueryCache($cache);
        
        $coll = $q->execute();
        
        $this->assertEqual($cache->count(), 1);
        $this->assertEqual(count($coll), 0);

        $coll = $q->execute();

        $this->assertEqual($cache->count(), 1);
        $this->assertEqual(count($coll), 0);    
    }
    
    public function testQueryCacheWorksWithGlobalConfiguration()
    {
        $cache = new Doctrine_Cache_Array();
        Doctrine_Manager::getInstance()->setAttribute(Doctrine::ATTR_QUERY_CACHE, $cache);
        
        $q = new Doctrine_Query();
        $q->select('u.name')->from('User u')->leftJoin('u.Phonenumber p');
        
        $coll = $q->execute();
        
        $this->assertEqual($cache->count(), 1);
        $this->assertEqual(count($coll), 8);

        $coll = $q->execute();

        $this->assertEqual($cache->count(), 1);
        $this->assertEqual(count($coll), 8);    
    }

    public function testResultSetCacheAddsResultSetsIntoCache()
    {
        $q = new Doctrine_Query();

        $cache = new Doctrine_Cache_Array();
        $q->useCache($cache)->select('u.name')->from('User u');
        $coll = $q->execute();

        $this->assertEqual($cache->count(), 1);
        $this->assertEqual(count($coll), 8);

        $coll = $q->execute();

        $this->assertEqual($cache->count(), 1);
        $this->assertEqual(count($coll), 8);
    }

    public function testResultSetCacheSupportsQueriesWithJoins()
    {
        $q = new Doctrine_Query();

        $cache = new Doctrine_Cache_Array();
        $q->useCache($cache);
        $q->select('u.name')->from('User u')->leftJoin('u.Phonenumber p');
        $coll = $q->execute();

        $this->assertEqual($cache->count(), 1);
        $this->assertEqual(count($coll), 8);

        $coll = $q->execute();

        $this->assertEqual($cache->count(), 1);
        $this->assertEqual(count($coll), 8);
    }

    public function testResultSetCacheSupportsPreparedStatements()
    {
        $q = new Doctrine_Query();

        $cache = new Doctrine_Cache_Array();
        $q->useCache($cache);
        $q->select('u.name')->from('User u')->leftJoin('u.Phonenumber p')
          ->where('u.id = ?');

        $coll = $q->execute(array(5));

        $this->assertEqual($cache->count(), 1);
        $this->assertEqual(count($coll), 1);

        $coll = $q->execute(array(5));

        $this->assertEqual($cache->count(), 1);
        $this->assertEqual(count($coll), 1);
    }
    
    public function testUseCacheSupportsBooleanTrueAsParameter()
    {
        $q = new Doctrine_Query();
        
        $cache = new Doctrine_Cache_Array();
        $this->conn->setAttribute(Doctrine::ATTR_CACHE, $cache);

        $q->useCache(true);
        $q->select('u.name')->from('User u')->leftJoin('u.Phonenumber p')
          ->where('u.id = ?');

        $coll = $q->execute(array(5));

        $this->assertEqual($cache->count(), 1);
        $this->assertEqual(count($coll), 1);

        $coll = $q->execute(array(5));

        $this->assertEqual($cache->count(), 1);
        $this->assertEqual(count($coll), 1);
        
        $this->conn->setAttribute(Doctrine::ATTR_CACHE, null);
    }
}

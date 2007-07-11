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
 * Doctrine_Search_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Search_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareTables()
    {
        $this->tables = array('SearchTest', 'SearchTestIndex');
        
        parent::prepareTables();
    }
    public function prepareData()
    { }
    public function testBuildingOfSearchRecordDefinition()
    {
        $e = new SearchTest();
        
        $this->assertTrue($e->SearchTestIndex instanceof Doctrine_Collection);
        
        $rel = $e->getTable()->getRelation('SearchTestIndex');

        $this->assertIdentical($rel->getLocal(), 'id');
        $this->assertIdentical($rel->getForeign(), 'searchtest_id');
    }
    public function testSavingEntriesUpdatesIndex()
    {
    	$e = new SearchTest();

        $e->title = 'Once there was an ORM framework';
        $e->content = 'There are many ORM frameworks, but nevertheless we decided to create one.';

        $e->save();
    }
    public function testQuerying()
    {
        $q = new Doctrine_Query();
        
        $q->select('t.title')
          ->from('SearchTest t')
          ->innerJoin('t.SearchTestIndex i')
          ->where('i.keyword = ?');

        $array = $q->execute(array('orm'), Doctrine_Hydrate::HYDRATE_ARRAY);
        
        $this->assertEqual($array[0]['title'], 'Once there was an ORM framework');
    }
    public function testQueryingReturnsEmptyArrayForStopKeyword()
    {
        $q = new Doctrine_Query();
        
        $q->select('t.title')
          ->from('SearchTest t')
          ->innerJoin('t.SearchTestIndex i')
          ->where('i.keyword = ?');

        $array = $q->execute(array('was'), Doctrine_Hydrate::HYDRATE_ARRAY);

        $this->assertEqual(count($array), 0);
    }
    public function testQueryingReturnsEmptyArrayForUnknownKeyword()
    {
        $q = new Doctrine_Query();
        
        $q->select('t.title')
          ->from('SearchTest t')
          ->innerJoin('t.SearchTestIndex i')
          ->where('i.keyword = ?');

        $array = $q->execute(array('someunknownword'), Doctrine_Hydrate::HYDRATE_ARRAY);

        $this->assertEqual(count($array), 0);
    }
}
class SearchTest extends Doctrine_Record 
{
    public function setTableDefinition()
    {
        $this->hasColumn('title', 'string', 100);
        $this->hasColumn('content', 'string');
    }
    public function setUp()
    {
    	$options = array('generateFiles' => false,
                         'fields' => array('title', 'content'));

        $this->loadTemplate('Doctrine_Search_Template', $options);
    }
}

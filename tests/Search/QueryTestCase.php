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
 * Doctrine_Search_Query_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Search_Query_TestCase extends Doctrine_UnitTestCase
{
    public function prepareTables()
    {
        $this->tables = array('SearchTest', 'SearchTestIndex');
        
        parent::prepareTables();
    }
    public function prepareData()
    { }

    public function testInitData()
    {
    	$e = new SearchTest();

        $e->title = 'Once there was an ORM framework';
        $e->content = 'There are many ORM frameworks, but nevertheless we decided to create one.';

        $e->save();

    	$e = new SearchTest();

        $e->title = 'Doctrine development continues';
        $e->content = 'The development has been going well so far.';

        $e->save();
    }

    public function testQuerying()
    {
        $q = new Doctrine_Query();
        $q->select('s.*')
          ->from('SearchTest s')
          ->innerJoin('s.SearchTestIndex i');

        $sq = new Doctrine_Search_Query($q);
        $sq->addAlias('i');
        $sq->search('ORM framework');
        $coll = $sq->execute();
        

    }
    
    public function testGettingRelevancyValues()
    {
    	$dql = 'SELECT s.*, 
                    (SELECT COUNT(i.id) 
                        FROM SearchTestIndex i 
                        WHERE i.keyword = ? 
                        AND i.searchtest_id = s.id) relevancy
                FROM SearchTest s';

        $q = new Doctrine_Query();

        $q->parseQuery($dql);
        $coll = $q->execute(array('orm'));

        $this->assertEqual($coll[0]->relevancy, 2);
        $this->assertEqual($coll[1]->relevancy, 0);
    }
    /**
    public function testGettingWeightedRelevancyValues()
    {
    	$dql = "SELECT s.*,
                    ((SELECT COUNT(i.id)
                        FROM SearchTestIndex i
                        WHERE i.keyword = ?
                        AND i.searchtest_id = s.id
                        AND i.field = 'title') * 2
                        +
                     (SELECT COUNT(i.id)
                        FROM SearchTestIndex i
                        WHERE i.keyword = ?
                        AND i.searchtest_id = s.id
                        AND i.field = 'content')) relevancy
                FROM SearchTest s";

        $q = new Doctrine_Query();

        $q->parseQuery($dql);
        $coll = $q->execute(array('orm'));

        $this->assertEqual($coll[0]->relevancy, 2);
        $this->assertEqual($coll[1]->relevancy, 0);
    }
    */
}

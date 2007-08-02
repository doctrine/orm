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
 * Doctrine_Search_QueryWeight_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Search_QueryWeight_TestCase extends Doctrine_UnitTestCase
{
    public function testQuerySupportsMultiWordOrOperatorSearchWithQuotes()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $q->search("doctrine^2 OR 'dbal database'");

        $sql = 'SELECT foreign_id, SUM(relevancy) AS relevancy_sum ' 
             . 'FROM (SELECT COUNT(keyword) * 2 AS relevancy, foreign_id '
             . 'FROM search_index '
             . 'WHERE keyword = ? '
             . 'GROUP BY foreign_id '
             . 'UNION '
             . 'SELECT COUNT(keyword) AS relevancy, foreign_id '
             . 'FROM search_index) AS query_alias '
             . 'WHERE keyword = ? AND (position + 1) = (SELECT position FROM search_index WHERE keyword = ?) '
             . 'GROUP BY foreign_id) '
             . 'GROUP BY foreign_id '
             . 'ORDER BY relevancy_sum';

        $this->assertEqual($q->getSql(), $sql);
    }
    
    public function testSearchSupportsMixingOfOperatorsParenthesisAndWeights()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $q->search('(doctrine^2 OR orm) AND dbal');

        $sql = "SELECT foreign_id, SUM(relevancy) AS relevancy_sum FROM
                        (SELECT COUNT(keyword) * 2 AS relevancy, foreign_id
                            FROM search_index
                            WHERE keyword = 'doctrine'
                            GROUP BY foreign_id
                    UNION
                        SELECT COUNT(keyword) * 2 AS relevancy, foreign_id
                            FROM search_index
                            WHERE keyword = 'orm'
                            GROUP BY foreign_id
                    INTERSECT
                         SELECT COUNT(keyword) AS relevancy, foreign_id
                            FROM search_index) AS query_alias
                            WHERE keyword = 'dbal'
                            GROUP BY foreign_id)
                GROUP BY foreign_id
                ORDER BY relevancy_sum";

        $this->assertEqual($q->getSql(), $sql);
    }

    public function testQuerySupportsMultiWordAndOperatorSearchWithQuotesAndWeights()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $q->search("doctrine^2 'dbal database'");

        $sql = "SELECT foreign_id, SUM(relevancy) AS relevancy_sum FROM
                        (SELECT COUNT(keyword) * 2 AS relevancy, foreign_id
                            FROM search_index
                            WHERE keyword = 'doctrine'
                            GROUP BY foreign_id
                    INTERSECT
                         SELECT COUNT(keyword) AS relevancy, foreign_id
                            FROM search_index) AS query_alias
                            WHERE keyword = 'dbal' AND (position + 1) = (SELECT position FROM search_index WHERE keyword = 'database')
                            GROUP BY foreign_id)
                GROUP BY foreign_id
                ORDER BY relevancy_sum";

        $this->assertEqual($q->getSql(), $sql);
    }

    public function testQuerySupportsMultiWordNegationOperatorSearchWithQuotesWeights()
    {
        $q = new Doctrine_Search_Query('SearchTestIndex');
        $q->search("doctrine^2 'dbal database' -rdbms");

        $sql = "SELECT foreign_id, SUM(relevancy) AS relevancy_sum FROM
                        (SELECT COUNT(keyword) * 2 AS relevancy, foreign_id
                            FROM search_index
                            WHERE keyword = 'doctrine'
                            GROUP BY foreign_id
                    INTERSECT
                         SELECT COUNT(keyword) AS relevancy, foreign_id
                            FROM search_index) AS query_alias
                            WHERE keyword = 'dbal' AND (position + 1) = (SELECT position FROM search_index WHERE keyword = 'database')
                            GROUP BY foreign_id
                    EXCEPT
                         SELECT COUNT(keyword) AS relevancy, foreign_id
                            FROM search_index) AS query_alias
                            WHERE keyword != 'rdbms'
                            GROUP BY foreign_id                            
                            )
                GROUP BY foreign_id
                ORDER BY relevancy_sum";
                
        $this->assertEqual($q->getSql(), $sql);
    }
}

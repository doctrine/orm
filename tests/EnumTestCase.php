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
 * Doctrine_Enum_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Enum_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData() 
    { }
    public function prepareTables() 
    {
        $this->tables = array("EnumTest", "EnumTest2", "EnumTest3");
        parent::prepareTables();
    }

    public function testParameterConversion() 
    {
        $test = new EnumTest();
        $test->status = 'open';
        $this->assertEqual($test->status, 'open');
        $test->save();

        try {
            $query = new Doctrine_Query($this->connection);
            $ret = $query->parseQuery('FROM EnumTest WHERE EnumTest.status = ?')
                         ->execute(array('open'));

            $this->assertEqual(count($ret), 1);
        } catch (Exception $e) {
            $this->fail();
        }

        try {
            $query = new Doctrine_Query($this->connection);
            $ret = $query->query("FROM EnumTest WHERE EnumTest.status = 'open'");
            $this->assertEqual(count($ret), 1);
        } catch (Exception $e) {
          $this->fail();
        }
    }

    public function testEnumFetchArray() {
        $q = new Doctrine_Query();
        $q->select('e.*')
          ->from('EnumTest e')
          ->limit(1);
        $ret = $q->execute(array(), Doctrine::FETCH_ARRAY);
        if (is_numeric($ret[0]['status']))
        {
          $this->fail();
        }
    }

    public function testInAndNotIn() 
    {
        try {
            $query = new Doctrine_Query($this->connection);
            $ret = $query->query("FROM EnumTest WHERE EnumTest.status IN ('open')");
            $this->assertEqual(count($ret), 1);
        } catch (Exception $e) {
            $this->fail();
        }

        try {
            $query = new Doctrine_Query($this->connection);
            $ret = $query->query("FROM EnumTest WHERE EnumTest.status NOT IN ('verified', 'closed')");
            $this->assertEqual(count($ret), 1);
        } catch (Exception $e) {
            $this->fail();
        }
    }

    public function testExpressionComposition() 
    {
        try {
            $query = new Doctrine_Query($this->connection);
            $ret = $query->query("FROM EnumTest e WHERE e.id > 0 AND (e.status != 'closed' OR e.status = 'verified')");
            $this->assertEqual(count($ret), 1);
        } catch (Exception $e) {
            $this->fail();
        }
    }
    public function testNotEqual() 
    {
        try {
            $query = new Doctrine_Query($this->connection);
            $ret = $query->query("FROM EnumTest WHERE EnumTest.status != 'closed'");
            $this->assertEqual(count($ret), 1);
        } catch (Exception $e) {
            $this->fail();
        }
    }

    public function testEnumType() 
    {

        $enum = new EnumTest();
        $enum->status = 'open';
        $this->assertEqual($enum->status, 'open');
        $enum->save();
        $this->assertEqual($enum->status, 'open');
        $enum->refresh();
        $this->assertEqual($enum->status, 'open');

        $enum->status = 'closed';

        $this->assertEqual($enum->status, 'closed');

        $enum->save();
        $this->assertEqual($enum->status, 'closed');
        $this->assertTrue(is_numeric($enum->id));
        $enum->refresh();
        $this->assertEqual($enum->status, 'closed');
    }

    public function testEnumTypeWithCaseConversion() 
    {
        $this->conn->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);

        $enum = new EnumTest();

        $enum->status = 'open';
        $this->assertEqual($enum->status, 'open');

        $enum->save();
        $this->assertEqual($enum->status, 'open');

        $enum->refresh();
        $this->assertEqual($enum->status, 'open');      
        
        $enum->status = 'closed';

        $this->assertEqual($enum->status, 'closed');

        $enum->save();
        $this->assertEqual($enum->status, 'closed');

        $enum->refresh();
        $this->assertEqual($enum->status, 'closed');

        $this->conn->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
    }

    public function testFailingRefresh() 
    {
        $enum = $this->connection->getTable('EnumTest')->find(1);

        $this->conn->exec('DELETE FROM enum_test WHERE id = 1');

        $f = false;
        try {
            $enum->refresh();
        } catch(Doctrine_Record_Exception $e) {
            $f = true;
        }
        $this->assertTrue($f);
    }

    public function testEnumWithLeftJoin()
    {
        // sorry, odd I know, best I can do...   pookey!
        try {
            $q = new Doctrine_Query($this->connection);
            $q->select('e.*, f.*, g.status')
              ->from('EnumTest e')
              ->innerjoin('e.Enum2 g')
              ->leftjoin('e.Enum3 f')
              ->where("e.status = 'verified'")
              ->limit(1)
              ->execute();
            $this->assertNotEqual($q->getQuery(), "SELECT e.id AS e__id, e.status AS e__status, e.text AS e__text, e2.id AS e2__id, e2.status AS e2__status, e3.text AS e3__text FROM enum_test e INNER JOIN enum_test2 e2 ON e.id = e2.enum_test_id LEFT JOIN enum_test3 e3 ON e.text = e3.text WHERE e.id IN (SELECT DISTINCT e4.id FROM enum_test e4 INNER JOIN enum_test2 e5 ON e4.id = e5.enum_test_id LEFT JOIN enum_test3 e6 ON e4.text = e6.text WHERE e4.status = 'verified' LIMIT 1) AND e.status = 'verified'");
        } catch (Exception $e) {
            $this->fail();
        }
    }
}
?>

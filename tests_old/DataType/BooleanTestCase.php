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
 * Doctrine_DataType_Boolean_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_DataType_Boolean_TestCase extends Doctrine_UnitTestCase {
    public function prepareData() { }
    public function prepareTables() {
        $this->tables = array("BooleanTest");
        parent::prepareTables();
    }
   
    public function testSetFalse() {
        $test = new BooleanTest();
        $test->is_working = false;

        $this->assertIdentical($test->is_working, false);
        $this->assertEqual($test->state(), Doctrine_Entity::STATE_TDIRTY);
        $test->save();

        $test->refresh();
        $this->assertIdentical($test->is_working, false);
    }

    public function testSetTrue() {
        $test = new BooleanTest();
        $test->is_working = true;
        $this->assertIdentical($test->is_working, true);
        $test->save();
        
        $test->refresh();
        $this->assertIdentical($test->is_working, true);
        
        $this->connection->clear();
        
        $test = $test->getRepository()->find($test->id);
        $this->assertIdentical($test->is_working, true);
    }
    public function testNormalQuerying() {
        $query = new Doctrine_Query($this->connection);
        $ret = $query->query('FROM BooleanTest WHERE BooleanTest.is_working = 0');
        $this->assertEqual(count($ret), 1);

        $query = new Doctrine_Query($this->connection);
        $ret = $query->query('FROM BooleanTest WHERE BooleanTest.is_working = 1');

        $this->assertEqual(count($ret), 1);
    }
    public function testPreparedQueries() {
        $query = new Doctrine_Query($this->connection);
        $ret = $query->query('FROM BooleanTest WHERE BooleanTest.is_working = ?', array(false));
        $this->assertEqual(count($ret), 1);

        $query = new Doctrine_Query($this->connection);
        $ret = $query->query('FROM BooleanTest WHERE BooleanTest.is_working = ?', array(true));
        $this->assertEqual(count($ret), 1);
    }
    public function testFetchingWithSmartConversion() {
        $query = new Doctrine_Query($this->connection);
        $ret = $query->query('FROM BooleanTest WHERE BooleanTest.is_working = false');
        $this->assertEqual(count($ret), 1);

        $query = new Doctrine_Query($this->connection);
        $ret = $query->query('FROM BooleanTest WHERE BooleanTest.is_working = true');

        $this->assertEqual(count($ret), 1);
    }

    public function testSavingNullValue() {
        $test = new BooleanTest();
        $this->is_working = null;

        $this->assertIdentical($this->is_working, null);
        $this->assertEqual($test->state(), Doctrine_Entity::STATE_TDIRTY);
        $test->save();

        $test->refresh();
        $this->assertIdentical($test->is_working, null);
        
        $test = new BooleanTest();
        $this->is_working_notnull = null;

        $this->assertIdentical($this->is_working_notnull, null);
        $this->assertEqual($test->state(), Doctrine_Entity::STATE_TDIRTY);
        $test->save();

        $test->refresh();
        $this->assertIdentical($test->is_working_notnull, false);
    }

}

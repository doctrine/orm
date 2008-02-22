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
 * Doctrine_Expression_Driver_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Expression_Driver_TestCase extends Doctrine_UnitTestCase {

    /**
     * AGGREGATE FUNCTIONS
     */
    public function testAvgReturnsValidSql() {
        $this->expr = new Doctrine_Expression_Mock();

        $this->assertEqual($this->expr->avg('id'), 'AVG(id)');
    }
    public function testCountReturnsValidSql() {
        $this->assertEqual($this->expr->count('id'), 'COUNT(id)');
    }
    public function testMaxReturnsValidSql() {
        $this->assertEqual($this->expr->max('id'), 'MAX(id)');
    }
    public function testMinReturnsValidSql() {
        $this->assertEqual($this->expr->min('id'), 'MIN(id)');
    }
    public function testSumReturnsValidSql() {
        $this->assertEqual($this->expr->sum('id'), 'SUM(id)');
    }

    public function testRegexpImplementedOnlyAtDriverLevel() {
        try {
            $this->expr->regexp('[abc]');
            $this->fail();
        } catch(Doctrine_Expression_Exception $e) {
            $this->pass();
        }
    }
    public function testSoundexImplementedOnlyAtDriverLevel() {
        try {
            $this->expr->soundex('arnold');
            $this->fail();
        } catch(Doctrine_Expression_Exception $e) {
            $this->pass();
        }
    }

    /**
     * TIME FUNCTIONS
     */
    public function testNowReturnsValidSql() {
        $this->assertEqual($this->expr->now(), 'NOW()');
    }

    /**
     * STRING FUNCTIONS
     */
    public function testUpperReturnsValidSql() {
        $this->assertEqual($this->expr->upper('id', 3), 'UPPER(id)');
    }
    public function testLowerReturnsValidSql() {
        $this->assertEqual($this->expr->lower('id'), 'LOWER(id)');
    }
    public function testLengthReturnsValidSql() {
        $this->assertEqual($this->expr->length('id'), 'LENGTH(id)');
    }
    public function testLtrimReturnsValidSql() {
        $this->assertEqual($this->expr->ltrim('id'), 'LTRIM(id)');
    }
    public function testLocateReturnsValidSql() {
        $this->assertEqual($this->expr->locate('id', 3), 'LOCATE(id, 3)');
    }
    public function testConcatReturnsValidSql() {
        $this->assertEqual($this->expr->concat('id', 'type'), 'id || type');
    }
    public function testSubstringReturnsValidSql() {
        $this->assertEqual($this->expr->substring('id', 3), 'SUBSTRING(id FROM 3)');

        $this->assertEqual($this->expr->substring('id', 3, 2), 'SUBSTRING(id FROM 3 FOR 2)');
    }

    /**
     * MATH FUNCTIONS
     */
    public function testRoundReturnsValidSql() {
        $this->assertEqual($this->expr->round(2.3), 'ROUND(2.3, 0)');

        $this->assertEqual($this->expr->round(2.3, 1), 'ROUND(2.3, 1)');
    }
    public function testModReturnsValidSql() {
        $this->assertEqual($this->expr->mod(2, 3), 'MOD(2, 3)');
    }
    public function testSubReturnsValidSql() {
        $this->assertEqual($this->expr->sub(array(2, 3)), '(2 - 3)');
    }
    public function testMulReturnsValidSql() {
        $this->assertEqual($this->expr->mul(array(2, 3)), '(2 * 3)');
    }
    public function testAddReturnsValidSql() {
        $this->assertEqual($this->expr->add(array(2, 3)), '(2 + 3)');
    }
    public function testDivReturnsValidSql() {
        $this->assertEqual($this->expr->div(array(2, 3)), '(2 / 3)');
    }

    /**
     * ASSERT OPERATORS
     */
    public function testEqReturnsValidSql() {
        $this->assertEqual($this->expr->eq(1, 1), '1 = 1');
    }
    public function testNeqReturnsValidSql() {
        $this->assertEqual($this->expr->neq(1, 2), '1 <> 2');
    }
    public function testGtReturnsValidSql() {
        $this->assertEqual($this->expr->gt(2, 1), '2 > 1');
    }
    public function testGteReturnsValidSql() {
        $this->assertEqual($this->expr->gte(1, 1), '1 >= 1');
    }
    public function testLtReturnsValidSql() {
        $this->assertEqual($this->expr->lt(1, 2), '1 < 2');
    }
    public function testLteReturnsValidSql() {
        $this->assertEqual($this->expr->lte(1, 1), '1 <= 1');
    }

    /**
     * WHERE OPERATORS
     */
    public function testNotReturnsValidSql() {
        $this->assertEqual($this->expr->not('id'), 'NOT(id)');
    }
    public function testInReturnsValidSql() {
        $this->assertEqual($this->expr->in('id', array(1, 2)), 'id IN (1, 2)');
    }
    public function testIsNullReturnsValidSql() {
        $this->assertEqual($this->expr->isNull('type'), 'type IS NULL');
    }
    public function testIsNotNullReturnsValidSql() {
        $this->assertEqual($this->expr->isNotNull('type'), 'type IS NOT NULL');
    }
    public function testBetweenReturnsValidSql() {
        $this->assertEqual($this->expr->between('age', 12, 14), 'age BETWEEN 12 AND 14');
    }
}

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
 * Doctrine_Connection_Pgsql_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Connection_Pgsql_TestCase extends Doctrine_UnitTestCase {
    public function testNoSuchTableErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'table test does not exist')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHTABLE);
    }
    public function testNoSuchTableErrorIsSupported2() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'relation does not exist')));

        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHTABLE);
    }
    public function testNoSuchTableErrorIsSupported3() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'sequence does not exist')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHTABLE);
    }
    public function testNoSuchTableErrorIsSupported4() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'class xx not found')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHTABLE);
    }
    public function testSyntaxErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'parser: parse error at or near')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_SYNTAX);
    }
    public function testSyntaxErrorIsSupported2() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'syntax error at')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_SYNTAX);
    }
    public function testSyntaxErrorIsSupported3() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'column reference r.r is ambiguous')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_SYNTAX);
    }
    public function testInvalidNumberErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'pg_atoi: error in somewhere: can\'t parse ')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_INVALID_NUMBER);
    }
    public function testInvalidNumberErrorIsSupported2() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'value unknown is out of range for type bigint')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_INVALID_NUMBER);
    }
    public function testInvalidNumberErrorIsSupported3() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'integer out of range')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_INVALID_NUMBER);
    }
    public function testInvalidNumberErrorIsSupported4() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 0, 'invalid input syntax for type integer')));

        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_INVALID_NUMBER);
    }
    public function testNoSuchFieldErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 0, 'column name (of relation xx) does not exist'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHFIELD);
    }
    public function testNoSuchFieldErrorIsSupported2() {
        $this->exc->processErrorInfo(array(0, 0, 'attribute xx not found'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHFIELD);
    }
    public function testNoSuchFieldErrorIsSupported3() {
        $this->exc->processErrorInfo(array(0, 0, 'relation xx does not have attribute'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHFIELD);
    }
    public function testNoSuchFieldErrorIsSupported4() {
        $this->exc->processErrorInfo(array(0, 0, 'column xx specified in USING clause does not exist in left table'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHFIELD);
    }
    public function testNoSuchFieldErrorIsSupported5() {
        $this->exc->processErrorInfo(array(0, 0, 'column xx specified in USING clause does not exist in right table'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHFIELD);
    }
    public function testNotFoundErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 0, 'index xx does not exist/'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOT_FOUND);
    }
    
    public function testNotNullConstraintErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 0, 'violates not-null constraint'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT_NOT_NULL);
    }
    public function testConstraintErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 0, 'referential integrity violation'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testConstraintErrorIsSupported2() {
        $this->exc->processErrorInfo(array(0, 0, 'violates xx constraint'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testInvalidErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 0, 'value too long for type character'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_INVALID);
    }
    public function testAlreadyExistsErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 0, 'relation xx already exists'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_ALREADY_EXISTS);
    }
    public function testDivZeroErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 0, 'division by zero'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_DIVZERO);
    }
    public function testDivZeroErrorIsSupported2() {
        $this->exc->processErrorInfo(array(0, 0, 'divide by zero'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_DIVZERO);
    }
    public function testAccessViolationErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 0, 'permission denied'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_ACCESS_VIOLATION);
    }
    public function testValueCountOnRowErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 0, 'more expressions than target columns'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_VALUE_COUNT_ON_ROW);
    }
}

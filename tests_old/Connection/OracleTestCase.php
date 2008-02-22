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
 * Doctrine_Connection_Oracle_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Connection_Oracle_TestCase extends Doctrine_UnitTestCase {
    public function testNoSuchTableErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 942, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHTABLE);
    }
    public function testSyntaxErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 900, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_SYNTAX);
    }
    public function testSyntaxErrorIsSupported2() {
        $this->exc->processErrorInfo(array(0, 921, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_SYNTAX);
    }
    public function testSyntaxErrorIsSupported3() {
        $this->exc->processErrorInfo(array(0, 923, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_SYNTAX);
    }
    public function testNoSuchFieldErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 904, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHFIELD);
    }
    public function testConstraintErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 1, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testConstraintErrorIsSupported2() {
        $this->exc->processErrorInfo(array(0, 2291, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testConstraintErrorIsSupported3() {
        $this->exc->processErrorInfo(array(0, 2449, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testConstraintErrorIsSupported4() {
        $this->exc->processErrorInfo(array(0, 2292, ''));

        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testNoSuchTableErrorIsSupported4() {
        $this->exc->processErrorInfo(array(0, 2289, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHTABLE);
    }
    public function testInvalidNumberErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 1722, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_INVALID_NUMBER);
    }
    public function testDivZeroErrorIsSupported1() {
        $this->exc->processErrorInfo(array(0, 1476, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_DIVZERO);
    }
    public function testNotFoundErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 1418, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOT_FOUND);
    }
    public function testNotNullConstraintErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 1400, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT_NOT_NULL);
    }
    public function testNotNullConstraintErrorIsSupported2() {
        $this->exc->processErrorInfo(array(0, 1407, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT_NOT_NULL);
    }
    public function testInvalidErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 1401, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_INVALID);
    }
    public function testAlreadyExistsErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 955, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_ALREADY_EXISTS);
    }    
    public function testValueCountOnRowErrorIsSupported() {
        $this->exc->processErrorInfo(array(0, 913, ''));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_VALUE_COUNT_ON_ROW);
    }
}

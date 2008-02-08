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
 * Doctrine_Connection_Mysql_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Connection_Mysql_TestCase extends Doctrine_UnitTestCase {
    public function testQuoteIdentifier() {
        $id = $this->connection->quoteIdentifier('identifier', false);
        $this->assertEqual($id, '`identifier`');
    }
    public function testNotLockedErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1100, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOT_LOCKED);
    }
    public function testNotFoundErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1091, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOT_FOUND);
    }
    public function testSyntaxErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1064, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_SYNTAX);
    }
    public function testNoSuchDbErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1049, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHDB);
    }
    public function testNoSuchFieldErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1054, '')));

        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHFIELD);
    }
    public function testNoSuchTableErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1051, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHTABLE);
    }
    public function testNoSuchTableErrorIsSupported2() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1146, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHTABLE);
    }
    public function testConstraintErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1048, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testConstraintErrorIsSupported2() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1216, '')));

        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testConstraintErrorIsSupported3() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1217, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testNoDbSelectedErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1046, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NODBSELECTED);
    }
    public function testAccessViolationErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1142, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_ACCESS_VIOLATION);
    }
    public function testAccessViolationErrorIsSupported2() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1044, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_ACCESS_VIOLATION);
    }
    public function testCannotDropErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1008, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CANNOT_DROP);
    }
    public function testCannotCreateErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1004, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CANNOT_CREATE);
    }
    public function testCannotCreateErrorIsSupported2() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1005, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CANNOT_CREATE);
    }
    public function testCannotCreateErrorIsSupported3() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1006, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CANNOT_CREATE);
    }
    public function testAlreadyExistsErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1007, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_ALREADY_EXISTS);
    }
    public function testAlreadyExistsErrorIsSupported2() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1022, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_ALREADY_EXISTS);
    }
    public function testAlreadyExistsErrorIsSupported3() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1050, '')));

        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_ALREADY_EXISTS);
    }
    public function testAlreadyExistsErrorIsSupported4() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1061, '')));

        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_ALREADY_EXISTS);
    }
    public function testAlreadyExistsErrorIsSupported5() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1062, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_ALREADY_EXISTS);
    }
    public function testValueCountOnRowErrorIsSupported() {
        $this->assertTrue($this->exc->processErrorInfo(array(0, 1136, '')));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_VALUE_COUNT_ON_ROW);
    }
}

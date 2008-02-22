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
 * Doctrine_Connection_Sqlite_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Connection_Sqlite_TestCase extends Doctrine_UnitTestCase { 
    public function testNoSuchTableErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, 'no such table: test1'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHTABLE);
    }
    public function testNoSuchIndexErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, 'no such index: test1'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOT_FOUND);
    }
    public function testUniquePrimaryKeyErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, 'PRIMARY KEY must be unique'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testIsNotUniqueErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, 'is not unique'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testColumnsNotUniqueErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, 'columns name, id are not unique'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testUniquenessConstraintErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, 'uniqueness constraint failed'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT);
    }
    public function testNotNullConstraintErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, 'may not be NULL'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_CONSTRAINT_NOT_NULL);
    }
    public function testNoSuchFieldErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, 'no such column: column1'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHFIELD);
    }
    public function testColumnNotPresentInTablesErrorIsSupported2() {
        $this->exc->processErrorInfo(array(0,0, 'column not present in both tables'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_NOSUCHFIELD);
    }
    public function testNearSyntaxErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, "near \"SELECT FROM\": syntax error"));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_SYNTAX);
    }
    public function testValueCountOnRowErrorIsSupported() {
        $this->exc->processErrorInfo(array(0,0, '3 values for 2 columns'));
        
        $this->assertEqual($this->exc->getPortableCode(), Doctrine::ERR_VALUE_COUNT_ON_ROW);
    }
}

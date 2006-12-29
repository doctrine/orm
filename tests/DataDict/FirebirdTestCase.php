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
 * Doctrine_DataDict_Firebird_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_DataDict_Firebird_TestCase extends Doctrine_UnitTestCase {
    public function testGetCharsetFieldDeclarationReturnsValidSql() {
        $this->assertEqual($this->dataDict->getCharsetFieldDeclaration('UTF-8'), 'CHARACTER SET UTF-8');
    }
    public function testGetCollationFieldDeclarationReturnsValidSql() {
        $this->assertEqual($this->dataDict->getCollationFieldDeclaration('xx'), 'COLLATE xx');
    }
    public function testGetPortableTypeForUnknownDbTypeThrowsException() {
        try {
            $this->dataDict->getPortableDeclaration(array('type' => 'unknown'));
            $this->fail();
        } catch(Doctrine_DataDict_Firebird_Exception $e) {
            $this->pass();
        }
    }
    public function testGetPortableTypeSupportsNativeDateType() {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'date'));
        
        $this->assertEqual($type, array(array('date'), null, null, null));
    }
    public function testGetPortableTypeSupportsNativeTimestampType() {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'timestamp'));
        
        $this->assertEqual($type, array(array('timestamp'), null, null, null));
    }
    public function testGetPortableTypeSupportsNativeTimeType() {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'time'));
        
        $this->assertEqual($type, array(array('time'), null, null, null));
    }
    public function testGetPortableTypeSupportsNativeFloatType() {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'float'));

        $this->assertEqual($type, array(array('float'), null, null, null));
    }
    public function testGetPortableTypeSupportsNativeDoubleType() {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'double'));

        $this->assertEqual($type, array(array('float'), null, null, null));
    }
    public function testGetPortableTypeSupportsNativeDoublePrecisionType() {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'double precision'));

        $this->assertEqual($type, array(array('float'), null, null, null));
    }
    public function testGetPortableTypeSupportsNativeDfloatType() {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'd_float'));
        
        $this->assertEqual($type, array(array('float'), null, null, null));
    }
    public function testGetPortableTypeSupportsNativeDecimalType() {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'decimal'));
        
        $this->assertEqual($type, array(array('decimal'), null, null, null));
    }
    public function testGetPortableTypeSupportsNativeNumericType() {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'numeric'));

        $this->assertEqual($type, array(array('decimal'), null, null, null));
    }
    public function testGetPortableTypeSupportsNativeBlobType() {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'blob'));

        $this->assertEqual($type, array(array('blob'), null, null, null));
    }
    public function testGetPortableTypeSupportsNativeVarcharType() {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'varchar'));

        $this->assertEqual($type, array(array('string'), null, null, false));
    }
    public function testGetPortableTypeSupportsNativeCharType() {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'char'));

        $this->assertEqual($type, array(array('string'), null, null, true));
    }
    public function testGetPortableTypeSupportsNativeCstringType() {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'cstring'));

        $this->assertEqual($type, array(array('string'), null, null, true));
    }
    public function testGetPortableTypeSupportsNativeBigintType() {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'bigint'));

        $this->assertEqual($type, array(array('integer'), null, null, null));
    }
    public function testGetPortableTypeSupportsNativeQuadType() {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'quad'));

        $this->assertEqual($type, array(array('integer'), null, null, null));
    }
    public function testGetNativeDefinitionSupportsIntegerType() {
        $a = array('type' => 'integer', 'length' => 20, 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'INT');

        $a['length'] = 4;

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'INT');

        $a['length'] = 2;

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'INT');
    }

    public function testGetNativeDefinitionSupportsFloatType() {
        $a = array('type' => 'float', 'length' => 20, 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'DOUBLE PRECISION');
    }
    public function testGetNativeDefinitionSupportsBooleanType() {
        $a = array('type' => 'boolean', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'SMALLINT');
    }
    public function testGetNativeDefinitionSupportsDateType() {
        $a = array('type' => 'date', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'DATE');
    }
    public function testGetNativeDefinitionSupportsTimestampType() {
        $a = array('type' => 'timestamp', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'TIMESTAMP');
    }
    public function testGetNativeDefinitionSupportsTimeType() {
        $a = array('type' => 'time', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'TIME');
    }
    public function testGetNativeDefinitionSupportsClobType() {
        $a = array('type' => 'clob');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'BLOB SUB_TYPE 1');
    }
    public function testGetNativeDefinitionSupportsBlobType() {
        $a = array('type' => 'blob');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'BLOB SUB_TYPE 0');
    }
    public function testGetNativeDefinitionSupportsCharType() {
        $a = array('type' => 'char', 'length' => 10);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'CHAR(10)');
    }
    public function testGetNativeDefinitionSupportsVarcharType() {
        $a = array('type' => 'varchar', 'length' => 10);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'VARCHAR(10)');
    }
    public function testGetNativeDefinitionSupportsArrayType() {
        $a = array('type' => 'array', 'length' => 40);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'VARCHAR(40)');
    }
    public function testGetNativeDefinitionSupportsStringType() {
        $a = array('type' => 'string');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'VARCHAR(16777215)');
    }
    public function testGetNativeDefinitionSupportsArrayType2() {
        $a = array('type' => 'array');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'VARCHAR(16777215)');
    }
    public function testGetNativeDefinitionSupportsObjectType() {
        $a = array('type' => 'object');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'VARCHAR(16777215)');
    }
}

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
 * Doctrine_DataDict_Oracle_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_DataDict_Pgsql_TestCase extends Doctrine_UnitTestCase 
{
    public function getDeclaration($type) 
    {
        return $this->dataDict->getPortableDeclaration(array('type' => $type, 'name' => 'colname', 'length' => 2, 'fixed' => true));
    }
    public function testGetPortableDeclarationForUnknownNativeTypeThrowsException() 
    {
        try {
            $this->dataDict->getPortableDeclaration(array('type' => 'some_unknown_type'));
            $this->fail();
        } catch(Doctrine_DataDict_Exception $e) {
            $this->pass();
        }
    }   
    public function testGetPortableDeclarationSupportsNativeBlobTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'blob'));
        
        $this->assertEqual($type, array('type' => array('blob'), 
                                        'length' => null, 
                                        'unsigned' => null, 
                                        'fixed' => null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'tinyblob'));

        $this->assertEqual($type, array('type' => array('blob'), 
                                        'length' => null, 
                                        'unsigned' => null, 
                                        'fixed' => null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'mediumblob'));

        $this->assertEqual($type, array('type' => array('blob'), 
                                        'length' => null, 
                                        'unsigned' => null, 
                                        'fixed' => null));
        
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'longblob'));

        $this->assertEqual($type, array('type' => array('blob'), 
                                        'length' => null, 
                                        'unsigned' => null, 
                                        'fixed' => null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'bytea'));

        $this->assertEqual($type, array('type' => array('blob'), 
                                        'length' => null, 
                                        'unsigned' => null, 
                                        'fixed' => null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'oid'));

        $this->assertEqual($type, array('type' => array('blob', 'clob'),
                                        'length' => null, 
                                        'unsigned' => null,
                                        'fixed' => null));
    }
    public function testGetPortableDeclarationSupportsNativeTimestampTypes()
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'timestamp'));
        
        $this->assertEqual($type, array('type' => array('timestamp'),
                                        'length' => null, 
                                        'unsigned' => null, 
                                        'fixed' => null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'datetime'));
        
        $this->assertEqual($type, array('type' => array('timestamp'),
                                        'length' => null, 
                                        'unsigned' => null, 
                                        'fixed' => null));
    }
    public function testGetPortableDeclarationSupportsNativeDecimalTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'decimal'));

        $this->assertEqual($type, array('type' => array('decimal'),
                                        'length' => null, 
                                        'unsigned' => null, 
                                        'fixed' => null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'money'));

        $this->assertEqual($type, array('type' => array('decimal'),
                                        'length' => null, 
                                        'unsigned' => null, 
                                        'fixed' => null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'numeric'));

        $this->assertEqual($type, array('type' => array('decimal'),
                                        'length' => null, 
                                        'unsigned' => null, 
                                        'fixed' => null));
    }
    public function testGetPortableDeclarationSupportsNativeFloatTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'float'));
        
        $this->assertEqual($type, array('type' => array('float'),
                                        'length' => null, 
                                        'unsigned' => null, 
                                        'fixed' => null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'double'));
        
        $this->assertEqual($type, array('type' => array('float'),
                                        'length' => null, 
                                        'unsigned' => null, 
                                        'fixed' => null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'real'));
        
        $this->assertEqual($type, array('type' => array('float'),
                                        'length' => null, 
                                        'unsigned' => null, 
                                        'fixed' => null));
    }
    public function testGetPortableDeclarationSupportsNativeYearType() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'year'));

        $this->assertEqual($type, array('type' => array('integer', 'date'),
                                        'length' => null, 
                                        'unsigned' => null, 
                                        'fixed' => null));
    }
    public function testGetPortableDeclarationSupportsNativeDateType() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'date'));
        
        $this->assertEqual($type, array('type' => array('date'),
                                        'length' => null, 
                                        'unsigned' => null, 
                                        'fixed' => null));
    }
    public function testGetPortableDeclarationSupportsNativeTimeType() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'time'));
        
        $this->assertEqual($type, array('type' => array('time'),
                                        'length' => null, 
                                        'unsigned' => null, 
                                        'fixed' => null));
    }
    public function testGetPortableDeclarationSupportsNativeStringTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'text'));

        $this->assertEqual($type, array('type' => array('string', 'clob'),
                                        'length' => null, 
                                        'unsigned' => null, 
                                        'fixed' => null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'interval'));

        $this->assertEqual($type, array('type' => array('string'),
                                        'length' => null,
                                        'unsigned' => null,
                                        'fixed' => false));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'varchar', 'length' => 1));

        $this->assertEqual($type, array('type' => array('string', 'boolean'),
                                        'length' => 1,
                                        'unsigned' => null,
                                        'fixed' => false));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'unknown', 'length' => 1));
        
        $this->assertEqual($type, array('type' => array('string', 'boolean'),
                                        'length' => 1,
                                        'unsigned' => null,
                                        'fixed' => true));

        
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'char', 'length' => 1));

        $this->assertEqual($type, array('type' => array('string', 'boolean'),
                                        'length' => 1,
                                        'unsigned' => null,
                                        'fixed' => true));


        $type = $this->dataDict->getPortableDeclaration(array('type' => 'bpchar', 'length' => 1));
        
        $this->assertEqual($type, array('type' => array('string', 'boolean'),
                                        'length' => 1,
                                        'unsigned' => null,
                                        'fixed' => true));

    }
    public function testGetPortableDeclarationSupportsNativeIntegerTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'smallint'));

        $this->assertEqual($this->getDeclaration('smallint'), array('type' => array('integer', 'boolean'), 'length' => 2, 'unsigned' => false, 'fixed' => null));
        $this->assertEqual($this->getDeclaration('int2'), array('type' => array('integer', 'boolean'), 'length' => 2, 'unsigned' => false, 'fixed' => null));

        $this->assertEqual($this->getDeclaration('int'), array('type' => array('integer'), 'length' => 4, 'unsigned' => false, 'fixed' => null));
        $this->assertEqual($this->getDeclaration('int4'), array('type' => array('integer'), 'length' => 4, 'unsigned' => false, 'fixed' => null));
        $this->assertEqual($this->getDeclaration('integer'), array('type' => array('integer'), 'length' => 4, 'unsigned' => false, 'fixed' => null));
        $this->assertEqual($this->getDeclaration('serial'), array('type' => array('integer'), 'length' => 4, 'unsigned' => false, 'fixed' => null));
        $this->assertEqual($this->getDeclaration('serial4'), array('type' => array('integer'), 'length' => 4, 'unsigned' => false, 'fixed' => null));

        $this->assertEqual($this->getDeclaration('bigint'), array('type' => array('integer'), 'length' => 8, 'unsigned' => false, 'fixed' => null));
        $this->assertEqual($this->getDeclaration('int8'), array('type' => array('integer'), 'length' => 8, 'unsigned' => false, 'fixed' => null));
        $this->assertEqual($this->getDeclaration('bigserial'), array('type' => array('integer'), 'length' => 8, 'unsigned' => false, 'fixed' => null));
        $this->assertEqual($this->getDeclaration('serial8'), array('type' => array('integer'), 'length' => 8, 'unsigned' => false, 'fixed' => null));
    }
    public function testGetPortableDeclarationSupportsNativeBooleanTypes()
    {
        $this->assertEqual($this->getDeclaration('bool'), array('type' => array('boolean'), 'length' => 1, 'unsigned' => false, 'fixed' => null));
        $this->assertEqual($this->getDeclaration('boolean'), array('type' => array('boolean'), 'length' => 1, 'unsigned' => false, 'fixed' => null));
    }

    public function testGetNativeDefinitionSupportsIntegerType() 
    {
        $a = array('type' => 'integer', 'length' => 20, 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'BIGINT');
        
        $a['length'] = 4;

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'INT');

        $a['length'] = 2;

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'SMALLINT');
    }
    public function testGetNativeDefinitionSupportsIntegerTypeWithAutoinc() 
    {
        $a = array('type' => 'integer', 'length' => 20, 'fixed' => false, 'autoincrement' => true);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'BIGSERIAL');

        $a['length'] = 4;

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'SERIAL');

        $a['length'] = 2;

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'SERIAL');
    }
    public function testGetNativeDefinitionSupportsFloatType() 
    {
        $a = array('type' => 'float', 'length' => 20, 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'FLOAT');
    }
    public function testGetNativeDefinitionSupportsBooleanType()
    {
        $a = array('type' => 'boolean', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'BOOLEAN');
    }
    public function testGetNativeDefinitionSupportsDateType() 
    {
        $a = array('type' => 'date', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'DATE');
    }
    public function testGetNativeDefinitionSupportsTimestampType() 
    {
        $a = array('type' => 'timestamp', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'TIMESTAMP without time zone');
    }
    public function testGetNativeDefinitionSupportsTimeType() 
    {
        $a = array('type' => 'time', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'TIME without time zone');
    }
    public function testGetNativeDefinitionSupportsClobType() 
    {
        $a = array('type' => 'clob');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'TEXT');
    }
    public function testGetNativeDefinitionSupportsBlobType() 
    {
        $a = array('type' => 'blob');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'BYTEA');
    }
    public function testGetNativeDefinitionSupportsCharType() 
    {
        $a = array('type' => 'char', 'length' => 10);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'CHAR(10)');
    }
    public function testGetNativeDefinitionSupportsVarcharType() 
    {
        $a = array('type' => 'varchar', 'length' => 10);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'VARCHAR(10)');
    }
    public function testGetNativeDefinitionSupportsArrayType() 
    {
        $a = array('type' => 'array', 'length' => 40);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'VARCHAR(40)');
    }
    public function testGetNativeDefinitionSupportsStringType() 
    {
        $a = array('type' => 'string');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'TEXT');
    }
    public function testGetNativeDefinitionSupportsArrayType2() 
    {
        $a = array('type' => 'array');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'TEXT');
    }
    public function testGetNativeDefinitionSupportsObjectType() 
    {
        $a = array('type' => 'object');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'TEXT');
    }
}

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
 * Doctrine_DataDict_Oracle_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_DataDict_Oracle_TestCase extends Doctrine_UnitTestCase {
    public function testGetPortableDeclarationForUnknownNativeTypeThrowsException() 
    {
        try {
            $this->dataDict->getPortableDeclaration(array('data_type' => 'some_unknown_type'));
            $this->fail();
        } catch(Doctrine_DataDict_Exception $e) {
            $this->pass();
        }
    }
    public function testGetPortableDeclarationSupportsNativeFloatType() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'float'));

        $this->assertEqual($type, array('type' => array('float'),
                                        'length' => null,
                                        'unsigned' => null,
                                        'fixed' => null));
    }
    public function testGetPortableDeclarationSupportsNativeIntegerTypes()
    {
        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'integer'));
        
        $this->assertEqual($type, array('type' => array('integer'),
                                        'length' => null,
                                        'unsigned' => null,
                                        'fixed' => null));

        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'pls_integer', 'data_length' => 1));
        
        $this->assertEqual($type, array('type' => array('integer', 'boolean'),
                                        'length' => 1,
                                        'unsigned' => null,
                                        'fixed' => null));

        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'binary_integer', 'data_length' => 1));
        
        $this->assertEqual($type, array('type' => array('integer', 'boolean'),
                                        'length' => 1,
                                        'unsigned' => null,
                                        'fixed' => null));
    }
    public function testGetPortableDeclarationSupportsNativeStringTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'varchar'));

        $this->assertEqual($type, array('type' => array('string'),
                                        'length' => null,
                                        'unsigned' => null,
                                        'fixed' => null));

        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'varchar2', 'data_length' => 1));

        $this->assertEqual($type, array('type' => array('string', 'boolean'),
                                        'length' => 1,
                                        'unsigned' => null,
                                        'fixed' => false));

        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'nvarchar2', 'data_length' => 1));
        
        $this->assertEqual($type, array('type' => array('string', 'boolean'),
                                        'length' => 1,
                                        'unsigned' => null,
                                        'fixed' => false));
        
        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'char', 'data_length' => 1));

        $this->assertEqual($type, array('type' => array('string', 'boolean'),
                                        'length' => 1,
                                        'unsigned' => null,
                                        'fixed' => true));

        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'nchar', 'data_length' => 1));
        
        $this->assertEqual($type, array('type' => array('string', 'boolean'),
                                        'length' => 1,
                                        'unsigned' => null,
                                        'fixed' => true));
    }
    public function testGetPortableDeclarationSupportsNativeNumberType() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'number'));
        
        $this->assertEqual($type, array('type' => array('integer'),
                                        'length' => null,
                                        'unsigned' => null,
                                        'fixed' => null));


        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'number', 'data_length' => 1));
        
        $this->assertEqual($type, array('type' => array('integer', 'boolean'),
                                        'length' => 1,
                                        'unsigned' => null,
                                        'fixed' => null));

    }
    public function testGetPortableDeclarationSupportsNativeTimestampType() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'date'));
        
        $this->assertEqual($type, array('type' => array('timestamp'),
                                        'length' => null,
                                        'unsigned' => null,
                                        'fixed' => null));

        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'timestamp'));
        
        $this->assertEqual($type, array('type' => array('timestamp'),
                                        'length' => null,
                                        'unsigned' => null,
                                        'fixed' => null));
    }
    public function testGetPortableDeclarationSupportsNativeClobTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'clob'));
        
        $this->assertEqual($type, array('type' => array('clob'),
                                        'length' => null,
                                        'unsigned' => null,
                                        'fixed' => null));

        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'long'));
        
        $this->assertEqual($type, array('type' => array('string', 'clob'),
                                        'length' => null,
                                        'unsigned' => null,
                                        'fixed' => null));
        
        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'nclob'));
        
        $this->assertEqual($type, array('type' => array('clob'),
                                        'length' => null,
                                        'unsigned' => null,
                                        'fixed' => null));

    }
    public function testGetPortableDeclarationSupportsNativeBlobTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'blob'));
        
        $this->assertEqual($type, array('type' => array('blob'),
                                        'length' => null,
                                        'unsigned' => null,
                                        'fixed' => null));

        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'long raw'));

        $this->assertEqual($type, array('type' => array('blob'),
                                        'length' => null,
                                        'unsigned' => null,
                                        'fixed' => null));

        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'long raw'));

        $this->assertEqual($type, array('type' => array('blob'),
                                        'length' => null,
                                        'unsigned' => null,
                                        'fixed' => null));
        
        $type = $this->dataDict->getPortableDeclaration(array('data_type' => 'raw'));

        $this->assertEqual($type, array('type' => array('blob'),
                                        'length' => null,
                                        'unsigned' => null,
                                        'fixed' => null));
    }
    public function testGetNativeDefinitionSupportsIntegerType() 
    {
        $a = array('type' => 'integer', 'length' => 20, 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'NUMBER(20)');
        
        $a['length'] = 4;

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'NUMBER(4)');

        $a['length'] = 2;

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'NUMBER(2)');
    }

    public function testGetNativeDefinitionSupportsFloatType() 
    {
        $a = array('type' => 'float', 'length' => 20, 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'NUMBER');
    }
    public function testGetNativeDefinitionSupportsBooleanType() 
    {
        $a = array('type' => 'boolean', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'NUMBER(1)');
    }
    public function testGetNativeDefinitionSupportsDateType() 
    {
        $a = array('type' => 'date', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'DATE');
    }
    public function testGetNativeDefinitionSupportsTimestampType() 
    {
        $a = array('type' => 'timestamp', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'DATE');
    }
    public function testGetNativeDefinitionSupportsTimeType() 
    {
        $a = array('type' => 'time', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'DATE');
    }
    public function testGetNativeDefinitionSupportsClobType() 
    {
        $a = array('type' => 'clob');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'CLOB');
    }
    public function testGetNativeDefinitionSupportsBlobType() 
    {
        $a = array('type' => 'blob');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'BLOB');
    }
    public function testGetNativeDefinitionSupportsCharType() 
    {
        $a = array('type' => 'char', 'length' => 10);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'CHAR(10)');
    }
    public function testGetNativeDefinitionSupportsVarcharType() 
    {
        $a = array('type' => 'varchar', 'length' => 10);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'VARCHAR2(10)');
    }
    public function testGetNativeDefinitionSupportsArrayType() 
    {
        $a = array('type' => 'array', 'length' => 40);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'VARCHAR2(40)');
    }
    public function testGetNativeDefinitionSupportsStringType() 
    {
        $a = array('type' => 'string');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'VARCHAR2(16777215)');
    }
    public function testGetNativeDefinitionSupportsArrayType2() 
    {
        $a = array('type' => 'array');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'VARCHAR2(16777215)');
    }
    public function testGetNativeDefinitionSupportsObjectType() 
    {
        $a = array('type' => 'object');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'VARCHAR2(16777215)');
    }
}

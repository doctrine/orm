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
 * Doctrine_DataDict_Mysql_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_DataDict_Mssql_TestCase extends Doctrine_UnitTestCase 
{
    public function testGetPortableDeclarationForUnknownNativeTypeThrowsException() 
    {
        try {
            $this->dataDict->getPortableDeclaration(array('type' => 'some_unknown_type'));
            $this->fail();
        } catch(Doctrine_DataDict_Mssql_Exception $e) {
            $this->pass();
        }
    }
    public function testGetPortableDeclarationSupportsNativeBitType() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'bit'));
        
        $this->assertEqual($type, array(array('boolean'), null, null, null));
    }
    public function testGetPortableDeclarationSupportsNativeStringTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'text'));

        $this->assertEqual($type, array(array('string', 'clob'), null, null, null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'char', 'length' => 1));

        $this->assertEqual($type, array(array('string', 'boolean'), 1, null, true));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'varchar', 'length' => 1));
        
        $this->assertEqual($type, array(array('string', 'boolean'), 1, null, false));
    }
    public function testGetPortableDeclarationSupportsNativeBlobTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'image'));
        
        $this->assertEqual($type, array(array('blob'), null, null, null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'varbinary'));

        $this->assertEqual($type, array(array('blob'), null, null, null));
    }
    public function testGetPortableDeclarationSupportsNativeIntegerTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'int'));
        
        $this->assertEqual($type, array(array('integer'), null, null, null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'int', 'length' => 1));
        
        $this->assertEqual($type, array(array('integer', 'boolean'), 1, null, null));
    }
    public function testGetPortableDeclarationSupportsNativeTimestampType() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'datetime'));
        
        $this->assertEqual($type, array(array('timestamp'), null, null, null));
    }
    public function testGetPortableDeclarationSupportsNativeDecimalTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'decimal'));

        $this->assertEqual($type, array(array('decimal'), null, null, null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'money'));

        $this->assertEqual($type, array(array('decimal'), null, null, null));
    }
    public function testGetPortableDeclarationSupportsNativeFloatTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'float'));

        $this->assertEqual($type, array(array('float'), null, null, null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'real'));

        $this->assertEqual($type, array(array('float'), null, null, null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'numeric'));

        $this->assertEqual($type, array(array('float'), null, null, null));
    }
    public function testGetNativeDefinitionSupportsIntegerType() 
    {
        $a = array('type' => 'integer', 'length' => 20, 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'INT');
        
        $a['length'] = 4;

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'INT');

        $a['length'] = 2;

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'INT');
    }

    public function testGetNativeDefinitionSupportsFloatType() 
    {
        $a = array('type' => 'float', 'length' => 20, 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'FLOAT');
    }
    public function testGetNativeDefinitionSupportsBooleanType() 
    {
        $a = array('type' => 'boolean', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'BIT');
    }
    public function testGetNativeDefinitionSupportsDateType() 
    {
        $a = array('type' => 'date', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'CHAR(10)');
    }
    public function testGetNativeDefinitionSupportsTimestampType() 
    {
        $a = array('type' => 'timestamp', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'CHAR(19)');
    }
    public function testGetNativeDefinitionSupportsTimeType() 
    {
        $a = array('type' => 'time', 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'CHAR(8)');
    }
    public function testGetNativeDefinitionSupportsClobType() 
    {
        $a = array('type' => 'clob');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'TEXT');
    }
    public function testGetNativeDefinitionSupportsBlobType() 
    {
        $a = array('type' => 'blob');

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'IMAGE');
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

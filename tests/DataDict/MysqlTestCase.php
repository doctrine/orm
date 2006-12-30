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
class Doctrine_DataDict_Mysql_TestCase extends Doctrine_UnitTestCase {
    public function testGetCharsetFieldDeclarationReturnsValidSql() 
    {
        $this->assertEqual($this->dataDict->getCharsetFieldDeclaration('UTF-8'), 'CHARACTER SET UTF-8');
    }
    public function testGetCollationFieldDeclarationReturnsValidSql() 
    {
        $this->assertEqual($this->dataDict->getCollationFieldDeclaration('xx'), 'COLLATE xx');
    }
    public function testGetPortableDeclarationForUnknownNativeTypeThrowsException() 
    {
        try {
            $this->dataDict->getPortableDeclaration(array('type' => 'some_unknown_type'));
            $this->fail();
        } catch(Doctrine_DataDict_Mysql_Exception $e) {
            $this->pass();
        }
    }
    public function testGetPortableDeclarationSupportsNativeIntegerTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'tinyint'));

        $this->assertEqual($type, array(array('integer', 'boolean'), 1, null, null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'smallint unsigned'));

        $this->assertEqual($type, array(array('integer'), 2, true, null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'mediumint unsigned'));

        $this->assertEqual($type, array(array('integer'), 3, true, null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'int unsigned'));

        $this->assertEqual($type, array(array('integer'), 4, true, null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'integer unsigned'));

        $this->assertEqual($type, array(array('integer'), 4, true, null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'bigint unsigned'));

        $this->assertEqual($type, array(array('integer'), 8, true, null));
    }
    public function testGetPortableDeclarationSupportsNativeStringTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'text'));

        $this->assertEqual($type, array(array('string', 'clob'), null, null, false));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'longtext'));

        $this->assertEqual($type, array(array('string', 'clob'), null, null, false));
        
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'mediumtext'));

        $this->assertEqual($type, array(array('string', 'clob'), null, null, false));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'tinytext'));

        $this->assertEqual($type, array(array('string', 'clob'), null, null, false));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'char(1)'));

        $this->assertEqual($type, array(array('string', 'boolean'), 1, null, true));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'varchar(1)'));

        $this->assertEqual($type, array(array('string', 'boolean'), 1, null, false));
    }
    public function testGetPortableDeclarationSupportsNativeFloatTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'float'));
        
        $this->assertEqual($type, array(array('float'), null, null, null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'real unsigned'));

        $this->assertEqual($type, array(array('float'), null, true, null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'double'));
        
        $this->assertEqual($type, array(array('float'), null, null, null));
    }

    public function testGetPortableDeclarationSupportsNativeDateType() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'date'));
        
        $this->assertEqual($type, array(array('date'), null, null, null));
    }
    public function testGetPortableDeclarationSupportsNativeDecimalTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'decimal'));
        
        $this->assertEqual($type, array(array('decimal'), null, null, null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'unknown'));
        
        $this->assertEqual($type, array(array('decimal'), null, null, null));
        
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'numeric'));
        
        $this->assertEqual($type, array(array('decimal'), null, null, null));
    }

    public function testGetPortableDeclarationSupportsNativeTimestampTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'timestamp'));
        
        $this->assertEqual($type, array(array('timestamp'), null, null, null));
        
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'datetime'));
        
        $this->assertEqual($type, array(array('timestamp'), null, null, null));
    }
    public function testGetPortableDeclarationSupportsNativeYearType() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'year'));

        $this->assertEqual($type, array(array('integer', 'date'), null, null, null));
    }
    public function testGetPortableDeclarationSupportsNativeBlobTypes() 
    {
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'blob'));

        $this->assertEqual($type, array(array('blob'), null, null, null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'mediumblob'));

        $this->assertEqual($type, array(array('blob'), null, null, null));
        
        $type = $this->dataDict->getPortableDeclaration(array('type' => 'tinyblob'));

        $this->assertEqual($type, array(array('blob'), null, null, null));

        $type = $this->dataDict->getPortableDeclaration(array('type' => 'longblob'));

        $this->assertEqual($type, array(array('blob'), null, null, null));
    }

    public function testGetNativeDefinitionSupportsIntegerType() 
    {
        $a = array('type' => 'integer', 'length' => 20, 'fixed' => false);

        $this->assertEqual($this->dataDict->getNativeDeclaration($a), 'BIGINT');
        
        $a['length'] = 4;

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'INT');

        $a['length'] = 2;

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'SMALLINT');
    }

    public function testGetNativeDeclarationSupportsFloatType() 
    {
        $a = array('type' => 'float', 'length' => 20, 'fixed' => false);

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'DOUBLE');
    }
    public function testGetNativeDeclarationSupportsBooleanType() 
    {
        $a = array('type' => 'boolean', 'fixed' => false);

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'TINYINT(1)');
    }
    public function testGetNativeDeclarationSupportsDateType() 
    {
        $a = array('type' => 'date', 'fixed' => false);

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'DATE');
    }
    public function testGetNativeDeclarationSupportsTimestampType() 
    {
        $a = array('type' => 'timestamp', 'fixed' => false);

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'DATETIME');
    }
    public function testGetNativeDeclarationSupportsTimeType() 
    {
        $a = array('type' => 'time', 'fixed' => false);

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'TIME');
    }
    public function testGetNativeDeclarationSupportsClobType() 
    {
        $a = array('type' => 'clob');

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'LONGTEXT');
    }
    public function testGetNativeDeclarationSupportsBlobType() 
    {
        $a = array('type' => 'blob');

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'LONGBLOB');
    }
    public function testGetNativeDeclarationSupportsCharType() 
    {
        $a = array('type' => 'char', 'length' => 10);

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'CHAR(10)');
    }
    public function testGetNativeDeclarationSupportsVarcharType() 
    {
        $a = array('type' => 'varchar', 'length' => 10);

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'VARCHAR(10)');
    }
    public function testGetNativeDeclarationSupportsArrayType() 
    {
        $a = array('type' => 'array', 'length' => 40);

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'VARCHAR(40)');
    }
    public function testGetNativeDeclarationSupportsStringType() 
    {
        $a = array('type' => 'string');

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'TEXT');
    }
    public function testGetNativeDeclarationSupportsStringTypeWithLongLength() 
    {
        $a = array('type' => 'string', 'length' => 2000);

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'TEXT');
    }
    public function testGetNativeDeclarationSupportsArrayType2() 
    {
        $a = array('type' => 'array');

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'TEXT');
    }
    public function testGetNativeDeclarationSupportsObjectType() 
    {
        $a = array('type' => 'object');

        $this->assertEqual($this->dataDict->GetNativeDeclaration($a), 'TEXT');
    }

}

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
 * Doctrine_Record_SerializeUnserialize_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Record_SerializeUnserialize_TestCase extends Doctrine_UnitTestCase 
{
    
    public function prepareTables()
    {
        $this->tables[] = 'SerializeTest';

        parent::prepareTables();
    }
    
    public function prepareData()
    { }

    public function testSerializeUnserialize()
    {
        $object = new SerializeTest();
        $object->booltest = true;
        $object->integertest = 13;
        $object->floattest = 0.13;
        $object->stringtest = 'string';
        $object->arraytest = array(1, 2, 3);
        $object->objecttest = new TestObject(13);
        $object->enumtest = 'java';
        $object->blobtest = 'blobtest';
        $object->clobtest = 'clobtest';
        $object->gziptest = 'gziptest';
        $object->timestamptest = '2007-08-07 11:55:00';
        $object->timetest = '11:55:00';
        $object->datetest = '2007-08-07';
        
        $object->save();
        
        $object_before = clone($object);
        $serialized = serialize($object);
        $object_after = unserialize($serialized);
        
        $this->assertIdentical($object_before->booltest, $object_after->booltest);
        $this->assertIdentical($object_before->integertest, $object_after->integertest);
        $this->assertIdentical($object_before->floattest, $object_after->floattest);
        $this->assertIdentical($object_before->stringtest, $object_after->stringtest);
        $this->assertIdentical($object_before->arraytest, $object_after->arraytest);
        $this->assertIdentical($object_before->enumtest, $object_after->enumtest);
        $this->assertEqual($object_before->objecttest, $object_after->objecttest);
        $this->assertIdentical($object_before->blobtest, $object_after->blobtest);
        $this->assertIdentical($object_before->clobtest, $object_after->clobtest);
        $this->assertIdentical($object_before->gziptest, $object_after->gziptest);
        $this->assertIdentical($object_before->timestamptest, $object_after->timestamptest);
        $this->assertIdentical($object_before->timetest, $object_after->timetest);
        $this->assertIdentical($object_before->datetest, $object_after->datetest);
        
    }
    
    public function testSerializeUnserializeRecord()
    {
        $test = new TestRecord();
        $test->save();
        
        $object = new SerializeTest();
        $object->objecttest = $test;
         
        $object->save();
        
        $object_before = clone($object);
       
        $serialized = serialize($object);
        $object_after = unserialize($serialized);
        
        $this->assertIdentical(get_class($object_after->objecttest), 'TestRecord');
    }
    
}

class SerializeTest extends Doctrine_Record 
{
    public function setTableDefinition()
    {
        $this->setTableName('serialize_test');
    
        $this->hasColumn('booltest', 'boolean');
        $this->hasColumn('integertest', 'integer', 4, array('unsigned' => true));
        $this->hasColumn('floattest', 'float');
        $this->hasColumn('stringtest', 'string', 200, array('fixed' => true));
        $this->hasColumn('arraytest', 'array', 10000);
        $this->hasColumn('objecttest', 'object');
        $this->hasColumn('blobtest', 'blob');
        $this->hasColumn('clobtest', 'clob');
        $this->hasColumn('timestamptest', 'timestamp');
        $this->hasColumn('timetest', 'time');
        $this->hasColumn('datetest', 'date');
        $this->hasColumn('enumtest', 'enum', 4, 
                         array(
                            'values' => array(
                                        'php',
                                        'java',
                                        'python'
                                        )
                               )
        );
        $this->hasColumn('gziptest', 'gzip');
    }

}

class TestObject
{
    
    private $test_field;
    
    public function __construct($value)
    {
    	$this->test_field = $value;
    }
        
}

class TestRecord extends Doctrine_Record 
{
    public function setTableDefinition()
    {
        $this->setTableName('test');
    }
}

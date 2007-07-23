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
 * Doctrine_Import_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_Import_TestCase extends Doctrine_UnitTestCase 
{

       
    public function prepareTables() 
    { }
    public function prepareData() 
    { }

    public function setUp()
    {
    	parent::setUp();

        //reading
        $reader = new Doctrine_Import_Reader_Db();
        $reader->setPdo($this->dbh);
        $this->schema = $reader->read();
    }
    
    public function testBadImport() 
    {
        $builder = new Doctrine_Import_Builder();
        
        try { 
            $builder->buildRecord(new Doctrine_Schema_Table());

            $this->fail();
        } catch(Doctrine_Import_Builder_Exception $e) {
            $this->pass();
        }

    }

    public function testImportTable() 
    {
        $definition = array('name' => 'import_test');

        $table = new Doctrine_Schema_Table($definition);
        $def     = array('name' => 'name',
                         'type' => 'string',
                         'length' => 20,
                         'default' => 'someone');

        $table->addColumn(new Doctrine_Schema_Column($def));

        $def     = array('name' => 'created',
                         'type' => 'integer',
                         'notnull' => true);

        $table->addColumn(new Doctrine_Schema_Column($def));

        $builder = new Doctrine_Import_Builder();

        $builder->setTargetPath('tmp');
        try {
            $builder->buildRecord($table);

            $this->pass();
        } catch(Doctrine_Import_Builder_Exception $e) {
            $this->fail();
        }
        $this->assertTrue(file_exists('tmp' . DIRECTORY_SEPARATOR . 'ImportTest.php'));
    }
    public function testImportedComponent() 
    {
        require_once('tmp' . DIRECTORY_SEPARATOR . 'ImportTest.php');

        $r = new ImportTest();
        $columns = $r->getTable()->getColumns();

        // id column is auto-created
        
        $this->assertEqual($columns['id'][0], 'integer');
        $this->assertEqual($columns['id'][1], 20);

        $this->assertEqual($columns['name'][0], 'string');
        $this->assertEqual($columns['name'][1], 20);
        
        $this->assertEqual($columns['created'][0], 'integer');

        unlink('tmp' . DIRECTORY_SEPARATOR . 'ImportTest.php');
    }
}

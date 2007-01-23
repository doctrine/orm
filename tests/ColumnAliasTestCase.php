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
 * Doctrine_ColumnAlias_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_ColumnAlias_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData() 
    { }
    public function prepareTables()
    { }

    public function testAliasesAreSupportedForRecordPropertyAccessors()
    {
        $record = new ColumnAliasTest;
        try {
            $record->alias1 = 'someone';
            $record->alias2 = 187;
            
            $this->assertEqual($record->alias1, 'someone');
            $this->assertEqual($record->alias2, 187);
        } catch(Doctrine_Record_Exception $e) {
            $this->fail();
        }
        $record->save();
    }
    public function testAliasesAreSupportedForDqlSelectPart()
    {
        $q = new Doctrine_Query();

        $q->select('c.alias1, c.alias2')->from('ColumnAliasTest c');
        
        $coll = $q->execute();
        
        $this->assertEqual($coll[0]->alias1, 'someone');
        $this->assertEqual($coll[0]->alias2, 187);
    }
    public function testAliasesAreSupportedForDqlWherePart()
    {
        $q = new Doctrine_Query();

        $q->select('c.alias1, c.alias2')
          ->from('ColumnAliasTest c')
          ->where('c.alias1 = ?');

        $coll = $q->execute(array('someone'));
        
        $this->assertEqual($coll[0]->alias1, 'someone');
        $this->assertEqual($coll[0]->alias2, 187);
    }
    public function testAliasesAreSupportedForDqlAggregateFunctions()
    {
        $q = new Doctrine_Query();

        $q->select('MAX(c.alias1)')
          ->from('ColumnAliasTest c');

        $coll = $q->execute();
        
        $this->assertEqual($coll[0]->max, 'someone');
    }
    public function testAliasesAreSupportedForDqlHavingPart()
    {
        $q = new Doctrine_Query();

        $q->select('c.alias1')
          ->from('ColumnAliasTest c')
          ->having('MAX(c.alias2) = 187')
          ->groupby('c.id');

        $coll = $q->execute();
        
        $this->assertEqual($coll[0]->alias1, 'someone');
    }
}
class ColumnAliasTest extends Doctrine_Record
{
    public function setTableDefinition()
    {
        $this->hasColumn('column1 as alias1', 'string', 200);
        $this->hasColumn('column2 as alias2', 'integer', 11);
    }
    public function setUp() 
    {
    }
}

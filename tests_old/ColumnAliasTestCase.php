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
 * Doctrine_ColumnAlias_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.org
 * @since       1.0
 * @version     $Revision$
 */
class Doctrine_ColumnAlias_TestCase extends Doctrine_UnitTestCase 
{
    public function prepareData() 
    {
        $book1 = new Book();
        $book1->name = 'Das Boot';
        $book1->save();
        
        $record1 = new ColumnAliasTest();
        $record1->alias1 = 'first';
        $record1->alias2 = 123;
        $record1->anotherField = 'camelCase';
        $record1->bookId = $book1->id;
        $record1->save();
        
        $record2 = new ColumnAliasTest();
        $record2->alias1 = 'one';
        $record2->alias2 = 456;
        $record2->anotherField = 'KoQ';
        $record2->save();
        
        $record2->anotherField = 'foo';
    }
    
    public function prepareTables()
    { 
        $this->tables = array('ColumnAliasTest', 'Book');
        
        parent::prepareTables();
    }

    public function testAliasesAreSupportedForJoins()
    {
        $q = new Doctrine_Query();
        $q->select('c.*, b.name')->from('ColumnAliasTest c')
                ->innerJoin('c.book b')
                ->where('c.anotherField = ?', 'camelCase');
        $result = $q->execute();
        $this->assertTrue(isset($result[0]->book));
        $this->assertEqual($result[0]->book->name, 'Das Boot');
    }
    
    public function testAliasesAreSupportedForArrayFetching()
    {
        $q = new Doctrine_Query();
        $q->select('c.*, b.name')->from('ColumnAliasTest c')
                ->innerJoin('c.book b')
                ->where('c.anotherField = ?', 'camelCase')
                ->setHydrationMode(Doctrine::HYDRATE_ARRAY);
        $result = $q->execute();
        $this->assertEqual($result[0]['alias1'], 'first');
        $this->assertEqual($result[0]['alias2'], 123);
        $this->assertEqual($result[0]['anotherField'], 'camelCase');
        $this->assertTrue(isset($result[0]['book']));
        $this->assertEqual($result[0]['book']['name'], 'Das Boot');
    }

    public function testAliasesAreSupportedForRecordPropertyAccessors()
    {
        $record = new ColumnAliasTest;
        try {
            $record->alias1 = 'someone';
            $record->alias2 = 187;
            
            $this->assertEqual($record->alias1, 'someone');
            $this->assertEqual($record->alias2, 187);
        } catch (Doctrine_Record_Exception $e) {
            $this->fail();
        }
    }
    
    public function testAliasesAreSupportedForDqlSelectPart()
    {
        $q = new Doctrine_Query();
        $q->select('c.alias1, c.alias2, c.anotherField')->from('ColumnAliasTest c');
        $coll = $q->execute();

        $this->assertEqual($coll[0]->alias1, 'first');
        $this->assertEqual($coll[0]->alias2, 123);
        $this->assertEqual($coll[0]->anotherField, 'camelCase');
    }
    
    public function testAliasesAreSupportedForDqlWherePart()
    {
        $q = new Doctrine_Query();

        $q->select('c.alias1, c.alias2, c.anotherField')
          ->from('ColumnAliasTest c')
          ->where('c.anotherField = ?');

        $coll = $q->execute(array('KoQ'));

        $this->assertEqual($coll[0]->alias1, 'one');
        $this->assertEqual($coll[0]->alias2, 456);
        $this->assertEqual($coll[0]->anotherField, 'KoQ');
    }
    
    public function testAliasesAreSupportedForDqlAggregateFunctions()
    {
        $q = new Doctrine_Query();

        $q->select('MAX(c.alias2)')->from('ColumnAliasTest c');

        $coll = $q->execute();

        $this->assertEqual($coll[0]->MAX, 456);
    }
    
    public function testAliasesAreSupportedForDqlHavingPart()
    {
        $q = new Doctrine_Query();

        $q->select('c.alias2')
          ->from('ColumnAliasTest c')
          ->groupby('c.id')
          ->having('c.alias2 > 123');

        $coll = $q->execute();
        
        $this->assertEqual($coll[0]->alias2, 456);
    }
}


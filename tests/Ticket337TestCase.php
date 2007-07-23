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
 * Doctrine_Template_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Inheritance
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
 
 
class ReportBase extends Doctrine_Record
{
	public function setTableDefinition() 
    {
		$this->setTableName('Report');
		$this->hasColumn('id', 'integer',20, 'autoincrement|primary');
        $this->hasColumn('description', 'string',50);		
        $this->hasColumn('type', 'integer', 11);        
	}
}

class Report extends ReportBase
{
	public function setTableDefinition() 
    {
		parent::setTableDefinition();
		$this->option('subclasses', array('ReportA','ReportB'));
	}
}
 
class ReportA extends ReportBase
{
	public function setUp() 
    {
        parent::setUp();
		$this->option('inheritanceMap', array('type' => 1));
	}
	
	public function setTableDefinition()
	{
		parent::setTableDefinition();
		$this->hasColumn('columnreporta', 'string',50);
	}
}

class ReportB extends ReportBase
{
	public function setUp() 
    {
        parent::setUp();
		$this->option('inheritanceMap', array('type' => 2));
	}
	
	public function setTableDefinition()
	{
		parent::setTableDefinition();
		$this->hasColumn('columnreportb', 'string',50);
	}
}

 
class Doctrine_Ticket337_TestCase extends Doctrine_UnitTestCase 
{

	public function testInit()
	{
		/*create table*/
		$this->dbh->exec("CREATE Table Report (".
							"id INTEGER  PRIMARY KEY AUTOINCREMENT,".
							"type INTEGER,".
							"description varchar(50),".
							"columnreporta varchar(50),".
							"columnreportb varchar(50))");		

	}
    public function testTicket337()
    {
		$reportA = new ReportA();
		$reportA->set('description',"teste Report A");
		$reportA->set('columnreporta',"somevalueA");
		$reportA->save();
		
		$reportB = new ReportB();
		$reportB->set('description',"teste Report B");
		$reportB->set('columnreportb',"somevalueB");		
		$reportB->save();

		
		$this->assertTrue($reportA->get('columnreporta') == "somevalueA");		
		$this->assertTrue($reportB->get('columnreportb') == "somevalueB");		
		
		$q = new Doctrine_Query();			
		$reportAFromDB = $q->from('Report')->where('id=?')->execute(array($reportA->id))->getFirst();
		
		$q = new Doctrine_Query();	
		$reportBFromDB = $q->from('Report')->where('id=?')->execute(array($reportB->id))->getFirst();
		
		
		//same tests as Doctrine_ColumnAggregationInheritance_TestCase::testSubclassReturnedIfInheritanceMatches()
		$this->assertTrue(($reportAFromDB instanceof ReportA));		
		$this->assertTrue(($reportBFromDB instanceof ReportB));		
		
		
		
		$this->assertEqual($reportAFromDB->get('description'),'teste Report A');
		$this->assertEqual($reportBFromDB->get('description'),'teste Report B');
		
		try{
			$this->assertEqual($reportAFromDB->get('columnreporta'),'somevalueA');
		}catch(Doctrine_Record_Exception $ex)
		{
			 $this->fail($ex->__toString());
		}
		
		try{
			$this->assertEqual($reportBFromDB->get('columnreportb'),'somevalueB');			
		}catch(Doctrine_Record_Exception $ex)
		{
			 $this->fail($ex->__toString());
		}		
    }
}

<?php

 
/**
 * Doctrine_Ticket330_TestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
 
class stNode extends Doctrine_Record
{
	public function setTableDefinition()
  	{
    	$this->setTableName('node_node');
 
    	$this->hasColumn('title', 'string', 255, array ());
  	}
  
  	public function setUp()
  	{
  		$this->hasOne('stNodeDetail as detail', 'stNodeDetail.node_id' , array( 'onDelete'=>'cascade'));
  	}
}
 
class stNodeDetail extends Doctrine_Record
{
	public function setTableDefinition()
  	{
    	$this->setTableName('node_detail');
    	
		$this->hasColumn('node_id', 'integer', 10, array (  'unique' => true,));
    	$this->hasColumn('null_column', 'string', 255, array ('default'=>null));
    	$this->hasColumn('is_bool', 'boolean', null, array ('default' => 0,));
    	$this->option('type', 'MyISAM');
  	}
  
  	public function setUp()
  	{
    	$this->hasOne('stNode as node', 'stNodeDetail.article_id', array('foreign' => 'id' , 'onDelete'=>'cascade'));
  	}
}
 
 
class Doctrine_Ticket330_TestCase extends Doctrine_UnitTestCase
{
	public function prepareData() 
    { }
    
    public function prepareTables()
    { }
    
    public function testUnnecessaryQueries()
    {
    	
    	$node1 = new stNode();
    	$node1->set('title', 'first node');
    	$node1->detail->set('is_bool', true);
    	$node1->save();
    	
    	$node2 = new stNode();
    	$node2->set('title', 'second node');
    	$node2->detail->set('null_column', 'value');
    	$node2->detail->set('is_bool', false);
    	$node2->save();
    	
    	$nodes = Doctrine_Query::create()
    				->select('n.title, d.null_column, d.is_bool')
    				->from('stNode n, n.detail d')    	
    				->execute();
    				
    	$prevCount = $this->dbh->count();
		
		    	
    	foreach ( $nodes as $node )
    	{
    		if ( $node->get('title') == 'first node')
    		{
    			$this->assertEqual($node->detail->get('is_bool'), true);
    			$this->assertEqual($node->detail->get('null_column'), null);
    			// Unnecessary query is triggered on line before due to null value column.
    			$this->assertEqual($this->dbh->count(), $prevCount);
    			
    			$prevCount = $this->dbh->count();
    		} 
    		else 
    		{
    			$this->assertEqual($node->detail->get('null_column'), 'value');
    			$this->assertEqual($node->detail->get('is_bool'), false);
    			// Unecessary query is triggered on line before due to false value column
    			$this->assertEqual($this->dbh->count(), $prevCount);
    		}
    	}
    	
    	$this->assertEqual($this->dbh->count(), $prevCount);
    	
    	echo $this->dbh->count()."\n\n";
		    	    	
    }
}

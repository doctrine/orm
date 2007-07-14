<?php 

/**
 * Doctrine_TicketNjero_TestCase
 *
 * @package     Doctrine
 * @author      Jeff Rafter <lists@jeffrafter.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */
 
class CoverageCode extends Doctrine_Record {
  
  public function setTableDefinition(){
    $this->setTableName('coverage_codes');
    $this->hasColumn('id', 'integer', 4, array('notnull' => true, 'primary' => true, 'autoincrement' => true));
    $this->hasColumn('code', 'integer', 4, array (  'notnull' => true,  'notblank' => true,));
    $this->hasColumn('description', 'string', 4000, array (  'notnull' => true,  'notblank' => true,));
  }
  
  public function setUp(){
    $this->index('code', array('fields' => 'code'));
  }  
}

class PolicyCode extends Doctrine_Record {
  
  public function setTableDefinition(){
    $this->setTableName('policy_codes');
    $this->hasColumn('id', 'integer', 4, array('notnull' => true, 'primary' => true, 'autoincrement' => true));
    $this->hasColumn('code', 'integer', 4, array (  'notnull' => true,  'notblank' => true,));
    $this->hasColumn('description', 'string', 4000, array (  'notnull' => true,  'notblank' => true,));
  }
  
  public function setUp(){
    $this->index('code', array('fields' => 'code'));
  }  
}

class LiabilityCode extends Doctrine_Record {
  
  public function setTableDefinition(){
    $this->setTableName('liability_codes');
    $this->hasColumn('id', 'integer', 4, array('notnull' => true, 'primary' => true, 'autoincrement' => true));
    $this->hasColumn('code', 'integer', 4, array (  'notnull' => true,  'notblank' => true,));
    $this->hasColumn('description', 'string', 4000, array (  'notnull' => true,  'notblank' => true,));
  }
  
  public function setUp(){
    $this->index('code', array('fields' => 'code'));
  }  
}

class Policy extends Doctrine_Record {
  
  public function setTableDefinition(){
    $this->setTableName('policies');
    $this->hasColumn('id', 'integer', 4, array('notnull' => true, 'primary' => true, 'autoincrement' => true));
    $this->hasColumn('rate_id', 'integer', 4, array ( ));
    $this->hasColumn('policy_number', 'integer', 4, array (  'unique' => true, ));
  }
  
  public function setUp(){
    $this->hasOne('Rate', array('local' => 'rate_id', 'foreign' => 'id' ));
  }

}

class Rate extends Doctrine_Record{
  
  public function setTableDefinition(){
    $this->setTableName('rates');
    $this->hasColumn('id', 'integer', 4, array('notnull' => true, 'primary' => true, 'autoincrement' => true));
    $this->hasColumn('policy_code', 'integer', 4, array (  'notnull' => true,  'notblank' => true,));
    $this->hasColumn('coverage_code', 'integer', 4, array (  'notnull' => true,  'notblank' => true,));
    $this->hasColumn('liability_code', 'integer', 4, array (  'notnull' => true,  'notblank' => true,));
    $this->hasColumn('total_rate', 'float', null, array (  'notnull' => true,  'notblank' => true,));
  }
  
  public function setUp(){
    $this->index('policy_code_idx', array('fields' => 'policy_code'));
    $this->index('coverage_code_idx', array('fields' => 'coverage_code'));
    $this->index('liability_code_idx', array('fields' => 'liability_code'));
    $this->hasOne('PolicyCode', array('local' => 'policy_code', 'foreign' => 'code' ));
    $this->hasOne('CoverageCode', array('local' => 'coverage_code', 'foreign' => 'code' ));
    $this->hasOne('LiabilityCode', array('local' => 'liability_code', 'foreign' => 'code' ));
  }
  
}
  
class Doctrine_TicketNjero_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData() { }
    public function prepareTables()
    {
    	$this->tables[] = 'CoverageCode';
    	$this->tables[] = 'PolicyCode';
    	$this->tables[] = 'LiabilityCode';
    	$this->tables[] = 'Policy';
    	$this->tables[] = 'Rate';
    	parent::prepareTables();    	
    }

    public function testHasOneMultiLevelRelations()
    {
      $policy_code = new PolicyCode();
      $policy_code->code = 1;
      $policy_code->description = "Special Policy";
      $policy_code->save();
      
      $coverage_code = new CoverageCode();
      $coverage_code->code = 1;
      $coverage_code->description = "Full Coverage";
      $coverage_code->save();
      
      $liability_code = new LiabilityCode();
      $liability_code->code = 1;
      $liability_code->description = "Limited Territory";
      $liability_code->save();

      $rate = new Rate();
      $rate->policy_code = 1;
      $rate->coverage_code = 1;
      $rate->liability_code = 1;
      $rate->total_rate = 123.45;
      $rate->save();
      
      $policy = new Policy();
      $policy->rate_id = 1;
      $policy->policy_number = "123456789";  
      $policy->save();
        
      $q = new Doctrine_Query();
      $p = $q->from("Policy p")
             ->where("p.id = 1")
             ->execute()
             ->getFirst();

      $this->assertEqual($p->rate_id, 1);
      $this->assertEqual($p->Rate->id, 2);
    }
}?>

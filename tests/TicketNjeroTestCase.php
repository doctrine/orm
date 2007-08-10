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
class CoverageCodeN extends Doctrine_Record {
  
  public function setTableDefinition(){
    $this->setTableName('coverage_codes');
    $this->hasColumn('id', 'integer', 4, array('notnull' => true, 'primary' => true, 'autoincrement' => true));
    $this->hasColumn('code', 'integer', 4, array (  'notnull' => true,  'notblank' => true,));
    $this->hasColumn('description', 'string', 4000, array (  'notnull' => true,  'notblank' => true,));
  }
  
  public function setUp(){
#    $this->index('code', array('fields' => 'code'));
  }  
}

class PolicyCodeN extends Doctrine_Record {
  
  public function setTableDefinition(){
    $this->setTableName('policy_codes');
    $this->hasColumn('id', 'integer', 4, array('notnull' => true, 'primary' => true, 'autoincrement' => true));
    $this->hasColumn('code', 'integer', 4, array (  'notnull' => true,  'notblank' => true,));
    $this->hasColumn('description', 'string', 4000, array (  'notnull' => true,  'notblank' => true,));
  }
  
  public function setUp(){
#    $this->index('code', array('fields' => 'code'));
  }  
}

class LiabilityCodeN extends Doctrine_Record {
  
  public function setTableDefinition(){
    $this->setTableName('liability_codes');
    $this->hasColumn('id', 'integer', 4, array('notnull' => true, 'primary' => true, 'autoincrement' => true));
    $this->hasColumn('code', 'integer', 4, array (  'notnull' => true,  'notblank' => true,));
    $this->hasColumn('description', 'string', 4000, array (  'notnull' => true,  'notblank' => true,));
  }
  
  public function setUp(){
#    $this->index('code', array('fields' => 'code'));
  }  
}

class PolicyN extends Doctrine_Record {
  
  public function setTableDefinition(){
    $this->setTableName('policies');
    $this->hasColumn('id', 'integer', 4, array('notnull' => true, 'primary' => true, 'autoincrement' => true));
    $this->hasColumn('rate_id', 'integer', 4, array ( ));
    $this->hasColumn('policy_number', 'integer', 4, array (  'unique' => true, ));
  }
  
  public function setUp(){
    $this->hasOne('RateN', array('local' => 'rate_id', 'foreign' => 'id' ));
  }

}

class RateN extends Doctrine_Record{
  
  public function setTableDefinition(){
    $this->setTableName('rates');
    $this->hasColumn('id', 'integer', 4, array('notnull' => true, 'primary' => true, 'autoincrement' => true));
    $this->hasColumn('policy_code', 'integer', 4, array (  'notnull' => true,  'notblank' => true,));
    $this->hasColumn('coverage_code', 'integer', 4, array (  'notnull' => true,  'notblank' => true,));
    $this->hasColumn('liability_code', 'integer', 4, array (  'notnull' => true,  'notblank' => true,));
    $this->hasColumn('total_rate', 'float', null, array (  'notnull' => true,  'notblank' => true,));
  }
  
  public function setUp(){
#    $this->index('policy_code_idx', array('fields' => 'policy_code'));
#    $this->index('coverage_code_idx', array('fields' => 'coverage_code'));
#    $this->index('liability_code_idx', array('fields' => 'liability_code'));
    $this->hasOne('PolicyCodeN', array('local' => 'policy_code', 'foreign' => 'code' ));
    $this->hasOne('CoverageCodeN', array('local' => 'coverage_code', 'foreign' => 'code' ));
    $this->hasOne('LiabilityCodeN', array('local' => 'liability_code', 'foreign' => 'code' ));
  }
  
}
  
class Doctrine_TicketNjero_TestCase extends Doctrine_UnitTestCase
{
    public function prepareData() { }
    public function prepareTables()
    {
    	$this->tables[] = 'CoverageCodeN';
    	$this->tables[] = 'PolicyCodeN';
    	$this->tables[] = 'LiabilityCodeN';
    	$this->tables[] = 'PolicyN';
    	$this->tables[] = 'RateN';
    	parent::prepareTables();    	
    }

    public function testHasOneMultiLevelRelations()
    {
      $policy_code = new PolicyCodeN();
      $policy_code->code = 1;
      $policy_code->description = "Special Policy";
      $policy_code->save();
      
      $coverage_code = new CoverageCodeN();
      $coverage_code->code = 1;
      $coverage_code->description = "Full Coverage";
      $coverage_code->save();
      
      $coverage_code = new CoverageCodeN();
      $coverage_code->code = 3; # note we skip 2
      $coverage_code->description = "Partial Coverage";
      $coverage_code->save();

      $liability_code = new LiabilityCodeN();
      $liability_code->code = 1;
      $liability_code->description = "Limited Territory";
      $liability_code->save();

      $rate = new RateN();
      $rate->policy_code = 1;
      $rate->coverage_code = 3;
      $rate->liability_code = 1;
      $rate->total_rate = 123.45;
      $rate->save();
      
      $policy = new PolicyN();
      $policy->rate_id = 1;
      $policy->policy_number = "123456789";  
      $policy->save();
        
      $q = new Doctrine_Query();

      # If I use
      # $p = $q->from('PolicyN p')
      # this test passes, but there is another issue just not reflected in this test yet, see "in my app" note below

      $p = $q->from('PolicyN p, p.RateN r, r.PolicyCodeN y, r.CoverageCodeN c, r.LiabilityCodeN l')
             ->where('(p.id = ?)', array('1'))
             ->execute()
             ->getFirst();

      $this->assertEqual($p->rate_id, 1);
      $this->assertEqual($p->RateN->id, 1);
      $this->assertEqual($p->RateN->policy_code, 1);
      $this->assertEqual($p->RateN->coverage_code, 3); # fail
      $this->assertEqual($p->RateN->liability_code, 1);

      $c = $p->RateN->coverage_code;
      $c2 = $p->RateN->CoverageCodeN->code;
      $c3 = $p->RateN->coverage_code;

      $this->assertEqual($c, $c2); # fail
      $this->assertEqual($c, $c3); # in my app this fails as well, but I can't reproduce this
      #echo "Values " . serialize(array($c, $c2, $c3));

    }
}?>

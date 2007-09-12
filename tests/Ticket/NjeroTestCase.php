<?php
/**
 * Doctrine_Ticket_Njero_TestCase
 *
 * @package     Doctrine
 * @author      Jeff Rafter <lists@jeffrafter.com>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */

class Doctrine_Ticket_Njero_TestCase extends Doctrine_UnitTestCase
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

      $q->from('PolicyN p, p.RateN r, r.PolicyCodeN y, r.CoverageCodeN c, r.LiabilityCodeN l')
        ->where('(p.id = ?)', array('1'));

      $p = $q->execute()->getFirst();

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
}

<?php
class Doctrine_AccessTestCase extends Doctrine_UnitTestCase {
    public function testOffsetMethods() {
        $this->assertEqual($this->new["name"],null);

        $this->new["name"] = "Jack";
        $this->assertEqual($this->new["name"],"Jack");
        
        $this->assertEqual($this->old["name"],"zYne");

        $this->old["name"] = "Jack";
        $this->assertEqual($this->old["name"],"Jack");
    }
    public function testOverload() {
        $this->assertEqual($this->new->name,null);

        $this->new->name = "Jack";
        $this->assertEqual($this->new->name,"Jack");

        $this->assertEqual($this->old->name,"zYne");

        $this->old->name = "Jack";
        $this->assertEqual($this->old->name,"Jack");
    }
    public function testSet() {
        $this->assertEqual($this->new->get("name"),null);

        $this->new->set("name","Jack");
        $this->assertEqual($this->new->get("name"),"Jack");

        $this->assertEqual($this->old->get("name"),"zYne");

        $this->old->set("name","Jack");
        $this->assertEqual($this->old->get("name"),"Jack");
        
        $this->assertEqual($this->old->getID(),4);
    }
}
?>

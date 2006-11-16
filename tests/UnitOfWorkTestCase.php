<?php
require_once("UnitTestCase.php");
class Doctrine_UnitOfWork_TestCase extends Doctrine_UnitTestCase {
    private $correct  = array("Task", "ResourceType", "Resource", "Assignment", "ResourceReference");
    private $correct2 = array (
              0 => 'Resource',
              1 => 'Task',
              2 => 'ResourceType',
              3 => 'Assignment',
              4 => 'ResourceReference',
            );
    public function testbuildFlushTree() {
        $task = new Task();

        $tree = $this->unitOfWork->buildFlushTree(array("Task"));
        $this->assertEqual($tree,array("Resource","Task","Assignment"));

        $tree = $this->unitOfWork->buildFlushTree(array("Task","Resource"));
        $this->assertEqual($tree, $this->correct);

        $tree = $this->unitOfWork->buildFlushTree(array("Task","Assignment","Resource"));
        $this->assertEqual($tree, $this->correct);

        $tree = $this->unitOfWork->buildFlushTree(array("Assignment","Task","Resource"));
        $this->assertEqual($tree, $this->correct2);
    }
    public function testbuildFlushTree2() {
        $this->correct = array("Forum_Category","Forum_Board","Forum_Thread");

        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Board"));
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Category","Forum_Board"));
        $this->assertEqual($tree, $this->correct);
    }
    public function testBuildFlushTree3() {
        $this->correct = array("Forum_Category","Forum_Board","Forum_Thread","Forum_Entry");

        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Entry","Forum_Board"));
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Board","Forum_Entry"));
        $this->assertEqual($tree, $this->correct);
    }
    public function testBuildFlushTree4() {
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Thread","Forum_Board"));
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Board","Forum_Thread"));
        $this->assertEqual($tree, $this->correct);
    }
    public function testBuildFlushTree5() {
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Board","Forum_Thread","Forum_Entry"));
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Board","Forum_Entry","Forum_Thread"));
        $this->assertEqual($tree, $this->correct);
    }
    public function testBuildFlushTree6() {
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Entry","Forum_Board","Forum_Thread"));
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Entry","Forum_Thread","Forum_Board"));
        $this->assertEqual($tree, $this->correct);
    }
    public function testBuildFlushTree7() {
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Thread","Forum_Board","Forum_Entry"));
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Thread","Forum_Entry","Forum_Board"));
        $this->assertEqual($tree, $this->correct);
    }
    public function testBuildFlushTree8() {
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Board","Forum_Thread","Forum_Category"));
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Category","Forum_Thread","Forum_Board"));
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Thread","Forum_Board","Forum_Category"));
        $this->assertEqual($tree, $this->correct);
    }
    public function testBuildFlushTree9() {
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Board","Forum_Thread","Forum_Category","Forum_Entry"));
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Board","Forum_Thread","Forum_Entry","Forum_Category"));
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Board","Forum_Category","Forum_Thread","Forum_Entry"));
        $this->assertEqual($tree, $this->correct);
    }
    public function testBuildFlushTree10() {
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Entry","Forum_Thread","Forum_Board","Forum_Category"));
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Entry","Forum_Thread","Forum_Category","Forum_Board"));
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Entry","Forum_Category","Forum_Board","Forum_Thread"));
        $this->assertEqual($tree, $this->correct);
    }
    public function testBuildFlushTree11() {
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Thread","Forum_Category","Forum_Board","Forum_Entry"));
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Thread","Forum_Entry","Forum_Category","Forum_Board"));
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Thread","Forum_Board","Forum_Entry","Forum_Category"));
        $this->assertEqual($tree, $this->correct);
    }
    public function testBuildFlushTree12() {
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Category","Forum_Entry","Forum_Board","Forum_Thread"));
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Category","Forum_Thread","Forum_Entry","Forum_Board"));
        $this->assertEqual($tree, $this->correct);
        $tree = $this->unitOfWork->buildFlushTree(array("Forum_Category","Forum_Board","Forum_Thread","Forum_Entry"));
        $this->assertEqual($tree, $this->correct);
    }       
}

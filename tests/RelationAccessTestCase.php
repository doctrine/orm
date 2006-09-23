<?php
class Doctrine_RelationAccessTestCase extends Doctrine_UnitTestCase {
    public function prepareData() {
        $o1 = new File_Owner();
        $o1->name = "owner1";
        $o2 = new File_Owner();
        $o2->name = "owner2";

		$f1 = new Data_File();
        $f1->filename = 'file1';
		$f2 = new Data_File();
        $f2->filename = 'file2';
		$f3 = new Data_File();
        $f3->filename = 'file3';
		
        $o1->Data_File->filename = 'file4';

        $this->connection->flush();
        $this->connection->clear();
	}
	
    public function prepareTables() {
        $this->tables = array("File_Owner", "Data_File"); 
        parent::prepareTables();
    }

    public function testAccessOneToOneFromForeignSide() {
	    $check = $this->connection->query("FROM File_Owner WHERE File_Owner.name = 'owner1'");
        $owner1 = $this->connection->query("FROM File_Owner.Data_File WHERE File_Owner.name = 'owner1'");
		$owner2 = $this->connection->query("FROM File_Owner.Data_File WHERE File_Owner.name = 'owner2'");
		$this->assertTrue(count($check) == 1);
		$this->assertTrue(count($owner1) == 1);
		$this->assertTrue(count($owner2) == 1);

		$check = $check[0];
		$owner1 = $owner1[0];
		$owner2 = $owner2[0];
		
		$check2 = $this->connection->query("FROM File_Owner WHERE File_Owner.id = ".$owner1->get('id'));
		$this->assertEqual(1, count($check2));
		$check2 = $check2[0];
		$this->assertEqual('owner1', $check2->get('name'));
		
        $this->assertTrue(isset($owner1->Data_File));
		$this->assertFalse(isset($owner2->Data_File));
        $this->assertEqual(1, $check->get('id'));
        $this->assertEqual(1, $owner1->get('id'));
		$this->assertEqual($owner1->get('id'), $check->get('id'));
		$this->assertEqual(2, $owner2->get('id'));
    }
	
	public function testAccessOneToOneFromLocalSide() {
	    $check = $this->connection->query("FROM Data_File WHERE Data_File.filename = 'file4'");
        $file1 = $this->connection->query("FROM Data_File.File_Owner WHERE Data_File.filename = 'file4'");
		$file2 = $this->connection->query("FROM Data_File.File_Owner WHERE Data_File.filename = 'file1'");
		$this->assertTrue(count($check) == 1);
		$this->assertTrue(count($file1) == 1);
		$this->assertTrue(count($file2) == 1);

		$check = $check[0];
		$file1 = $file1[0];
		$file2 = $file2[0];
		
		$check2 = $this->connection->query("FROM Data_File WHERE Data_File.id = ".$file1->get('id'));
		$this->assertEqual(1, count($check2));
		$check2 = $check2[0];
		$this->assertEqual('file4', $check2->get('filename'));
		
        $this->assertTrue(isset($file1->File_Owner));
		$this->assertFalse(isset($file2->File_Owner));
        $this->assertEqual(4, $check->get('id'));
        $this->assertEqual(4, $file1->get('id'));
		$this->assertEqual($file1->get('id'), $check->get('id'));
		$this->assertEqual(1, $file2->get('id'));

    }
}
?>

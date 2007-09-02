<?php
class ValidatorTest_ClientToAddressModel extends Doctrine_Record {

	public function setTableDefinition() {

		$this->hasColumn("client_id", "integer", 11, array('primary' => true));
		$this->hasColumn("address_id", "integer", 11, array('primary' => true));
	}

	public function construct(){
	}

	public function setUp() {
	}
}

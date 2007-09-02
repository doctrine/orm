<?php
class ValidatorTest_AddressModel extends Doctrine_Record {
	public function setTableDefinition() {

		$this->hasColumn("id", "integer", 11, array('autoincrement' => true,
													'primary'       => true
													));
		$this->hasColumn('address1', 'string', 255, array('notnull' => true, 'notblank'));
		$this->hasColumn('address2', 'string', 255, array('notnull' => true));
		$this->hasColumn('city', 'string', 255, array('notnull' => true, 'notblank'));
		$this->hasColumn('state', 'string', 10, array('notnull' => true, 'notblank', 'usstate'));
		$this->hasColumn('zip', 'string', 15, array('notnull' => true, 'notblank', 'regexp' => '/^[0-9-]*$/'));
	}

	public function setUp() {
		$this->hasMany('ValidatorTest_ClientModel', array('local' => 'address_id', 'foreign' => 'client_id', 'refClass' => 'ValidatorTest_ClientToAddressModel'));
	}
}

<?php
class ValidatorTest_AddressModel extends Doctrine_Record {
	public static function initMetadata($class) {
		$class->setColumn("id", "integer", 11, array('autoincrement' => true,
													'primary'       => true
													));
		$class->setColumn('address1', 'string', 255, array('notnull' => true, 'notblank'));
		$class->setColumn('address2', 'string', 255, array('notnull' => true));
		$class->setColumn('city', 'string', 255, array('notnull' => true, 'notblank'));
		$class->setColumn('state', 'string', 10, array('notnull' => true, 'notblank', 'usstate'));
		$class->setColumn('zip', 'string', 15, array('notnull' => true, 'notblank', 'regexp' => '/^[0-9-]*$/'));
		$class->hasMany('ValidatorTest_ClientModel', array('local' => 'address_id', 'foreign' => 'client_id', 'refClass' => 'ValidatorTest_ClientToAddressModel'));
	}
}

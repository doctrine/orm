<?php
class ValidatorTest_ClientModel extends Doctrine_Entity {
	public static function initMetadata($class) {
		$class->setColumn('id', 'integer', 4, array('notnull' => true,
	                                           'primary' => true,
	                                           'autoincrement' => true,
	                                           'unsigned' => true));
		$class->setColumn('short_name', 'string', 32, array('notnull' => true, 'notblank', 'unique' => true));
		$class->hasMany("ValidatorTest_AddressModel", array('local' => 'client_id', 'foreign' => 'address_id', 'refClass' => 'ValidatorTest_ClientToAddressModel'));
	}
}

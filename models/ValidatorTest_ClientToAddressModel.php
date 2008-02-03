<?php
class ValidatorTest_ClientToAddressModel extends Doctrine_Record {

	public static function initMetadata($class) {
		$class->setColumn("client_id", "integer", 11, array('primary' => true));
		$class->setColumn("address_id", "integer", 11, array('primary' => true));
	}
}

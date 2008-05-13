<?php
class ClientModel extends Doctrine_Entity
{
	public static function initMetadata($class) 
    {
		$class->setTableName('clients');

		$class->setColumn('id', 'integer', 4, array('notnull' => true,
	                                           'primary' => true,
	                                           'autoincrement' => true,
	                                           'unsigned' => true));
		$class->setColumn('short_name', 'string', 32, array('notnull' => true, 'notblank', 'unique' => true));
		$class->hasMany('AddressModel', array('local' => 'client_id', 'foreign' => 'address_id', 'refClass' => 'ClientToAddressModel'));
	}
}

class ClientToAddressModel extends Doctrine_Entity 
{
	public static function initMetadata($class) 
    {
		$class->setTableName('clients_to_addresses');

		$class->setColumn('client_id', 'integer', 11, array('primary' => true));
		$class->setColumn('address_id', 'integer', 11, array('primary' => true));
		
		$class->hasOne('ClientModel', array('local' => 'client_id', 'foreign' => 'id', 'onDelete' => 'CASCADE'));
    	$class->hasOne('AddressModel', array('local' => 'address_id', 'foreign' => 'id', 'onDelete' => 'CASCADE'));
	}
}

class AddressModel extends Doctrine_Entity 
{
	public static function initMetadata($class) 
    {
		$class->setTableName('addresses');

		$class->setColumn('id', 'integer', 11, array('autoincrement' => true,
													'primary'       => true
													));
		$class->setColumn('address1', 'string', 255, array('notnull' => true, 'notblank'));
		$class->setColumn('address2', 'string', 255, array('notnull' => true));
		$class->setColumn('city', 'string', 255, array('notnull' => true, 'notblank'));
		$class->setColumn('state', 'string', 10, array('notnull' => true, 'notblank', 'usstate'));
		$class->setColumn('zip', 'string', 15, array('notnull' => true, 'notblank', 'regexp' => '/^[0-9-]*$/'));
		$class->hasMany('ClientModel', array('local' => 'address_id', 'foreign' => 'client_id', 'refClass' => 'ClientToAddressModel'));
	}
}

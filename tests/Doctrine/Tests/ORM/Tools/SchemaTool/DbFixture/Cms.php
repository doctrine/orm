<?php
$fixtures = array (
  'cms_addresses' => 
  array (
    0 => 
    array (
      'name' => 'id',
      'values' => 
      array (
      ),
      'primary' => true,
      'unique' => false,
      'default' => NULL,
      'notnull' => true,
      'autoincrement' => true,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("Integer")),
      'length' => NULL,
      'unsigned' => false,
      'fixed' => false,
    ),
    1 => 
    array (
      'name' => 'country',
      'values' => 
      array (
      ),
      'primary' => false,
      'unique' => false,
      'default' => NULL,
      'notnull' => true,
      'autoincrement' => false,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("String")),
      'length' => 50,
      'unsigned' => false,
      'fixed' => false,
    ),
    2 => 
    array (
      'name' => 'zip',
      'values' => 
      array (
      ),
      'primary' => false,
      'unique' => false,
      'default' => NULL,
      'notnull' => true,
      'autoincrement' => false,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("String")),
      'length' => 50,
      'unsigned' => false,
      'fixed' => false,
    ),
    3 => 
    array (
      'name' => 'city',
      'values' => 
      array (
      ),
      'primary' => false,
      'unique' => false,
      'default' => NULL,
      'notnull' => true,
      'autoincrement' => false,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("String")),
      'length' => 50,
      'unsigned' => false,
      'fixed' => false,
    ),
    4 => 
    array (
      'name' => 'user_id',
      'values' => 
      array (
      ),
      'primary' => false,
      'unique' => false,
      'default' => NULL,
      'notnull' => false,
      'autoincrement' => false,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("Integer")),
      'length' => NULL,
      'unsigned' => false,
      'fixed' => false,
    ),
  ),
  'cms_articles' => 
  array (
    0 => 
    array (
      'name' => 'id',
      'values' => 
      array (
      ),
      'primary' => true,
      'unique' => false,
      'default' => NULL,
      'notnull' => true,
      'autoincrement' => true,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("Integer")),
      'length' => NULL,
      'unsigned' => false,
      'fixed' => false,
    ),
    1 => 
    array (
      'name' => 'topic',
      'values' => 
      array (
      ),
      'primary' => false,
      'unique' => false,
      'default' => NULL,
      'notnull' => true,
      'autoincrement' => false,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("String")),
      'length' => 255,
      'unsigned' => false,
      'fixed' => false,
    ),
    2 => 
    array (
      'name' => 'text',
      'values' => 
      array (
      ),
      'primary' => false,
      'unique' => false,
      'default' => NULL,
      'notnull' => true,
      'autoincrement' => false,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("String")),
      'length' => 255,
      'unsigned' => false,
      'fixed' => false,
    ),
    3 => 
    array (
      'name' => 'user_id',
      'values' => 
      array (
      ),
      'primary' => false,
      'unique' => false,
      'default' => NULL,
      'notnull' => false,
      'autoincrement' => false,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("Integer")),
      'length' => NULL,
      'unsigned' => false,
      'fixed' => false,
    ),
  ),
  'cms_groups' => 
  array (
    0 => 
    array (
      'name' => 'id',
      'values' => 
      array (
      ),
      'primary' => true,
      'unique' => false,
      'default' => NULL,
      'notnull' => true,
      'autoincrement' => true,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("Integer")),
      'length' => NULL,
      'unsigned' => false,
      'fixed' => false,
    ),
    1 => 
    array (
      'name' => 'name',
      'values' => 
      array (
      ),
      'primary' => false,
      'unique' => false,
      'default' => NULL,
      'notnull' => true,
      'autoincrement' => false,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("String")),
      'length' => 50,
      'unsigned' => false,
      'fixed' => false,
    ),
  ),
  'cms_phonenumbers' => 
  array (
    0 => 
    array (
      'name' => 'phonenumber',
      'values' => 
      array (
      ),
      'primary' => true,
      'unique' => false,
      'default' => NULL,
      'notnull' => true,
      'autoincrement' => false,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("String")),
      'length' => 50,
      'unsigned' => false,
      'fixed' => false,
    ),
    1 => 
    array (
      'name' => 'user_id',
      'values' => 
      array (
      ),
      'primary' => false,
      'unique' => false,
      'default' => NULL,
      'notnull' => false,
      'autoincrement' => false,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("Integer")),
      'length' => NULL,
      'unsigned' => false,
      'fixed' => false,
    ),
  ),
  'cms_users' => 
  array (
    0 => 
    array (
      'name' => 'id',
      'values' => 
      array (
      ),
      'primary' => true,
      'unique' => false,
      'default' => NULL,
      'notnull' => true,
      'autoincrement' => true,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("Integer")),
      'length' => NULL,
      'unsigned' => false,
      'fixed' => false,
    ),
    1 => 
    array (
      'name' => 'status',
      'values' => 
      array (
      ),
      'primary' => false,
      'unique' => false,
      'default' => NULL,
      'notnull' => true,
      'autoincrement' => false,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("String")),
      'length' => 50,
      'unsigned' => false,
      'fixed' => false,
    ),
    2 => 
    array (
      'name' => 'username',
      'values' => 
      array (
      ),
      'primary' => false,
      'unique' => true,
      'default' => NULL,
      'notnull' => true,
      'autoincrement' => false,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("String")),
      'length' => 255,
      'unsigned' => false,
      'fixed' => false,
    ),
    3 => 
    array (
      'name' => 'name',
      'values' => 
      array (
      ),
      'primary' => false,
      'unique' => false,
      'default' => NULL,
      'notnull' => true,
      'autoincrement' => false,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("String")),
      'length' => 255,
      'unsigned' => false,
      'fixed' => false,
    ),
  ),
  'cms_users_groups' => 
  array (
    0 => 
    array (
      'name' => 'user_id',
      'values' => 
      array (
      ),
      'primary' => true,
      'unique' => false,
      'default' => '0',
      'notnull' => true,
      'autoincrement' => false,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("Integer")),
      'length' => NULL,
      'unsigned' => false,
      'fixed' => false,
    ),
    1 => 
    array (
      'name' => 'group_id',
      'values' => 
      array (
      ),
      'primary' => true,
      'unique' => false,
      'default' => '0',
      'notnull' => true,
      'autoincrement' => false,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("Integer")),
      'length' => NULL,
      'unsigned' => false,
      'fixed' => false,
    ),
  ),
);

return $fixtures;
?>

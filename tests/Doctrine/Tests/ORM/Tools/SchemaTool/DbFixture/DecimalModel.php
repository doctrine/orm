<?php
return array (
  'decimal_model' => 
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
      'name' => 'decimal',
      'values' => 
      array (
      ),
      'primary' => false,
      'unique' => false,
      'default' => NULL,
      'notnull' => true,
      'autoincrement' => false,
      'type' => 
      Doctrine\DBAL\Types\Type::getType(strtolower("Decimal")),
      'length' => 5,
      'unsigned' => false,
      'fixed' => false,
    ),
  ),
);
?>

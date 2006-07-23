<?php
$query = new Doctrine_RawSql($session);

$query->select('{entity.name}')
      ->from('entity');

$query->addComponent("entity", "User");

$coll = $query->execute();
?>

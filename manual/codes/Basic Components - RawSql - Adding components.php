<?php
$query = new Doctrine_RawSql($session);

$query->parseQuery("SELECT {entity.*}, {phonenumber.*} 
                   FROM entity 
                   LEFT JOIN phonenumber 
                   ON phonenumber.entity_id = entity.id");

$query->addComponent("entity", "Entity");
$query->addComponent("phonenumber", "Phonenumber");

$entities = $query->execute();
?>

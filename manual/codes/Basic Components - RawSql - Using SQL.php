<?php
$query = new Doctrine_RawSql($session);

$query->parseQuery("SELECT {entity.name} FROM entity");
        
$entities = $query->execute();
?>

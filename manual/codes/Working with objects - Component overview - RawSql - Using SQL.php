<?php
$query = new Doctrine_RawSql($conn);

$query->parseQuery("SELECT {entity.name} FROM entity");
        
$entities = $query->execute();
?>

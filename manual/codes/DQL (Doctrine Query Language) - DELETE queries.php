<?php
$q    = 'DELETE FROM Account WHERE id > ?';

$rows = $this->conn->query($q, array(3));

// the same query using the query interface

$q = new Doctrine_Query();

$rows = $q->update('Account')
          ->where('id > ?')
          ->execute(array(3));
          
print $rows; // the number of affected rows
?>

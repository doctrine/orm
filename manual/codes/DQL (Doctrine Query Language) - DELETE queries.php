<?php
$q    = 'DELETE FROM Account WHERE id > ?';

$rows = $this->conn->query($q, array(3));

// the same query using the query interface

$q = new Doctrine_Query();

$rows = $q->delete('Account')
          ->from('Account a')
          ->where('a.id > ?', 3)
          ->execute();
          
print $rows; // the number of affected rows
?>

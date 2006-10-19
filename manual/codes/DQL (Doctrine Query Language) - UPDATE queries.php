<?php
$q    = 'UPDATE Account SET amount = amount + 200 WHERE id > 200';

$rows = $this->conn->query($q);

// the same query using the query interface

$q = new Doctrine_Query();

$rows = $q->update('Account')
          ->set('amount', 'amount + 200')
          ->where('id > 200')
          ->execute();
          
print $rows; // the number of affected rows
?>

<code>
DELETE FROM <component_name>
    [WHERE <where_condition>]
    [ORDER BY ...]
    [LIMIT <record_count>]
</code>

* The DELETE statement deletes records from //component_name// and returns the number of records deleted.

* The optional WHERE clause specifies the conditions that identify which records to delete.
Without WHERE clause, all records are deleted.

* If the ORDER BY clause is specified, the records are deleted in the order that is specified.

* The LIMIT clause places a limit on the number of rows that can be deleted.
The statement will stop as soon as it has deleted //record_count// records.



<code type="php">
$q    = 'DELETE FROM Account WHERE id > ?';

$rows = $this->conn->query($q, array(3));

// the same query using the query interface

$q = new Doctrine_Query();

$rows = $q->delete('Account')
          ->from('Account a')
          ->where('a.id > ?', 3)
          ->execute();

print $rows; // the number of affected rows
</code>

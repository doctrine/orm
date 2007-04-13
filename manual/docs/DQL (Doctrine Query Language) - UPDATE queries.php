UPDATE statement syntax:
<code>
UPDATE //component_name//
    SET //col_name1//=//expr1// [, //col_name2//=//expr2// ...]
    [WHERE //where_condition//]
    [ORDER BY ...]
    [LIMIT //record_count//]
</code>

* The UPDATE statement updates columns of existing records in //component_name// with new values and returns the number of affected records.

* The SET clause indicates which columns to modify and the values they should be given.

* The optional WHERE clause specifies the conditions that identify which records to update.
Without WHERE clause, all records are updated.

* The optional ORDER BY clause specifies the order in which the records are being updated.

* The LIMIT clause places a limit on the number of records that can be updated. You can use LIMIT row_count to restrict the scope of the UPDATE. 
A LIMIT clause is a **rows-matched restriction** not a rows-changed restriction.
The statement stops as soon as it has found //record_count// rows that satisfy the WHERE clause, whether or not they actually were changed.


<code type="php">
$q    = 'UPDATE Account SET amount = amount + 200 WHERE id > 200';

$rows = $this->conn->query($q);

// the same query using the query interface

$q = new Doctrine_Query();

$rows = $q->update('Account')
          ->set('amount', 'amount + 200')
          ->where('id > 200')
          ->execute();
          
print $rows; // the number of affected rows
</code>

<?php ?>
Doctrine supports transaction savepoints. This means you can set named transactions and have them nested.
<br \><br \>
The Doctrine_Transaction::beginTransaction(<i>$savepoint</i>) sets a named transaction savepoint with a name of <i>$savepoint</i>.
If the current transaction has a savepoint with the same name, the old savepoint is deleted and a new one is set.
<br \><br \>
<?php
renderCode("<?php
try {
    \$conn->beginTransaction();
    // do some operations here

    // creates a new savepoint called mysavepoint
    \$conn->beginTransaction('mysavepoint');
    try {
        // do some operations here

        \$conn->commit('mysavepoint');
    } catch(Exception \$e) {
        \$conn->rollback('mysavepoint');
    }
    \$conn->commit();
} catch(Exception \$e) {
    \$conn->rollback();
}
?>");
?>

<br \><br \>
The Doctrine_Transaction::rollback(<i>$savepoint</i>) rolls back a transaction to the named savepoint.
Modifications that the current transaction made to rows after the savepoint was set are undone in the rollback.

NOTE: Mysql, for example, does not release the row locks that were stored in memory after the savepoint.
<br \><br \>
Savepoints that were set at a later time than the named savepoint are deleted.
<br \><br \>
The Doctrine_Transaction::commit(<i>$savepoint</i>) removes the named savepoint from the set of savepoints of the current transaction. 
<br \><br \>
All savepoints of the current transaction are deleted if you execute a commit or rollback is being called without savepoint name parameter.
<?php
renderCode("<?php
try {
    \$conn->beginTransaction();
    // do some operations here

    // creates a new savepoint called mysavepoint
    \$conn->beginTransaction('mysavepoint');
    
    // do some operations here

    \$conn->commit();  // deletes all savepoints
} catch(Exception \$e) {
    \$conn->rollback(); // deletes all savepoints
}
?>");
?>

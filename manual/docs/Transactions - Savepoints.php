<?php ?>
Doctrine supports transaction savepoints. This means you can set named transactions and have them nested.



The Doctrine_Transaction::beginTransaction(//$savepoint//) sets a named transaction savepoint with a name of //$savepoint//.
If the current transaction has a savepoint with the same name, the old savepoint is deleted and a new one is set.



<code type="php">
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
?></code>




The Doctrine_Transaction::rollback(//$savepoint//) rolls back a transaction to the named savepoint.
Modifications that the current transaction made to rows after the savepoint was set are undone in the rollback.

NOTE: Mysql, for example, does not release the row locks that were stored in memory after the savepoint.



Savepoints that were set at a later time than the named savepoint are deleted.



The Doctrine_Transaction::commit(//$savepoint//) removes the named savepoint from the set of savepoints of the current transaction. 



All savepoints of the current transaction are deleted if you execute a commit or rollback is being called without savepoint name parameter.
<code type="php">
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
?></code>


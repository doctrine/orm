A database transaction is a unit of interaction with a database management system or similar system that is treated in a coherent 
and reliable way independent of other transactions that must be either entirely completed or aborted. 
Ideally, a database system will guarantee all of the ACID(Atomicity, Consistency, Isolation, and Durability) properties for each transaction. 

* [http://en.wikipedia.org/wiki/Atomicity Atomicity] refers to the ability of the DBMS to guarantee that either all of the tasks of a transaction are performed or none of them are. The transfer of funds can be completed or it can fail for a multitude of reasons, but atomicity guarantees that one account won't be debited if the other is not credited as well.


* [http://en.wikipedia.org/wiki/Database_consistency Consistency] refers to the database being in a legal state when the transaction begins and when it ends. This means that a transaction can't break the rules, or //integrity constraints//, of the database. If an integrity constraint states that all accounts must have a positive balance, then any transaction violating this rule will be aborted.


* [http://en.wikipedia.org/wiki/Isolation_%28computer_science%29 Isolation] refers to the ability of the application to make operations in a transaction appear isolated from all other operations. This means that no operation outside the transaction can ever see the data in an intermediate state; a bank manager can see the transferred funds on one account or the other, but never on both—even if she ran her query while the transfer was still being processed. More formally, isolation means the transaction history (or [http://en.wikipedia.org/wiki/Schedule_%28computer_science%29 schedule]) is [http://en.wikipedia.org/wiki/Serializability serializable]. For performance reasons, this ability is the most often relaxed constraint. See the [/wiki/Isolation_%28computer_science%29 isolation] article for more details.


* [http://en.wikipedia.org/wiki/Durability_%28computer_science%29 Durability] refers to the guarantee that once the user has been notified of success, the transaction will persist, and not be undone. This means it will survive system failure, and that the [http://en.wikipedia.org/wiki/Database_system database system] has checked the integrity constraints and won't need to abort the transaction. Typically, all transactions are written into a [http://en.wikipedia.org/wiki/Database_log log] that can be played back to recreate the system to its state right before the failure. A transaction can only be deemed committed after it is safely in the log.


- //from [http://www.wikipedia.org wikipedia]//



In Doctrine all operations are wrapped in transactions by default. There are some things that should be noticed about how Doctrine works internally:

*  Doctrine uses application level transaction nesting.


*  Doctrine always executes INSERT / UPDATE / DELETE queries at the end of transaction (when the outermost commit is called). The operations
are performed in the following order: all inserts, all updates and last all deletes. Doctrine knows how to optimize the deletes so that 
delete operations of the same component are gathered in one query.



<code type="php">
$conn->beginTransaction();

$user = new User();
$user->name = 'New user';
$user->save();

$user = $conn->getTable('User')->find(5);
$user->name = 'Modified user';
$user->save();

$conn->commit(); // all the queries are executed here
</code>

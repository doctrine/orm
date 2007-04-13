Doctrine Collections can be deleted in very same way is Doctrine Records you just call delete() method.
As for all collections Doctrine knows how to perform single-shot-delete meaning it only performs one 
database query for the each collection. 

 

For example if we have collection of users which own [0-*] phonenumbers. When deleting the collection
of users doctrine only performs two queries for this whole transaction. The queries would look something like:



DELETE FROM user WHERE id IN (1,2,3, ... ,N)

DELETE FROM phonenumber WHERE id IN (1,2,3, ... ,M)




It should also be noted that Doctrine is smart enough to perform single-shot-delete per table when transactions are used.
So if you are deleting a lot of records and want to optimize the operation just wrap the delete calls in Doctrine_Connection transaction.


<code type="php">
// delete all users with name 'John'

$users = $table->findByDql("name LIKE '%John%'");

$users->delete();
</code>

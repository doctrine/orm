Doctrine Collections can be deleted in very same way is Doctrine Records you just call delete() method.
As for all collections Doctrine knows how to perform single-shot-delete meaning it only performs one 
database query for the each collection. 
<br \> <br \>
For example if we have collection of users which own [0-*] phonenumbers. When deleting the collection
of users doctrine only performs two queries for this whole transaction. The queries would look something like:
<br \><br \>
DELETE FROM user WHERE id IN (1,2,3, ... ,N)<br \>
DELETE FROM phonenumber WHERE id IN (1,2,3, ... ,M)<br \>

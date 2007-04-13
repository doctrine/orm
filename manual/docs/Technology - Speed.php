* <b class="title">Lazy initialization**

For collection elements



* <b class="title">Subselect fetching**

Doctrine knows how to fetch collections efficiently using a subselect.



* <b class="title">Executing SQL statements later, when needed**

The connection never issues an INSERT or UPDATE until it is actually needed. So if an exception occurs and you need to abort the transaction, some statements will never actually be issued. Furthermore, this keeps lock times in the database as short as possible (from the late UPDATE to the transaction end).



* <b class="title">Join fetching**

Doctrine knows how to fetch complex object graphs using joins and subselects



* <b class="title">Multiple collection fetching strategies**

Doctrine has multiple collection fetching strategies for performance tuning.



* <b class="title">Dynamic mixing of fetching strategies**

Fetching strategies can be mixed and for example users can be fetched in a batch collection while
users' phonenumbers are loaded in offset collection using only one query.



* <b class="title">Driver specific optimizations**

Doctrine knows things like bulk-insert on mysql



* <b class="title">Transactional single-shot delete**

Doctrine knows how to gather all the primary keys of the pending objects in delete list and performs only one sql delete statement per table.



* <b class="title">Updating only the modified columns.**

Doctrine always knows which columns have been changed.



* <b class="title">Never inserting/updating unmodified objects.**

Doctrine knows if the the state of the record has changed.



* <b class="title">PDO for database abstraction**

PDO is by far the fastest availible database abstraction layer for php.






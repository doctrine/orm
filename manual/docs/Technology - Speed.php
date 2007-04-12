<li \><b class="title">Lazy initialization</b><br \>
For collection elements
<br \><br \>
<li \><b class="title">Subselect fetching</b><br \>
Doctrine knows how to fetch collections efficiently using a subselect.
<br \><br \>
<li \><b class="title">Executing SQL statements later, when needed</b><br \>
The connection never issues an INSERT or UPDATE until it is actually needed. So if an exception occurs and you need to abort the transaction, some statements will never actually be issued. Furthermore, this keeps lock times in the database as short as possible (from the late UPDATE to the transaction end).
<br \><br \>
<li \><b class="title">Join fetching</b><br \>
Doctrine knows how to fetch complex object graphs using joins and subselects
<br \><br \>
<li \><b class="title">Multiple collection fetching strategies</b><br \>
Doctrine has multiple collection fetching strategies for performance tuning.
<br \><br \>
<li \><b class="title">Dynamic mixing of fetching strategies</b><br \>
Fetching strategies can be mixed and for example users can be fetched in a batch collection while
users' phonenumbers are loaded in offset collection using only one query.
<br \><br \>
<li \><b class="title">Driver specific optimizations</b><br \>
Doctrine knows things like bulk-insert on mysql
<br \><br \>
<li \><b class="title">Transactional single-shot delete</b><br \>
Doctrine knows how to gather all the primary keys of the pending objects in delete list and performs only one sql delete statement per table.
<br \><br \>
<li \><b class="title">Updating only the modified columns.</b><br \>
Doctrine always knows which columns have been changed.
<br \><br \>
<li \><b class="title">Never inserting/updating unmodified objects.</b><br \>
Doctrine knows if the the state of the record has changed.
<br \><br \>
<li \><b class="title">PDO for database abstraction</b><br \>
PDO is by far the fastest availible database abstraction layer for php.
<br \><br \>



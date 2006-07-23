Whenever you fetch records with eg. Doctrine_Table::findAll or Doctrine_Session::query methods an instance of
Doctrine_Collection is returned. There are many types of collections in Doctrine and it is crucial to understand
the differences of these collections. Remember choosing the right fetching strategy (collection type) is one of the most 
influental things when it comes to boosting application performance.
<br \><br \>
<li>Immediate Collection<ul>
Fetches all records and all record data immediately into collection memory. Use this collection only if you really need to show all that data
in web page.
<br \><br \>
Example query:<br \>
SELECT id, name, type, created FROM user
<br \><br \></ul>
<li>Batch Collection<ul>
Fetches all record primary keys into colletion memory. When individual collection elements are accessed this collection initializes proxy objects.
When the non-primary-key-property of a proxy object is accessed that object sends request to Batch collection which loads the data
for that specific proxy object as well as other objects close to that proxy object.
<br \><br \>
Example queries:<br \>
SELECT id FROM user<br \>
SELECT id, name, type, created FROM user WHERE id IN (1,2,3,4,5)<br \>
SELECT id, name, type, created FROM user WHERE id IN (6,7,8,9,10)<br \>
[ ... ]<br \>
</ul>
<li>Lazy Collection<ul>
Lazy collection is exactly same as Batch collection with batch size preset to one.
<br \><br \>
Example queries:<br \>
SELECT id FROM user<br \>
SELECT id, name, type, created FROM user WHERE id = 1<br \>
SELECT id, name, type, created FROM user WHERE id = 2<br \>
SELECT id, name, type, created FROM user WHERE id = 3<br \>
[ ... ]<br \>
</ul>
<li>Offset Collection<ul>
Offset collection is the same as immediate collection with the difference that it uses database provided limiting of queries.
<br \><br \>
Example queries:<br \>
SELECT id, name, type, created FROM user LIMIT 5<br \>
SELECT id, name, type, created FROM user LIMIT 5 OFFSET 5<br \>
SELECT id, name, type, created FROM user LIMIT 5 OFFSET 10<br \>
[ ... ]<br \></ul>


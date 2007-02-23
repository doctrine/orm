Doctrine_Collection is a collection of records (see Doctrine_Record). As with records the collections can be deleted and saved using
Doctrine_Collection::delete() and Doctrine_Collection::save() accordingly.
<br \><br \>
When fetching data from database with either DQL API (see Doctrine_Query) or rawSql API (see Doctrine_RawSql) the methods return an instance of
Doctrine_Collection by default.
<br \><br \>
The following example shows how to initialize a new collection:

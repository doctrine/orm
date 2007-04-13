Doctrine_Db is a wrapper for PDO database object. Why should you consider using Doctrine_Db instead of PDO? 



1. It provides efficient eventlistener architecture, hence its easy to add new aspects to existing methods like on-demand-caching



2. Doctrine_Db lazy-connects database. Creating an instance of Doctrine_Db doesn't directly connect database, hence
Doctrine_Db fits perfectly for application using for example page caching.



3. It has many short cuts for commonly used fetching methods like Doctrine_Db::fetchOne().



4. Supports PEAR-like data source names as well as PDO data source names.


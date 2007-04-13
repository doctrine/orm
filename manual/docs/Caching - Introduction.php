<?php ?>
Doctrine_Cache offers an intuitive and easy-to-use query caching solution. It provides the following things:

    *  Multiple cache backends to choose from (including Memcached, APC and Sqlite)
    



    *  Manual tuning and/or self-optimization. Doctrine_Cache knows how to optimize itself, yet it leaves user
    full freedom of whether or not he/she wants to take advantage of this feature.
    



    *  Advanced options for fine-tuning. Doctrine_Cache has many options for fine-tuning performance.
    



    *  Cache hooks itself directly into Doctrine_Db eventlistener system allowing it to be easily added on-demand.





Doctrine_Cache hooks into Doctrine_Db eventlistener system allowing pluggable caching.
It evaluates queries and puts SELECT statements in cache. The caching is based on propabalistics. For example
if savePropability = 0.1 there is a 10% chance that a query gets cached. 



Now eventually the cache would grow very big, hence Doctrine uses propabalistic cache cleaning. 
When calling Doctrine_Cache::clean() with cleanPropability = 0.25 there is a 25% chance of the clean operation being invoked.
What the cleaning does is that it first reads all the queries in the stats file and sorts them by the number of times occurred.
Then if the size is set to 100 it means the cleaning operation will leave 100 most issued queries in cache and delete all other cache entries.








Initializing a new cache instance:



<code type="php">
\$dbh   = new Doctrine_Db('mysql:host=localhost;dbname=test', \$user, \$pass);

\$cache = new Doctrine_Cache('memcache');

// register it as a Doctrine_Db listener

\$dbh->addListener(\$cache);
?></code>



Now you know how to set up the query cache. In the next chapter you'll learn how to tweak the cache in order to get maximum performance.




Doctrine has very comprehensive and fast caching solution.
Its cache is <b>always up-to-date</b>.
In order to achieve this doctrine does the following things:
<br /><br />
<table border=1 class='dashed' cellpadding=0 cellspacing=0>
<tr><td>
1. Every Doctrine_Table has its own cache directory. The default is cache/componentname/. All the cache files are saved into that directory.
The format of each cache file is [primarykey].cache.
<br /><br />
2. When retrieving records from the database doctrine always tries to hit the cache first.
<br /><br />
3. If a record (Doctrine_Record) is retrieved from database or inserted into database it will be saved into cache.
<br /><br />
4. When a Data Access Object is deleted or updated it will be deleted from the cache
</td></tr>
</table>
<br /><br />
Now one might wonder that this kind of solution won't work since eventually the cache will be a copy of database!
So doctrine does the following things to ensure the cache won't get too big:
<br /><br />
<table border=1 class='dashed' cellpadding=0 cellspacing=0>

<tr><td>

1. Every time a cache file is accessed the id of that record will be added into the $fetched property of Doctrine_Cache
<br /><br />
2. At the end of each script the Doctrine_Cache destructor will write all these primary keys at the end of a stats.cache file
<br /><br />
3. Doctrine does propabalistic cache cleaning. The default interval is 200 page loads (= 200 constructed Doctrine_Managers). Basically this means
that the average number of page loads between cache cleans is 200.
<br /><br />
4. On every cache clean stats.cache files are being read and the least accessed cache files
(cache files that have the smallest id occurance in the stats file) are then deleted. 
For example if the cache size is set to 200 and the number of files in cache is 300, then 100 least accessed files are being deleted.
Doctrine also clears every stats.cache file.

</td></tr>
</table>
<br /><br />
So for every 199 fast page loads there is one page load which suffers a little overhead from the cache cleaning operation.

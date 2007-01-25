<?php ?>
Doctrine offers many index options, some of them being db-specific. Here is a full list of availible options:
<div class='sql'>
<pre>
unique      => boolean(true / false)        
        whether or not the index is unique index

sorting     => string('ASC' / 'DESC')      
        what kind of sorting does the index use (ascending / descending)

primary     => boolean(true / false)        
        whether or not the index is primary index

fulltext    => boolean(true / false)        
        whether or not the specified index is a FULLTEXT index (only availible on Mysql)
        
gist        => boolean(true / false)        
        whether or not the specified index is a GiST index (only availible on Pgsql)
</pre>
</div>

<?php ?>
Doctrine supports many special indexes. These include Mysql FULLTEXT and Pgsql GiST indexes.
In the following example we define a Mysql FULLTEXT index for the field 'content'.



<?php 
renderCode("<?php
class Article 
{
    public function setTableDefinition() 
    {
    	\$this->hasColumn('name', 'string');
        \$this->hasColumn('content', 'string');

        \$this->index('content', array('fields' => 'content',
                                       'type' => 'fulltext'));
    }
}
?>");

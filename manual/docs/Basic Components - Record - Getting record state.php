Every Doctrine_Record has a state. First of all record can be transient or persistent.
Every record that is retrieved from database is persistent and every newly created record is transient.
If a Doctrine_Record is retrieved from database but the only loaded property is its primary key, then this record
has a state called proxy.
<br /><br />
Every transient and persistent Doctrine_Record is either clean or dirty. Doctrine_Record is clean when none of its properties are changed and
dirty when atleast one of its properties has changed. 

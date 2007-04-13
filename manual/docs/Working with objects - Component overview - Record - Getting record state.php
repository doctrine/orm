Every Doctrine_Record has a state. First of all record can be transient or persistent.
Every record that is retrieved from database is persistent and every newly created record is transient.
If a Doctrine_Record is retrieved from database but the only loaded property is its primary key, then this record
has a state called proxy.



Every transient and persistent Doctrine_Record is either clean or dirty. Doctrine_Record is clean when none of its properties are changed and
dirty when atleast one of its properties has changed. 

<code type="php">
$state = $record->getState();

switch($state):
    case Doctrine_Record::STATE_PROXY:
        // record is in proxy state, 
        // meaning its persistent but not all of its properties are
        // loaded from the database
    break;
    case Doctrine_Record::STATE_TCLEAN:
        // record is transient clean,
        // meaning its transient and 
        // none of its properties are changed
    break;
    case Doctrine_Record::STATE_TDIRTY:
        // record is transient dirty,
        // meaning its transient and 
        // some of its properties are changed
    break;
    case Doctrine_Record::STATE_DIRTY:
        // record is dirty, 
        // meaning its persistent and 
        // some of its properties are changed
    break;
    case Doctrine_Record::STATE_CLEAN:
        // record is clean,
        // meaning its persistent and 
        // none of its properties are changed
    break;
endswitch;
</code>

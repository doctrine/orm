<?php
$state = $record->getState();

switch($state):
    case Doctrine_Record::STATE_PROXY:
        // data access object is in proxy state, 
        // meaning its persistent but not all of its properties are
        // loaded from the database
    break;
    case Doctrine_Record::STATE_TCLEAN:
        // data access object is transient clean,
        // meaning its transient and 
        // none of its properties are changed
    break;
    case Doctrine_Record::STATE_TDIRTY:
        // data access object is transient dirty, 
        // meaning its transient and 
        // some of its properties are changed
    break;
    case Doctrine_Record::STATE_DIRTY:
        // data access object is dirty, 
        // meaning its persistent and 
        // some of its properties are changed
    break;
    case Doctrine_Record::STATE_CLEAN:
        // data access object is clean, 
        // meaning its persistent and 
        // none of its properties are changed
    break;
endswitch;
?>

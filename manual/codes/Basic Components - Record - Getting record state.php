<?php
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
?>

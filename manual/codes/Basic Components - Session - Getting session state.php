<?php
switch($session->getState())
    case Doctrine_Session::STATE_ACTIVE:
        // session open and zero open transactions
    break;
    case Doctrine_Session::STATE_ACTIVE:
        // one open transaction
    break;
    case Doctrine_Session::STATE_BUSY:
        // multiple open transactions
    break;
    case Doctrine_Session::STATE_CLOSED:
        // session closed
    break;
endswitch;
?>

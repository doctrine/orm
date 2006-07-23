<?php
$sess = $manager->openSession(Doctrine_DB::getConnection("schema://username:password@hostname/database"));

// get session state:
switch($sess):
    case Doctrine_Session::STATE_BUSY:
        // multiple open transactions
    break;
    case Doctrine_Session::STATE_ACTIVE:
        // one open transaction
    break;
    case Doctrine_Session::STATE_CLOSED:
        // closed state
    break;
    case Doctrine_Session::STATE_OPEN:
        // open state and zero open transactions
    break;
endswitch;

// getting database handler

$dbh = $sess->getDBH();

// flushing the session
$sess->flush();


// print lots of useful info about session:
print $sess;
?>

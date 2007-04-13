Connection state gives you information about how active connection currently is. You can get the current state
by calling Doctrine_Connection::getState().

<code type="php">
switch($conn->getState()):
    case Doctrine_Connection::STATE_ACTIVE:
        // connection open and zero open transactions
    break;
    case Doctrine_Connection::STATE_ACTIVE:
        // one open transaction
    break;
    case Doctrine_Connection::STATE_BUSY:
        // multiple open transactions
    break;
    case Doctrine_Connection::STATE_CLOSED:
        // connection closed
    break;
endswitch;
</code>

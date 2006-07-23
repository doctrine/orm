<?php
function saveUserAndGroup(Doctrine_Session $session, User $user, Group $group) {
    $session->beginTransaction();
    
    $user->save();
    
    $group->save();

    $session->commit();
}

try {
    $session->beginTransaction();

    saveUserAndGroup($session,$user,$group);
    saveUserAndGroup($session,$user2,$group2);
    saveUserAndGroup($session,$user3,$group3);

    $session->commit();
} catch(Doctrine_Exception $e) {
    $session->rollback();
}
?>

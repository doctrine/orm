<?php
try {
    $session->beginTransaction();
    
    $user->save();
        
        $session->beginTransaction();
            $group->save();
            $email->save();
            
        $session->commit();

    $session->commit();
} catch(Exception $e) {
    $session->rollback();
}
?>

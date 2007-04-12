
<code type="php">
try {
    $conn->beginTransaction();
    
    $user->save();
        
        $conn->beginTransaction();
            $group->save();
            $email->save();
            
        $conn->commit();

    $conn->commit();
} catch(Exception $e) {
    $conn->rollback();
}
</code>

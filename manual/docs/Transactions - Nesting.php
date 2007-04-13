
<code type="php">
function saveUserAndGroup(Doctrine_Connection $conn, User $user, Group $group) {
    $conn->beginTransaction();
    
    $user->save();

    $group->save();

    $conn->commit();
}

try {
    $conn->beginTransaction();

    saveUserAndGroup($conn,$user,$group);
    saveUserAndGroup($conn,$user2,$group2);
    saveUserAndGroup($conn,$user3,$group3);

    $conn->commit();
} catch(Doctrine_Exception $e) {
    $conn->rollback();
}
</code>

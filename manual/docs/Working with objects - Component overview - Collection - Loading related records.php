Doctrine provides means for effiently retrieving all related records for all record elements. That means
when you have for example a collection of users you can load all phonenumbers for all users by simple calling
the loadRelated() method.

<code type="php">
$users = $conn->query("FROM User");

// now lets load phonenumbers for all users

$users->loadRelated("Phonenumber");

foreach($users as $user) {
    print $user->Phonenumber->phonenumber;
    // no additional db queries needed here
}

// the loadRelated works an any relation, even associations:

$users->loadRelated("Group");

foreach($users as $user) {
    print $user->Group->name;
}
</code>

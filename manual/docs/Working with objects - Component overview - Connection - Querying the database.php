Doctrine_Connection::query() is a simple method for efficient object retrieval. It takes one parameter (DQL query) and optionally prepared statement params.

<code type="php">

// select all users

$users = $conn->query('FROM User');

// select all users where user email is jackdaniels@drinkmore.info

$users = $conn->query("FROM User WHERE User.Email.address = 'jackdaniels@drinkmore.info'");

// using prepared statements

$users = $conn->query('FROM User WHERE User.name = ?', array('Jack'));
</code>

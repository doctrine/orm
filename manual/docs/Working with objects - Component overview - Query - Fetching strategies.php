
<code type="php">
// select all users and load the data directly (Immediate fetching strategy)

$coll = $conn->query("FROM User-I");

// or

$coll = $conn->query("FROM User-IMMEDIATE");

// select all users and load the data in batches

$coll = $conn->query("FROM User-B");

// or 

$coll = $conn->query("FROM User-BATCH");

// select all user and use lazy fetching

$coll = $conn->query("FROM User-L");

// or 

$coll = $conn->query("FROM User-LAZY");
</code>

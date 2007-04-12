
<code type="php">

// works only if you use doctrine database handler

$dbh = $conn->getDBH();

$times = $dbh->getExecTimes();

// print all executed queries and their execution times

foreach($dbh->getQueries() as $index => $query) {
    print $query." ".$times[$index];
}

</code>

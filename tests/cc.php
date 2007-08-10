<?php
// include doctrine, and register it's autoloader
require_once dirname(__FILE__) . '/../lib/Doctrine.php';
spl_autoload_register(array('Doctrine', 'autoload'));

$result = unserialize(file_get_contents("coverage.txt"));
$path = $result["path"];
$coverage = $result["coverage"];

$key ="percentage";
if(isset($_GET["order"])){
    $key = $_GET["order"];
 }
$totallines = 0;
$totalcovered = 0;
$totalmaybe = 0;
$totalnotcovered = 0;

$coveredArray = array();
foreach ($coverage as $file => $lines) {
    $pos = strpos($file, $path);
    if($pos === false && $pos !== 0){
        continue;
    }
    
    $class = str_replace(DIRECTORY_SEPARATOR, '_', substr($file, strlen($path), -4));
    $class = str_replace($path, Doctrine::getPath(), $class); 
  
       if (strpos($class, '_Interface')) {
        continue;
    }
   
    if(!class_exists($class)){
        continue;
    }



    $total = count($lines) -1; //we have to remove one since it always reports the last line as a hit
    $covered = 0;
    $maybe = 0;
    $notcovered = 0;
    foreach($lines as $result){
        switch($result){
           case "1":
                $covered++;
                break;
           case "-1":
               $notcovered++;
               break;
           case "-2":
               $maybe++;
               break;
        }
    }
    $covered--; //again we have to remove that last line.
    $totallines += $total;
    $totalcovered += $covered;
    $totalnotcovered += $notcovered;
    $totalmaybe += $maybe;

    if ($total === 0) {
        $total = 1;
    }
    $percentage = round((($covered + $maybe) / $total) * 100, 2);
    $coveredArray[$class] = array("covered" => $covered, "maybe" => $maybe, "notcovered"=>$notcovered, "total" => $total, "percentage" => $percentage);
}


//lets sort it
uasort($coveredArray, "sortArray");
if(isset($_GET["desc"]) && $_GET["desc"] == "true"){
    $coveredArray = array_reverse($coveredArray, true);
 }


?>
<h1>Coverage report for Doctrine</h1>
<p>Default mode shows results sorted by perentage. This can be changed with order = covered|total|maybe|notcovered|percentage and desc=true GET variables</p>
<?php
echo "<table>";
echo "<tr><th></th><th>Percentage</th><th>Total</th><th>Covered</th><th>Maybe</th><th>Not Covered</th><th></th></tr>";
print "<tr><td>" . TOTAL . "</td><td>" . round((($totalcovered + $totalmaybe) / $totallines) * 100, 2) . " % </td><td>$totallines</td><td>$totalcovered</td><td>$totalmaybe</td><td>$totaldead</td><td></td></tr>";
foreach($coveredArray as $class => $info){
    print "<tr><td>" . $class . "</td><td>" . $info["percentage"] . " % </td><td>" . $info["total"] . "</td><td>" . $info["covered"] . "</td><td>" . $info["maybe"] . "</td><td>" . $info["notcovered"]. "</td><td>" . printLink($class) . "</td></tr>";
}
print "</table>";

function sortArray($a, $b){
    global $key;
    if ($a[$key] == $b[$key]) {
        return 0;
    }
    return ($a[$key] < $b[$key]) ? -1 : 1;
}

function printLink($className){
    global $path;
    $className = str_replace("_", "/", $className) . ".php";
    return '<a href="coverage.php?file=' . $path . $className . '">coverage</a>';
 }

function countCovered($value, $key){
    global $covered;
    if($value >= 1){
        $covered++;
    }
}

<?php
// include doctrine, and register it's autoloader
require_once dirname(__FILE__) . '/../lib/Doctrine.php';
spl_autoload_register(array('Doctrine', 'autoload'));

?>
<html>
<head>
<style type="text/css">
    .covered{ background: green;}
    .normal{ background: white;}
    .red{ background: red;}
    .orange{ background: #f90;}

 dl.table-display
{
margin: 2em 0;
padding: 0;
font-family: georgia, times, serif;
}

.table-display dt
{
float: left;
margin: 0 0 0 0;
padding: 0 .5em 0 .5em;
}

/* commented backslash hack for mac-ie5 \*/
dt { clear: both; }
/* end hack */

.table-display dd{
    float: left;
    margin: 0 0 0 0;
    }
</style>
</head>
<body>
<?
$result = unserialize(file_get_contents("coverage.txt"));
$coverage = $result["coverage"];

function getCoverageReport($file){
    global $coverage;
    $html = '<div id="coverage">';
    if(!isset($coverage[$file])){
        $html .= 'No coverage for this file</div>';
        return $html;
    }
    $coveredLines = $coverage[$file]; 
    $fileArray = file($file);
    $html .= '<dl class="table-display">' . "\n";
    foreach($fileArray as $num => $line){
       $linenum = $num+1;
       $html .= '<dt>' . $linenum . '</dt>' . "\n";
       $class ="normal";
       if(isset($coveredLines[$linenum]) && $coveredLines[$linenum] == 1){
           $class = "covered";
       }else if(isset($coveredLines[$linenum]) && $coveredLines[$linenum] == -1){
           $class ="red";
       }else if(isset($coveredLines[$linenum]) && $coveredLines[$linenum] == -2){
           $class ="orange";
       }
       $html .= '<dd class="' . $class . '">' . htmlspecialchars($line) . '</dd>' . "\n";
    }
    $html .='</dl></div>';
    return $html;
}

if(isset($_GET["file"])){
    $file = $_GET["file"];
    echo '<a href="coverage.php">Back to filelist</a>';
    echo '<a href="cc.php">Back to coverage report</a>';
    echo '<h1>Coverage for ' . $file . '</h1>';
    echo getCoverageReport($file);    

    }else{
echo "<ul>";
$it = new RecursiveDirectoryIterator(Doctrine::getPath());
foreach(new RecursiveIteratorIterator($it) as $file){
    if(strpos($file->getPathname(), ".svn")){
        continue;
     }
    echo '<li><a href="?file=' . $file->getPathname() . '">' . $file->getPathname() . '</a></li>';
}
}
?>
</body>
</html>

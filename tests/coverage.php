<?php
// include doctrine, and register it's autoloader
require_once dirname(__FILE__) . '/../lib/Doctrine.php';
spl_autoload_register(array('Doctrine', 'autoload'));
$path = "/home/bjartka/workspace/doctrine/lib/";

?>
<html>
<head>
<style type="text/css">
    .covered{ background: #eee;}
    
</style>
</head>
<body>
<?

function getCoverageReport($file){
    $coverage = unserialize(file_get_contents("coverage.txt"));
    $html = '<div id="coverage">';
    if(!isset($coverage[$file])){
        $html .= 'No coverage for this file</div>';
        return $html;
    }
    $coveredLines = $coverage[$file]; 
    $fileArray = file($file);
    $html .= '<dl class="filelist">' . "\n";
    foreach($fileArray as $num => $line){
        $linenum = $num+1;
       $html .= '<dt>' . $linenum . '</dt>' . "\n";
       $class ="normal";
       if(isset($coveredLines[$linenum]) && $coveredLines[$linenum] == 1){
           $class = "covered";
       }
       $html .= '<dd class="' . $class . '">' . htmlspecialchars($line) . '</dd>' . "\n";
    }
    $html .='</dl></div>';
    return $html;

}

if(isset($_GET["file"])){
    $file = $_GET["file"];
    echo '<a href="coverage.php">Back to filelist</a>';
    echo '<h1>Coverage for ' . $file . '</h1>';
    echo getCoverageReport($file);    

    }else{
echo "<ul>";
$it = new RecursiveDirectoryIterator($path);
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

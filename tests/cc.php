<?php
require_once dirname(__FILE__) . '/../lib/Doctrine.php';
spl_autoload_register(array('Doctrine', 'autoload'));

$reporter = new Doctrine_Coverage_Report("coverage.txt");
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
<?php

if(isset($_GET["file"])){
    echo '<h1>Coverage for ' . $_GET["file"] . '</h1>';
    echo '<a href="cc.php">Back to coverage report</a>';
    $reporter->showFile($_GET["file"]);
}else{
?>
    <h1>Coverage report for Doctrine</h1>
    <p>Default mode shows results sorted by percentage. This can be changed with GET variables:<br /> <ul><li>order = covered|total|maybe|notcovered|percentage</li><li>desc=true</li></ul></p>
    <table>
     <tr><th></th><th>Percentage</th><th>Total</th><th>Covered</th><th>Maybe</th><th>Not Covered</th><th></th></tr>
<?php
    $reporter->showSummary();
    echo "</table>";
}
?>
</body>
</html>

<?php

/*
 *
 * This class is a mess right now. Will clean it up later perhaps. If we do not 
 * change to simpletest..
 */
class Doctrine_Coverage_Report
{

    const COVERED = 1;
    const MAYBE = -2;
    const NOTCOVERED = -1;

    private $path;
    private $coverage;
    private $key;
    private $covered;
    private $totallines = 0;
    private $totalcovered = 0;
    private $totalmaybe = 0;
    private $totalnotcovered = 0;

    public function __construct($file)
    {
        $result = unserialize(file_get_contents("coverage.txt"));
        $this->path = $result["path"];
        $this->coverage = $result["coverage"];

        $this->sortBy ="percentage"; // default sort
    }

    public function showFile($fileName)
    {
        $key = $this->path . $fileName;
        $html = '<div id="coverage">';
        if( !isset( $this->coverage[$key]))
        {
            echo '<h2>This file has not been tested!</h2>';
        }
        $coveredLines = $this->coverage[$key];
        $fileArray = file(Doctrine::getPath() . "/".$fileName);
        $html .= '<dl class="table-display">' . "\n";
        foreach( $fileArray as $num => $line){
            $linenum = $num+1;
            $html .= '<dt>' . $linenum . '</dt>' . "\n";
            $class ="normal";
            if( isset($coveredLines[$linenum]) && $coveredLines[$linenum] == 1){
                $class = "covered";
            }else if( isset($coveredLines[$linenum]) && $coveredLines[$linenum] == -1){
                $class ="red";
            }else if( isset($coveredLines[$linenum]) && $coveredLines[$linenum] == -2){
                $class ="orange";
            }
            $html .= '<dd class="' . $class . '">' . htmlspecialchars($line) . '</dd>' . "\n";
        }
        $html .='</dl></div>';
        echo $html;
    }

    public function generateNotCoveredFiles()
    {
        $it = new RecursiveDirectoryIterator(Doctrine::getPath());

        $notCoveredArray = array();
        foreach( new RecursiveIteratorIterator($it) as $file){
            if( strpos($file->getPathname(), ".svn")){
                continue;
            }
            $path = Doctrine::getPath() . DIRECTORY_SEPERATOR;
            $coveredPath = str_replace($path, $this->path, $file->getPathname());
            if( isset($this->coverage[$coveredPath])){
                continue;
            }

            $class = str_replace(DIRECTORY_SEPARATOR, '_', substr($file, strlen($this->path), -4));
            if (strpos($class, '_Interface')) {
                continue;
            }

            if(!class_exists($class)){
                continue;
            }

            try{
                $refClass = new ReflectionClass($class);
            }catch(Exception $e){
                echo $e->getMessage();
                continue;
            }
            $lines = 0;
            $methodLines = 0;
            foreach($refClass->getMethods() as $refMethod){

                if($refMethod->getDeclaringClass() != $refClass){
                    continue;
                }
                $methodLines = $refMethod->getEndLine() - $refMethod->getStartLine();
                $lines += $methodLines;
            }
            if($methodLines == 0){
                $notCoveredArray[$class] = array("covered" => 0, "maybe" => 0, "notcovered"=>$lines, "total" => $lines, "percentage" => 100);
             }else{
                $notCoveredArray[$class] = array("covered" => 0, "maybe" => 0, "notcovered"=>$lines, "total" => $lines, "percentage" => 0);
             }
            $this->totallines += $lines;
            $this->totalnotcovered += $lines;
        }
        return $notCoveredArray;
    }

    public function showSummary()
    {
        if(isset($_GET["order"])){
            $this->sortBy = $_GET["order"];
        }
        $coveredArray = $this->generateCoverage();
        $notcoveredArray = $this->generateNotCoveredFiles();
        $coveredArray = array_merge($coveredArray, $notcoveredArray);

        //lets sort it.
        uasort($coveredArray, array($this,"sortArray"));

        //and flip if it perhaps?
        if(isset($_GET["desc"]) && $_GET["desc"] == "true"){
            $coveredArray = array_reverse($coveredArray, true);
        }

        //ugly code to print out the result:
        echo "<tr><td>" . TOTAL . "</td><td>" . round((($this->totalcovered + $this->totalmaybe) / $this->totallines) * 100, 2) . " % </td><td>$this->totallines</td><td>$this->totalcovered</td><td>$this->totalmaybe</td><td>$this->totalnotcovered</td><td></td></tr>";
        foreach($coveredArray as $class => $info){
            $fileName = str_replace("_", "/", $class) . ".php";
            echo "<tr><td>" . $class . "</td><td>" . $info["percentage"] . " % </td><td>" . $info["total"] . "</td><td>" . $info["covered"] . "</td><td>" . $info["maybe"] . "</td><td>" . $info["notcovered"]. "</td><td><a href=\"cc.php?file=" . $fileName . "\">coverage</a></td></tr>";
        }
    }

    public function generateCoverage()
    {
        $coveredArray = array();
        foreach ($this->coverage as $file => $lines) {
            $pos = strpos($file, $this->path);
            if($pos === false && $pos !== 0){
                continue;
            }

            $class = str_replace(DIRECTORY_SEPARATOR, '_', substr($file, strlen($this->path), -4));
            $class = str_replace($this->path, Doctrine::getPath(), $class); 
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
                case self::COVERED:
                    $covered++;
                    break;
                case self::NOTCOVERED:
                    $notcovered++;
                    break;
                case self::MAYBE:
                    $maybe++;
                    break;
                }
            }
            $covered--; //again we have to remove that last line.
            $this->totallines += $total;
            $this->totalcovered += $covered;
            $this->totalnotcovered += $notcovered;
            $this->totalmaybe += $maybe;

            if ($total === 0) {
                $total = 1;
            }
            $percentage = round((($covered + $maybe) / $total) * 100, 2);
            $coveredArray[$class] = array("covered" => $covered, "maybe" => $maybe, "notcovered"=>$notcovered, "total" => $total, "percentage" => $percentage);
        }
        return $coveredArray;
    }

    public function sortArray($a, $b)
    {
        if ($a[$this->sortBy] == $b[$this->sortBy]) {
            return 0;
        }
        return ($a[$this->sortBy] < $b[$this->sortBy]) ? -1 : 1;
    }
}


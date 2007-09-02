<?php
/*
 *  $Id: Doctrine.php 1976 2007-07-11 22:03:47Z zYne $
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information, see
 * <http://www.phpdoctrine.com>.
 */

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


</style>
</head>
<body>
<?php

if (isset($_GET["file"])){
    echo '<h1>Coverage for ' . $_GET["file"] . '</h1>';
    echo '<a href="cc.php">Back to coverage report</a>';
    $reporter->showFile($_GET["file"]);
} else {
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
/**
 * Doctrine
 * the base class of Doctrine framework
 *
 * @package     Doctrine
 * @author      Bjarte S. Karlsen <bjartka@pvv.ntnu.no>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision: 1976 $
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

    /*
     * Create a new coverage report
     *
     * @param string $file The name of the file where coverage data is stored
     *
     */
    public function __construct($file)
    {
        $result = unserialize(file_get_contents("coverage.txt"));
        $this->path = $result["path"];
        $this->coverage = $result["coverage"];

        $this->sortBy ="percentage"; // default sort
    }

    /*
     * Show graphical coverage report for a file
     *
     * @param string $fileName The name of the file to show
     */
    public function showFile($fileName)
    {
        $key = $this->path . $fileName;
        $html = '<div id="coverage">';
        if (!isset( $this->coverage[$key]))
        {
            echo '<h2>This file has not been tested!</h2>';
        }
        $coveredLines = $this->coverage[$key];
        $fileArray = file(Doctrine::getPath() . "/".$fileName);
        $html .= '<table>' . "\n";
        foreach ($fileArray as $num => $line){
            $linenum = $num+1;
            $html .= '<tr><td>' . $linenum . '</td>' . "\n";
            $class ="normal";
            if (isset($coveredLines[$linenum]) && $coveredLines[$linenum] == 1){
                $class = "covered";
            } else if (isset($coveredLines[$linenum]) && $coveredLines[$linenum] == -1) {
                $class ="red";
            } else if (isset($coveredLines[$linenum]) && $coveredLines[$linenum] == -2) {
                $class ="orange";
            }
            
            $line = str_replace(" ", "&nbsp;", htmlspecialchars($line));
            $html .= '<td class="' . $class . '">' . $line . '</td></tr>' . "\n";
        }
        $html .='</table></div>';
        echo $html;
    }

    /*
     * Generate coverage data for non tested files
     *
     * Scans all files and records data for those that are not in the coverage 
     * record.
     *
     * @return array An array with coverage data
     */
    public function generateNotCoveredFiles()
    {
        $it = new RecursiveDirectoryIterator(Doctrine::getPath());

        $notCoveredArray = array();
        foreach (new RecursiveIteratorIterator($it) as $file){
            if (strpos($file->getPathname(), ".svn")){
                continue;
            }
            $path = Doctrine::getPath() . DIRECTORY_SEPARATOR;
            $coveredPath = str_replace($path, $this->path, $file->getPathname());
            if (isset($this->coverage[$coveredPath])){
                continue;
            }

            $class = str_replace($path, "", $file->getPathname());
            $class = str_replace(DIRECTORY_SEPARATOR, "_", $class);
            $class = substr($class, 0,-4);
            if (strpos($class, '_Interface')) {
                continue;
            }

            if ( ! class_exists($class)){
                continue;
            }

            try{
                $refClass = new ReflectionClass($class);
            } catch (Exception $e){
                echo $e->getMessage();
                continue;
            }
            $lines = 0;
            $methodLines = 0;
            foreach ($refClass->getMethods() as $refMethod){

                if ($refMethod->getDeclaringClass() != $refClass){
                    continue;
                }
                $methodLines = $refMethod->getEndLine() - $refMethod->getStartLine();
                $lines += $methodLines;
            }
            if ($methodLines == 0){
                $notCoveredArray[$class] = array("covered" => 0, "maybe" => 0, "notcovered"=>$lines, "total" => $lines, "percentage" => 100);
            } else {
                $notCoveredArray[$class] = array("covered" => 0, "maybe" => 0, "notcovered"=>$lines, "total" => $lines, "percentage" => 0);
            }
            $this->totallines += $lines;
            $this->totalnotcovered += $lines;
        }
        return $notCoveredArray;
    }

    /*
     * Show a summary of all files in Doctrine and their coverage data
     *
     * @uses generateNonCoveredFiles
     * @uses generateCoverage
     */
    public function showSummary()
    {
        if (isset($_GET["order"])){
            $this->sortBy = $_GET["order"];
        }
        $coveredArray = $this->generateCoverage();
        $notcoveredArray = $this->generateNotCoveredFiles();
        $coveredArray = array_merge($coveredArray, $notcoveredArray);

        //lets sort it.
        uasort($coveredArray, array($this,"sortArray"));

        //and flip if it perhaps?
        if (isset($_GET["desc"]) && $_GET["desc"] == "true"){
            $coveredArray = array_reverse($coveredArray, true);
        }

        //ugly code to print out the result:
        echo "<tr><td>" . TOTAL . "</td><td>" . round((($this->totalcovered + $this->totalmaybe) / $this->totallines) * 100, 2) . " % </td><td>$this->totallines</td><td>$this->totalcovered</td><td>$this->totalmaybe</td><td>$this->totalnotcovered</td><td></td></tr>";
        foreach($coveredArray as $class => $info){
            $fileName = str_replace("_", "/", $class) . ".php";
            echo "<tr><td>" . $class . "</td><td>" . $info["percentage"] . " % </td><td>" . $info["total"] . "</td><td>" . $info["covered"] . "</td><td>" . $info["maybe"] . "</td><td>" . $info["notcovered"]. "</td><td><a href=\"cc.php?file=" . $fileName . "\">coverage</a></td></tr>";
        }
    }

    /*
     * Generate coverage data for tested files
     *
     *@return array An array of coverage data
     */
    public function generateCoverage()
    {
        $coveredArray = array();
        foreach ($this->coverage as $file => $lines) {
            $pos = strpos($file, $this->path);
            if ($pos === false && $pos !== 0){
                continue;
            }

            $class = str_replace(DIRECTORY_SEPARATOR, '_', substr($file, strlen($this->path), -4));
            $class = str_replace($this->path, Doctrine::getPath(), $class); 
            if (strpos($class, '_Interface')) {
                continue;
            }

            if ( ! class_exists($class)){
                continue;
            }

            $total = count($lines) -1; //we have to remove one since it always reports the last line as a hit
            $covered = 0;
            $maybe = 0;
            $notcovered = 0;
            foreach ($lines as $result){
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

   /*
    * Uasort function to sort the array by key
    *
    */
    public function sortArray($a, $b)
    {
        if ($a[$this->sortBy] == $b[$this->sortBy]) {
            return 0;
        }
        return ( $a[$this->sortBy] < $b[$this->sortBy]) ? -1 : 1;
    }
}


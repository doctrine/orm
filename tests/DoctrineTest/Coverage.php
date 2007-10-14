<?php
/*
 *  $Id$
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

/**
 * Doctrine_UnitTestCase
 *
 * @package     Doctrine
 * @author      Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author      Bjarte S. Karlsen <bjartka@pvv.ntnu.no>
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 * @category    Object Relational Mapping
 * @link        www.phpdoctrine.com
 * @since       1.0
 * @version     $Revision$
 */

class DoctrineTest_Coverage
{

    const COVERED = 1;
    const MAYBE = -2;
    const NOTCOVERED = -1;

    private $covered;
    private $totallines = 0;
    private $totalcovered = 0;
    private $totalmaybe = 0;
    private $totalnotcovered = 0;
    private $result;

    /*
     * Create a new Coverage object. We read data from a fixed file. 
     */
    public function __construct()
    {
        $this->result = unserialize(file_get_contents($this->getCoverageDir() . "coverage.txt"));
        $this->sortBy ="percentage"; // default sort
    }

    /**
     * Get the directory to store coverage report in
     *
     * @return string The path to store the coverage in
     */
    public function getCoverageDir(){
        $dir = Doctrine::getPath() . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "tests" . DIRECTORY_SEPARATOR . "coverage" . DIRECTORY_SEPARATOR;
        return $dir;
    }

    /*
     * Show a summary of all files in Doctrine and their coverage data
     *
     */
    public function showSummary()
    {
        if ( isset($_GET['order'])){
            $this->sortBy = $_GET['order'];
        }

        if ( ! isset($this->result['data'])){
            die("Impropper coverage report. Please regenerate");
        }

        $coveredArray = $this->result["data"];
        //lets sort it.
        uasort($coveredArray, array($this,"sortArray"));

        //and flip if it perhaps?
        if (isset($_GET["flip"]) && $_GET["flip"] == "true"){
            $coveredArray = array_reverse($coveredArray, true);
        }

        $totals = $this->result["totals"];

        echo '<tr><td>TOTAL</td><td>' , 
            $totals['percentage'] , '%</td><td>' , 
            $totals['lines'] , '</td><td>' , 
            $totals['covered'] , '</td><td>', 
            $totals['maybe'] , '</td><td>',
            $totals['notcovered'] , '</td><td></tr>';

        foreach($coveredArray as $class => $info){
            echo '<tr><td>' . $class . '</td><td>' . $info['percentage'] . ' % </td><td>' . $info['total'] . '</td><td>' . $info['covered'] . '</td><td>' . $info['maybe'] . '</td><td>' . $info['notcovered']. '</td>';
            if ( $info['type'] == "covered") {
                echo '<td><a href="' , $class , '.html">', $class , '</a></td>';
            } else {
                echo '<td>not tested</td>';
            }
            echo '</tr>';
        }
    }


    /**
     * Return the revision the coverage was made against
     *
     *@param int The revision number
     */
    public function getRevision(){
        return $this->result["revision"];
    }

    /**
     * Generate the report.
     *
     * This method will analyze the coverage data and create a data array that 
     * contains information about each of the classes in Doctrine/lib. It will 
     * also generate html files for each file that has coverage data with 
     * information about what lines that are covered. 
     *
     *
     * @uses generateCoverageInfoCoveredFile
     * @uses saveFile
     * @uses generateCoverageInfoNotCoveredFile
     * @uses getCoverageDir
     * @uses calculateTotalPercentage
     *
     */
    public function generateReport(){
        $svn_info = explode(" ", exec("svn info | grep Revision"));
        $this->result["revision"] = $svn_info[1];

        //loop through all files and generate coverage files for them
        $it = new RecursiveDirectoryIterator(Doctrine::getPath());
        $notCoveredArray = array();
        foreach (new RecursiveIteratorIterator($it) as $file){
            if (strpos($file->getPathname(), ".svn")){
                continue;
            } 

            if(strpos($file->getPathname(), "cli.php")){
                continue;
            }

            $class = $this->getClassNameFromFileName($file->getPathname());

            if (strpos($class, '_Interface')) {
                continue;
            }

            if ( ! class_exists($class)){
                continue;
            }
            if (isset($this->result['coverage'][$file->getPathname()])){
                $coverageInfo[$class] = $this->generateCoverageInfoCoveredFile($file->getPathname());
                $this->saveFile($file->getPathname());
            }else{
                $coverageInfo[$class] = $this->generateCoverageInfoNotCoveredFile($class);
            }
        }
        $this->result["totals"] = array(
            "lines" => $this->totallines, 
            "notcovered" => $this->totalnotcovered,
            "covered" => $this->totalcovered, 
            "maybe" => $this->totalmaybe, 
            "percentage" => $this->calculateTotalPercentage());  

        $this->result["data"] = $coverageInfo;

        file_put_contents($this->getCoverageDir() . "coverage.txt", serialize($this->result));

    }

    /**
     *
     * Return the name of a class from its filename.
     *
     * This method simply removes the Doctrine Path and raplces _ with / and 
     * removes .php to get the classname for a file
     *
     * @param string $fileName The name of the file
     * @return string The name of the class
     */
    public function getClassNameFromFileName($fileName){
        $path = Doctrine::getPath() . DIRECTORY_SEPARATOR;
        $class = str_replace($path, "", $fileName);
        $class = str_replace(DIRECTORY_SEPARATOR, "_", $class);
        $class = substr($class, 0,-4);
        return $class;
    }

    /**
     * Calculate total coverage percentage
     *
     *@return double The percetage as a double
     */
    public function calculateTotalPercentage(){
        return round((($this->totalcovered + $this->totalmaybe) / $this->totallines) * 100, 2);
    }

    /**
     * Generate Coverage for a class that is not in the coverage report.
     *
     * This method will simply check if the method has no lines that should be 
     * tested or not. Then it will return data to be stored for later use. 
     *
     * @param string $class The name of a class
     * @return array An associative array with coverage information
     */
    public function generateCoverageInfoNotCoveredFile($class){
        try{
            $refClass = new ReflectionClass($class);
        } catch (Exception $e){
            echo $e->getMessage();
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
        $this->totallines += $lines;
        $this->totalnotcovered += $lines;
        if ($lines == 0){
            return array("covered" => 0, "maybe" => 0, "notcovered"=>$lines, "total" => $lines, "percentage" => 100, "type" => "notcovered");
        } else {
            return  array("covered" => 0, "maybe" => 0, "notcovered"=>$lines, "total" => $lines, "percentage" => 0, "type" => "notcovered");
        }
    }


    /*
     * Save a html report for the given filename 
     *
     * @param string $fileName The name of the file 
     */
    public function saveFile($fileName)
    {
        $className = $this->getClassNameFromFileName($fileName);
        $title = "Coverage for " . $className;
        
        $html = '<html>
    <head>
        <title>' . $title . '</title>
        <style type="text/css">
            .covered{ background: green;}
            .normal{ background: white;}
            .red{ background: red;}
            .orange{ background: #f90;}
       </style>
</head>
<body><h1>' . $title . '</h1><p><a href="index.php">Back to coverage report</a></p>';
        $coveredLines = $this->result["coverage"][$fileName];
        $fileArray = file($fileName);

        $html .= '<table>' . "\n";
        foreach ($fileArray as $num => $line){
            $linenum = $num+1;
            $html .= '<tr><td>' . $linenum . '</td>' . "\n";
            $class ="normal";
            if (isset($coveredLines[$linenum]) && $coveredLines[$linenum] == self::COVERED){
                $class = "covered";
            } else if (isset($coveredLines[$linenum]) && $coveredLines[$linenum] == self::NOTCOVERED) {
                $class ="red";
            } else if (isset($coveredLines[$linenum]) && $coveredLines[$linenum] == self::MAYBE) {
                $class ="orange";
            }

            $line = str_replace(" ", "&nbsp;", htmlspecialchars($line));
            $html .= '<td class="' . $class . '">' . $line . '</td></tr>' . "\n";
        }
        $html .='</table></body></html>';
        file_put_contents($this->getCoverageDir() . $className . ".html",$html);
    }

    /*
     * Generate coverage data for tested file
     *
     *@return array An array of coverage data
     */
    public function generateCoverageInfoCoveredFile($file)
    {
        $lines = $this->result["coverage"][$file];

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
        return array("covered" => $covered, "maybe" => $maybe, "notcovered"=>$notcovered, "total" => $total, "percentage" => $percentage, "type" => "covered");
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
        return ( $a[$this->sortBy] < $b[$this->sortBy]) ? 1 : -1;
    }
}

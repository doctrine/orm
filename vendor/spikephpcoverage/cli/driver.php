<?php
/*
 *  $Id: license.txt 13981 2005-03-16 08:09:28Z eespino $
 *  
 *  Copyright(c) 2004-2006, SpikeSource Inc. All Rights Reserved.
 *  Licensed under the Open Software License version 2.1
 *  (See http://www.spikesource.com/license.html)
*/
?>
<?php
    /**
     * This driver file can be used to initialize and generate PHPCoverage 
     * report when PHPCoverage is used with a non-PHP test tool - like HttpUnit
     * It can, of course, be used for PHP test tools like SimpleTest and PHPUnit
     * 
     * Notes:
     *  * Option parsing courtesy of "daevid at daevid dot com" (http://php.planetmirror.com/manual/en/function.getopt.php)
     *  * Originally contributed by Ed Espino <eespino@spikesource.com>
    */

    if( ! defined("__PHPCOVERAGE_HOME")) {
        define("__PHPCOVERAGE_HOME", dirname(dirname(__FILE__)));
    }
    require_once __PHPCOVERAGE_HOME . "/conf/phpcoverage.conf.php";
    require_once __PHPCOVERAGE_HOME . "/util/Utility.php";

    // ######################################################################
    // ######################################################################

    function usage() {
        global $util;
        echo "Usage: " . $_SERVER['argv'][0] . " <options>\n";
        echo "\n";
        echo "    Options: \n";
        echo "       --phpcoverage-home <path> OR -p <path>    Path to PHPCoverage home (defaults to PHPCOVERAGE_HOME environment property)\n";
        echo "       --init                                    Initialize PHPCoverage Reporting\n";
        echo "       --report                                  Generate PHPCoverage Reports\n";
        echo "       --cleanup                                 Remove existing PHPCoverage data\n";
        echo "     init options \n";
        echo "       --cov-url <url>                           Specify application default url\n";
        echo "       --tmp-dir <path>                          Specify tmp directory location (Defaults to '" . $util->getTmpDir() . "')\n";
        echo "       --cov-file-name <name>                    Specify coverage data file name (Defaults to 'phpcoverage.data.xml')\n";
        echo "     report options \n";
        echo "       --cov-data-files <path1,path2,...>        Coverage data file path [use this instead of --cov-url for a local file path]\n";
        echo "       --report-name <name>                      Report name\n";
        echo "       --report-dir <path>                       Report directory path (Defaults to 'report')\n";
        echo "       --appbase-path <path>                     Application base path (Defaults to PHPCOVERAGE_APPBASE_PATH if specified on the command line)\n";
        echo "       --include-paths <path1,path2,...>         Comma-separated paths to include in code coverage report. (Includes appbase-path by default)\n";
        echo "       --exclude-paths <path1,path2,...>         Comma-separated paths to exclude from code coverage report.\n";
        echo "       --print-summary                           Print coverage report summary to console.\n";
        echo "     other options \n";
        echo "       --verbose OR -v                           Print verbose information\n";
        echo "       --help OR -h                              Display this usage information\n";
        exit;
    }

    //
    // Setup command line argument processing
    //

    $OPTION["p"] = false;
    $OPTION['verbose'] = false;
    $OPTION['init'] = false;
    $OPTION['report'] = false;
    $OPTION['cleanup'] = false;
    $OPTION['cov-url'] = false;
    $OPTION['report-name'] = false;
    $OPTION['report-dir'] = false;
    $OPTION['tmp-dir'] = false;
    $OPTION['cov-file-name'] = false;
    $OPTION['cov-data-files'] = false;
    $OPTION['appbase-path'] = false;

    //
    // loop through our arguments and see what the user selected
    //

    for ($i = 1; $i < $_SERVER["argc"]; $i++) {
        switch($_SERVER["argv"][$i]) {
        case "--phpcoverage-home":
        case "-p":
            $OPTION['p'] = $_SERVER['argv'][++$i];
            break;
        case "-v":
        case "--verbose":
            $OPTION['verbose'] = true;
            break;
        case "--init":
            $OPTION['init'] = true;
            break;
        case "--report":
            $OPTION['report'] = true;
            break;
        case "--cleanup":
            $OPTION['cleanup'] = true;
            break;
        case "--cov-url":
            $OPTION['cov-url'] = $_SERVER['argv'][++$i] . "/" . "phpcoverage.remote.top.inc.php";
            break; 
        case "--tmp-dir":
            $OPTION['tmp-dir'] = $_SERVER['argv'][++$i];
            break;
        case "--cov-file-name":
            $OPTION['cov-file-name'] = $_SERVER['argv'][++$i];
            break;
        case "--cov-data-files":
            $OPTION['cov-data-files'] = $_SERVER['argv'][++$i];
            break;
        case "--report-name":
            $OPTION['report-name'] = $_SERVER['argv'][++$i];
            break; 
        case "--report-dir":
            $OPTION['report-dir'] = $_SERVER['argv'][++$i];
            break; 
        case "--appbase-path":
            $OPTION['appbase-path'] = $_SERVER['argv'][++$i];
            break;
        case "--include-paths":
            $OPTION['include-paths'] = $_SERVER['argv'][++$i];
            break;
        case "--exclude-paths":
            $OPTION['exclude-paths'] = $_SERVER['argv'][++$i];
            break;
        case "--print-summary":
            $OPTION['print-summary'] = true;
            break;
        case "--help":
        case "-h":
            usage();
            break;
        }
    }

    if($OPTION['p'] == false) {
        $OPTION['p'] = __PHPCOVERAGE_HOME;
        if(empty($OPTION['p']) || !is_dir($OPTION['p'])) {
            die("PHPCOVERAGE_HOME does not exist. [" . $OPTION['p'] . "]");
        }
    }

    putenv("PHPCOVERAGE_HOME=" . $OPTION['p']);

    require_once $OPTION['p'] . "/phpcoverage.inc.php";
    require_once PHPCOVERAGE_HOME . "/remote/RemoteCoverageRecorder.php";
    require_once PHPCOVERAGE_HOME . "/reporter/HtmlCoverageReporter.php";

    // Initializations 
    $includePaths = array();
    $excludePaths = array();

    if ( ! $OPTION['cov-url']){
        if( ! $OPTION['report'] && !$OPTION['cov-data-files']) {
            echo "ERROR: No --cov-url option specified.\n";
            exit(1);
        }
    }

    if($OPTION['init']) {
        if( ! $OPTION['tmp-dir']) {
            $OPTION['tmp-dir'] = $util->getTmpDir();
        }
        if( ! $OPTION['cov-file-name']) {
            $OPTION['cov-file-name'] = "phpcoverage.data.xml";
        }
    }

    if($OPTION['report']) {
        if ( ! $OPTION['report-name']){
            echo "ERROR: No --report-name option specified.\n";
            exit(1);
        }

        if( ! $OPTION['report-dir']) {
            if( ! empty($PHPCOVERAGE_REPORT_DIR)) {
                $OPTION["report-dir"] = $PHPCOVERAGE_REPORT_DIR;
            }
            else {
                $OPTION["report-dir"] = "report";
            }
        }

        if(empty($OPTION['appbase-path']) && !empty($PHPCOVERAGE_APPBASE_PATH)) {
            $OPTION['appbase-path'] = realpath($PHPCOVERAGE_APPBASE_PATH);
        }

        if(isset($OPTION['include-paths'])) {
            $includePaths = explode(",", $OPTION['include-paths']);
        }
        if(isset($OPTION['appbase-path']) && !empty($OPTION["appbase-path"])) {
            $includePaths[] = $OPTION['appbase-path'];
        }

        if(isset($OPTION['exclude-paths'])) {
            $excludePaths = explode(",", $OPTION['exclude-paths']);
        }
    }

    if ($OPTION['verbose']){
        echo "Options: " . print_r($OPTION, true) . "\n";
        echo "include-paths: " . print_r($includePaths, true) . "\n";
        echo "exclude-paths: " . print_r($excludePaths, true) . "\n";
    }

    //
    //
    //

    if ($OPTION['init']){
        echo "PHPCoverage: init " . $OPTION['cov-url'] . "?phpcoverage-action=init&cov-file-name=". urlencode($OPTION["cov-file-name"]) . "&tmp-dir=" . urlencode($OPTION['tmp-dir']) . "\n";

        //
        // Initialize the PHPCoverage reporting framework
        //

        file_get_contents($OPTION['cov-url'] . "?phpcoverage-action=init&cov-file-name=". urlencode($OPTION["cov-file-name"]) . "&tmp-dir=" . urlencode($OPTION['tmp-dir']));

    } 
    else if ($OPTION['report']){


        //
        // Retrieve coverage data (xml) from the PHPCoverage reporting framework
        //

        if($OPTION['cov-data-files']) {
            $OPTION['cov-data-fileset'] = explode(",", $OPTION['cov-data-files']);
            foreach($OPTION['cov-data-fileset'] as $covDataFile) {
                if( ! is_readable($covDataFile)) {
                    echo "Error: Cannot read cov-data-file: " . $covDataFile . "\n";
                    exit(1);
                }
                $xmlUrl[] = $covDataFile;
            }
        }
        else {
            echo "PHPCoverage: report " . $OPTION['cov-url'] . "?phpcoverage-action=get-coverage-xml" . "\n";
            $xmlUrl = $OPTION['cov-url'] . "?phpcoverage-action=get-coverage-xml";
        }

        //
        // Configure reporter, and generate the PHPCoverage report
        //

        $covReporter = new HtmlCoverageReporter($OPTION['report-name'], "", $OPTION["report-dir"]);

        //
        // Notice the coverage recorder is of type RemoteCoverageRecorder
        //

        $cov = new RemoteCoverageRecorder($includePaths, $excludePaths, $covReporter);
        $cov->generateReport($xmlUrl, true);
        $covReporter->printTextSummary($OPTION["report-dir"] . "/report.txt");
        // Should the summary be printed to console ?
        if(isset($OPTION['print-summary']) && $OPTION['print-summary']) {
            $covReporter->printTextSummary();
        }

    } 
    else if ($OPTION['cleanup']){

        echo "PHPCoverage: cleanup " . $OPTION['cov-url'] . "?phpcoverage-action=cleanup";
        file_get_contents($OPTION['cov-url'] . "?phpcoverage-action=cleanup");

    }

?>


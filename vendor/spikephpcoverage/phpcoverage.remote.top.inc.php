<?php
/*
 *  $Id: phpcoverage.remote.top.inc.php 14666 2005-03-23 19:39:55Z npac $
 *  
 *  Copyright(c) 2004-2006, SpikeSource Inc. All Rights Reserved.
 *  Licensed under the Open Software License version 2.1
 *  (See http://www.spikesource.com/license.html)
 */
?>
<?php
    if(isset($_REQUEST)){
        $debug = false;
        // Uncomment the line below to permanently turn on debugging.
        // Alternatively, export a variable called phpcoverage-debug before
        // starting the web server.
        $debug = true;
        if(isset($_REQUEST["phpcoverage-debug"]) || 
        isset($_SERVER["phpcoverage-debug"]) || 
        isset($_ENV["phpcoverage-debug"])) {
            $debug = true;
        }
        if($debug) error_log("[phpcoverage.remote.top.inc.php] ################## START ###################");

        $PHPCOVERAGE_HOME = false;
        global $PHPCOVERAGE_HOME;

        $basedir = dirname(__FILE__);
        $this_script = basename(__FILE__);
        $called_script = basename($_SERVER["SCRIPT_FILENAME"]);

        if( ! empty($_REQUEST["PHPCOVERAGE_HOME"])) {
            $PHPCOVERAGE_HOME = $_REQUEST["PHPCOVERAGE_HOME"];
        }
        if(empty($PHPCOVERAGE_HOME)) {
            $env_var = getenv("PHPCOVERAGE_HOME");
            if(empty($env_var)) {
                $msg = "Could not find PHPCOVERAGE_HOME. Please either export it in your environment before starting the web server. Or include PHPCOVERAGE_HOME=<path> in your HTTP request.";
                error_log("[phpcoverage.remote.top.inc.php] FATAL: " . $msg);
                die($msg);
            }
            else {
                $PHPCOVERAGE_HOME = $env_var;
            }
        }

        if(empty($PHPCOVERAGE_HOME) || !is_dir($PHPCOVERAGE_HOME)) {
            $msg = "ERROR: Could not locate PHPCOVERAGE_HOME [$PHPCOVERAGE_HOME]. ";
            $msg .= "Use 'php <filename> PHPCOVERAGE_HOME=/path/to/coverage/home'\n";
            die($msg);
        }


        // Fallback
        if( ! defined("PHPCOVERAGE_HOME")) {
            $include_path = get_include_path();
            set_include_path($PHPCOVERAGE_HOME. ":" . $include_path);
            define('PHPCOVERAGE_HOME', $PHPCOVERAGE_HOME);
        }

        if($debug) error_log("[phpcoverage.remote.top.inc.php] PHPCOVERAGE_HOME=" . $PHPCOVERAGE_HOME);

        // Register the shutdown function to get code coverage results before
        // script exits abnormally.
        register_shutdown_function('spikephpcoverage_before_shutdown');
        require_once PHPCOVERAGE_HOME . "/conf/phpcoverage.conf.php";
        require_once PHPCOVERAGE_HOME . "/util/Utility.php";
        require_once PHPCOVERAGE_HOME . "/remote/RemoteCoverageRecorder.php";
        require_once PHPCOVERAGE_HOME . "/reporter/HtmlCoverageReporter.php";

        global $util;
        $logger = $util->getLogger();

        // Create a distinct hash (may or may not be unique)
        $session_id = md5($_SERVER["REMOTE_ADDR"] . $_SERVER["SERVER_NAME"]);
        $tmpFile = $util->getTmpDir() . "/phpcoverage.session." . $session_id;
        $logger->info("[phpcoverage.remote.top.inc.php] Session id: " . $session_id . " Saved in: " . $tmpFile,
            __FILE__, __LINE__);
        if(file_exists($tmpFile)) {
            $object = file_get_contents($tmpFile);
            $cov = unserialize($object);
            $logger->info("[phpcoverage.remote.top.inc.php] Coverage object found." ,
                __FILE__, __LINE__);
        }
        else {
            $covReporter = new HtmlCoverageReporter(
                "PHPCoverage report",
                "",
                $util->getTmpDir() . "/php-coverage-report"
            );
            $cov = new RemoteCoverageRecorder(array(), array(), $covReporter);
            $object = serialize($cov);
            file_put_contents($tmpFile, $object);
            $logger->info("[phpcoverage.remote.top.inc.php] Stored coverage object found",
                __FILE__, __LINE__);
        }

        if( ! empty($_REQUEST["phpcoverage-action"])) {
            $logger->info("[phpcoverage.remote.top.inc.php] phpcoverage-action=" . strtolower($_REQUEST["phpcoverage-action"]),
                __FILE__, __LINE__);
            switch(strtolower($_REQUEST["phpcoverage-action"])) {
            case "init":
                if( ! empty($_REQUEST["tmp-dir"])) {
                    $cov->setTmpDir($_REQUEST["tmp-dir"]);
                }
                $cov->setCoverageFileName($_REQUEST["cov-file-name"]);
                if( ! $cov->cleanCoverageFile()) {
                    die("Cannot delete existing coverage data.");
                }
                break;

            case "instrument":
                break;

            case "get-coverage-xml":
                $cov->getCoverageXml();
                break;

            case "cleanup":
                if(file_exists($tmpFile) && is_writable($tmpFile)) {
                    unlink($tmpFile);
                    unset($cov);
                    $logger->info("[phpcoverage.remote.top.inc.php] Cleaned up!",
                        __FILE__, __LINE__);
                    return;
                }
                else {
                    $logger->error("[phpcoverage.remote.top.inc.php] Error deleting file: " . $tmpFile,
                        __FILE__, __LINE__);
                }
                break;
            }
        }

        $cov->startInstrumentation();
        $logger->info("[phpcoverage.remote.top.inc.php] Instrumentation turned on.",
            __FILE__, __LINE__);
        $object = serialize($cov);
        file_put_contents($tmpFile, $object);
        $logger->info("[phpcoverage.remote.top.inc.php] BEGIN: " . $called_script,
            __FILE__, __LINE__);
    }

    function spikephpcoverage_before_shutdown() {
        global $cov, $logger;
        $logger->debug("[phpcoverage.remote.top.inc.php::before_shutdown()] Getting code coverage before shutdown: START",
            __FILE__, __LINE__);
        require dirname(__FILE__) . "/phpcoverage.remote.bottom.inc.php";
        $logger->debug("[phpcoverage.remote.top.inc.php::before_shutdown()] Getting code coverage before shutdown: FINISH",
            __FILE__, __LINE__);
    }
?>

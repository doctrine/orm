<?php
    /*
    *  $Id: phpcoverage.inc.php 14670 2005-03-23 21:25:46Z npac $
    *  
    *  Copyright(c) 2004-2006, SpikeSource Inc. All Rights Reserved.
    *  Licensed under the Open Software License version 2.1
    *  (See http://www.spikesource.com/license.html)
    */
?>
<?php
    global $PHPCOVERAGE_REPORT_DIR;
    global $PHPCOVERAGE_HOME;
    global $PHPCOVERAGE_APPBASE_PATH;

    $basedir = dirname(__FILE__);
    for($ii=1; $ii < $argc; $ii++) {
        if(strpos($argv[$ii], "PHPCOVERAGE_REPORT_DIR=") !== false) {
            parse_str($argv[$ii]);
        }
        else if(strpos($argv[$ii], "PHPCOVERAGE_HOME=") !== false) {
            parse_str($argv[$ii]);
        }
        else if(strpos($argv[$ii], "PHPCOVERAGE_APPBASE_PATH=") !== false) {
            parse_str($argv[$ii]);
        }
    }

    if(empty($PHPCOVERAGE_HOME)) {
        $envvar = getenv("PHPCOVERAGE_HOME");
        if(empty($envvar)) {
            $share_home = getenv("LOCAL_CACHE");
            $PHPCOVERAGE_HOME = $share_home . "/common/spikephpcoverage/src/";
        }
        else {
            $PHPCOVERAGE_HOME = $envvar;
        }
    }

    if(empty($PHPCOVERAGE_HOME) || !is_dir($PHPCOVERAGE_HOME)) {
        $msg = "ERROR: Could not locate PHPCOVERAGE_HOME [$PHPCOVERAGE_HOME]. ";
        $msg .= "Use 'php <filename> PHPCOVERAGE_HOME=/path/to/coverage/home'\n";
        die($msg);
    }

    // Fallback
    if(!defined("PHPCOVERAGE_HOME")) {
        $include_path = get_include_path();
        set_include_path($PHPCOVERAGE_HOME. PATH_SEPARATOR . $include_path);
        define('PHPCOVERAGE_HOME', $PHPCOVERAGE_HOME);
    }

    error_log("[phpcoverage.inc.php] PHPCOVERAGE_HOME=" . $PHPCOVERAGE_HOME);
    error_log("[phpcoverage.inc.php] PHPCOVERAGE_REPORT_DIR=" . $PHPCOVERAGE_REPORT_DIR);
    error_log("[phpcoverage.inc.php] PHPCOVERAGE_APPBASE_PATH=" . $PHPCOVERAGE_APPBASE_PATH);

?>

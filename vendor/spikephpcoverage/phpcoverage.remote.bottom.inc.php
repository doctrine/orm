<?php
/*
 *  $Id: phpcoverage.remote.bottom.inc.php 14665 2005-03-23 19:37:50Z npac $
 *  
 *  Copyright(c) 2004-2006, SpikeSource Inc. All Rights Reserved.
 *  Licensed under the Open Software License version 2.1
 *  (See http://www.spikesource.com/license.html)
*/
?>
<?php

    if(isset($_REQUEST)) {
        global $spc_config, $util;
        $logger = $util->getLogger();

        // Create a distinct hash (may or may not be unique)
        $session_id = md5($_SERVER["REMOTE_ADDR"] . $_SERVER["SERVER_NAME"]);
        $tmpFile = $util->getTmpDir() . "/phpcoverage.session." . $session_id;
        $logger->info("[phpcoverage.remote.bottom.inc.php] Session id: " . $session_id,
            __FILE__, __LINE__);

        if( ! isset($cov)) {
            if(file_exists($tmpFile)) {
                $object = file_get_contents($tmpFile);
                $cov = unserialize($object);
                $logger->info("[phpcoverage.remote.bottom.inc.php] Coverage object found: " . $cov, __FILE__, __LINE__);
            }
        }

        if(isset($cov)) {
            // PHPCoverage bottom half
            if( ! isset($called_script)) {
                $called_script = "";
            }
            $logger->info("[phpcoverage.remote.bottom.inc.php] END: " . $called_script,
                __FILE__, __LINE__);
            // Save the code coverage
            $cov->saveCoverageXml();
            $logger->info("[phpcoverage.remote.bottom.inc.php] Saved coverage xml",
                __FILE__, __LINE__);
            $cov->startInstrumentation();
            $logger->info("[phpcoverage.remote.bottom.inc.php] Instrumentation turned on.",
                __FILE__, __LINE__);
            $object = serialize($cov);
            file_put_contents($tmpFile, $object);
            $logger->info("[phpcoverage.remote.bottom.inc.php] ################## END ###################",
                __FILE__, __LINE__);
        }
    }

?>

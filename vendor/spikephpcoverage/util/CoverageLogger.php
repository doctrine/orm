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

    class CoverageLogger {

        private $level;
        private $logLevels = array(
            "LOG_CRITICAL",
            "LOG_ERROR",
            "LOG_WARNING",
            "LOG_NOTICE",
            "LOG_INFO",
            "LOG_DEBUG"
        );

        public function setLevel($level) {
            if( ! is_numeric($level)) {
                for($i = 0; $i < count($this->logLevels); $i++) {
                    if(strcasecmp($this->logLevels[$i], $level) === 0) {
                        $level = $i;
                        break;
                    }
                }
            }
            $this->level = $level;
        }

        public function critical($str, $file="", $line="") {
            if($this->level >= 0) {
                error_log("[CRITICAL] [" . $file . ":" . $line . "] " . $str);
            }
        }

        public function error($str, $file="", $line="") {
            if($this->level >= 1) {
                error_log("[ERROR] [" . $file . ":" . $line . "] " . $str);
            }
        }

        public function warn($str, $file="", $line="") {
            if($this->level >= 2) {
                error_log("[WARNING] [" . $file . ":" . $line . "] " . $str);
            }
        }

        public function notice($str, $file="", $line="") {
            if($this->level >= 3) {
                error_log("[NOTICE] [" . $file . ":" . $line . "] " . $str);
            }
        }

        public function info($str, $file="", $line="") {
            if($this->level >= 4) {
                error_log("[INFO] [" . $file . ":" . $line . "] " . $str);
            }
        }

        public function debug($str, $file="", $line="") {
            if($this->level >= 5) {
                error_log("[DEBUG] [" . $file . ":" . $line . "] " . $str);
            }
        }

        public function getLevelName($level) {
            return $this->logLevels[$level];
        }
    }

    // testing 
    if(isset($_SERVER["argv"][1]) && $_SERVER["argv"][1] == "__main__") {
        $logger = new CoverageLogger();
        for($i = 0; $i < 6; $i++) {
            $logger->setLevel($i);
            error_log("############## Level now: " . $i);
            $logger->debug("");
            $logger->info("");
            $logger->notice("");
            $logger->warn("");
            $logger->error("");
            $logger->critical("");
        }

        error_log("############# With Level Names");
        for($i = 0; $i < 6; $i++) {
            $logger->setLevel($logger->getLevelName($i));
            error_log("############## Level now: " . $logger->getLevelName($i));
            $logger->debug("");
            $logger->info("", __FILE__, __LINE__);
            $logger->notice("");
            $logger->warn("");
            $logger->error("");
            $logger->critical("");
        }
    }
?>

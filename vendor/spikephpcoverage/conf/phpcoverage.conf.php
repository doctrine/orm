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
    global $spc_config;
    
    // Set to 'LOG_DEBUG' for maximum log output
    // Note that the log file size will grow rapidly
    //   with LOG_DEBUG
    $spc_config['log_level']        = 'LOG_NOTICE';
    //$spc_config['log_level']        = 'LOG_DEBUG';

    // file extension to be treated as php files
    // comma-separated list, no space
    $spc_config['extensions']       = array('php', 'tpl', 'inc');

    // temporary directory to save transient files
    $spc_config['tmpdir']           = '/tmp';

    // temporary directory on Windows machines
    $spc_config['windows_tmpdir']   = 'C:/TMP';
?>

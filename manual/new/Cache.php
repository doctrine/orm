<?php

class Cache
{
    protected $_dir;
    protected $_ext;
    protected $_page;
    protected $_file; 
    protected $_timeToLive;

    public function __construct($dir, $ext, $timeToLive)
    {
        $this->_dir = $dir;
        $this->_ext = $ext;
        $this->_timeToLive = $timeToLive;
        
        $this->_page = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $this->_file = $this->_dir . md5($this->_page) . '.' . $this->_ext;  
    }
    
    /**
     * Begins caching the output.
     *
     * @return A boolean value indicating whether a valid cached version of the
     *         page was found and echoed (false), or not (true).
     */
    public function begin()
    {
        $showCache = (file_exists($this->_file) && $this->isValid());
        clearstatcache();
         
        if ($showCache) {
            readfile($this->_file);
            return false;
        } else {
            ob_start();
            return true;
        }
    }
    
    /**
     * Ends caching the output and saves it to a cache file.
     * 
     */
    public function end()
    {
        // Generate a new cache file
        $fp = @fopen($this->_file, 'w');
        
        // Save the contents of output buffer to the file
        @fwrite($fp, ob_get_contents());
        @fclose($fp);
        
        ob_end_flush();
    }

    /**
     * Deletes all files in the cache directory.
     */    
    public function clear()
    {
        if ($handle = opendir($this->dir)) {
            while ($file = readdir($handle)) {
                if ($file !== '.' && $file !== '..') {
                    unlink($this->dir . '/' . $file);
                }
            }
            closedir($handle);
        }
    }
    
    /**
     * This method is used to check whether the cache file is valid to use.
     * 
     * Currently it compares the modification date of the cache file to the
     * time-to-live value.
     * 
     * @return True, if cache file is valid; false otherwise.
     */
    protected function isValid()
    {
        return (time() - filemtime($this->_file)) < $this->_timeToLive;
    }
}
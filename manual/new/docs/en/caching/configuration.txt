There are couple of availible Cache attributes on Doctrine:

    * Doctrine::ATTR_CACHE_SIZE
        
            *  Defines which cache container Doctrine uses
            *  Possible values: Doctrine::CACHE_* (for example Doctrine::CACHE_FILE)
        
    * Doctrine::ATTR_CACHE_DIR
        
        *  cache directory where .cache files are saved
        *  the default cache dir is %ROOT%/cachedir, where
        %ROOT% is automatically converted to doctrine root dir
        
    * Doctrine::ATTR_CACHE_SLAM
        
            *  On very busy servers whenever you start the server or modify files you can create a race of many processes all trying to cache the same file at the same time. This option sets the percentage of processes that will skip trying to cache an uncached file. Or think of it as the probability of a single process to skip caching. For example, setting apc.slam_defense to 75 would mean that there is a 75% chance that the process will not cache an uncached file. So, the higher the setting the greater the defense against cache slams. Setting this to 0 disables this feature
        
    * Doctrine::ATTR_CACHE_SIZE
        
            *  Cache size attribute
        
    * Doctrine::ATTR_CACHE_TTL
        
            *  How often the cache is cleaned
        


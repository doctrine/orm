There are couple of availible Cache attributes on Doctrine:
<ul>
    <li \>Doctrine::ATTR_CACHE_SIZE
        <ul>
            <li \> Defines which cache container Doctrine uses
            <li \> Possible values: Doctrine::CACHE_* (for example Doctrine::CACHE_FILE)
        </ul>
    <li \>Doctrine::ATTR_CACHE_DIR
        <ul>
        <li \> cache directory where .cache files are saved
        <li \> the default cache dir is %ROOT%/cachedir, where
        %ROOT% is automatically converted to doctrine root dir
        </ul>
    <li \>Doctrine::ATTR_CACHE_SLAM
        <ul>
            <li \> On very busy servers whenever you start the server or modify files you can create a race of many processes all trying to cache the same file at the same time. This option sets the percentage of processes that will skip trying to cache an uncached file. Or think of it as the probability of a single process to skip caching. For example, setting apc.slam_defense to 75 would mean that there is a 75% chance that the process will not cache an uncached file. So, the higher the setting the greater the defense against cache slams. Setting this to 0 disables this feature
        </ul>
    <li \>Doctrine::ATTR_CACHE_SIZE
        <ul>
            <li \> Cache size attribute
        </ul>
    <li \>Doctrine::ATTR_CACHE_TTL
        <ul>
            <li \> How often the cache is cleaned
        </ul>
</ul>

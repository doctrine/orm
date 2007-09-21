<?php
class Doctrine_Resource
{
    public static function request($url, $request)
    {   
        $url .= strstr($url, '?') ? '&':'?';
        $url .= http_build_query($request);
        
        return file_get_contents($url);
    }
}
<?php

namespace LaravelRequire\Support;

class FilenameFilter
{
    
    public static $acceptFilters = [
        '/.*\.php$/',
    ];
    
    public static function accept($filename)
    {
        foreach(self::$acceptFilters as $filter) {
            if (preg_match($filter, $filename)==1)
                return true;
        }
            
        return false;
    }
    
}

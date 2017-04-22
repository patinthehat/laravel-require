<?php

namespace LaravelRequire\Support;

class ProjectConfiguration
{
    public function __construct()
    {
        //
    }   

    public function readConfigurationFile()
    {
        return file_get_contents(config_path() . '/app.php');
    }

    public function writeConfigurationFile($contents)
    {
        return file_put_contents(config_path() . '/app.php', $contents);
    }
    
}

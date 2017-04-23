<?php

namespace LaravelRequire\Support;

class ProjectConfiguration
{

   /**
    * Read the contents of the config/app.php file
    * @return string
    */
    public function readConfigurationFile()
    {
        return file_get_contents(config_path() . '/app.php');
    }

    /**
     * Write the data back to config/app.php
     * @param string $contents
     * @return boolean
     */
    public function writeConfigurationFile($contents)
    {
        return file_put_contents(config_path() . '/app.php', $contents);
    }

}

<?php

namespace LaravelRequire\Support\Rules;

use LaravelRequire\Support\Rules\MatchFilenameRuleContract;
use LaravelRequire\Support\Rules\MatchSourceCodeRuleContract;

class ServiceProviderRule implements MatchFilenameRuleContract, MatchSourceCodeRuleContract
{
    /**
     * Try to determine if $filename is a service provider by matching part of its filename.
     * @param unknown $filename
     * @return boolean
     */
    public function filenameMatch($filename)
    {
        //UserVerificationServiceProvider.php

       $result = (preg_match('/[a-zA-Z0-9_]*ServiceProvider\.php/', basename($filename))==1);
       if (!$result)
           $result = ends_with(basename($filename), 'ServiceProvider.php');

       return $result;
    }

    /**
     * Return an array of information if a service provider class was located in the source code
     * being processed.  Returns false if no match was found.
     *
     * @param string $contents
     * @return array|boolean
     */
    public function sourceCodeMatch($contents)
    {
        if (preg_match('/([a-zA-Z0-9_]+)\s+extends\s+([a-zA-Z0-9_\\\]*ServiceProvider)/', $contents, $m)==1) {
            //return $m;
            return ['class'=>$m[1], 'extends'=>$m[2], 'name'=>$m[1], 'type'=>'serviceprovider'];
        }

        return false;
    }
}

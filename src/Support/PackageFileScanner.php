<?php

namespace LaravelRequire\Support;

use LaravelRequire\Support\ClassInformationParser;
use LaravelRequire\Support\ServiceProviderInformation;

class PackageFileScanner
{

    public function scanFile($filename, $rule)
    {
        $contents = file_get_contents($filename);
        $rules = [$rule];
        $info = false;
        $parser = new ClassInformationParser;

        foreach($rules as $rule) {

            $filenameMatch = $rule->filenameMatch($filename);
            if ($filenameMatch!==false) {
                $info = new RegisteredItemInformation();

                $classname  = $parser->getClassnameFromSource($contents);
                $namespace  = $parser->getNamespaceFromSource($contents);
                $info->filename($filename)
                    ->classname($classname)
                    ->namespace($namespace);
                return $info;
            }

            $codeMatch = $rule->sourceCodeMatch($contents);
            if ($codeMatch!==false && !$info) {

                $info = new RegisteredItemInformation();
                $info->classname($codeMatch[1])
                    ->filename($filename)
                    ->extends($codeMatch[2]);

                $namespace  = $parser->getNamespaceFromSource($contents);
                $info->namespace($namespace);
                return $info;
            }
        }
        return $info;
    }

}
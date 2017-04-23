<?php

namespace LaravelRequire\Support\Packages;

use LaravelRequire\Support\ClassInformationParser;
use LaravelRequire\Support\ServiceProviderInformation;
use LaravelRequire\Support\RegisteredItemInformation;

/**
 * Handles scanning the contents of a file.
 */
class PackageFileScanner
{

    /**
     * @param string $filename
     * @param unknown $rule
     * @return boolean|\LaravelRequire\Support\RegisteredItemInformation|\LaravelRequire\Support\RegisteredItemInformation
     */
    public function scanFile($filename, $rule)
    {
        $contents = file_get_contents($filename);
        $rules = [
            $rule
        ];
        $info = false;
        $parser = new ClassInformationParser();

        foreach ($rules as $rule) {

            $filenameMatch = $rule->filenameMatch($filename);
            if ($filenameMatch !== false) {
                $info = new RegisteredItemInformation();

                $classname = $parser->getClassnameFromSource($contents);
                $namespace = $parser->getNamespaceFromSource($contents);
                $info->filename($filename)
                    ->classname($classname)
                    ->namespace($namespace)
                    ->name($classname);

                return $info;
            }

            $codeMatch = $rule->sourceCodeMatch($contents);
            if ($codeMatch !== false && !$info) {
                $codeClassname = $codeMatch['class'];
                $codeExtends = $codeMatch['extends'];
                $codeName = $codeMatch['name'];
                $codeType = $codeMatch['type'];

                $info = new RegisteredItemInformation();
                $info->classname($codeClassname)
                    ->filename($filename)
                    ->extends($codeExtends)
                    ->name($codeName);

                $namespace = $parser->getNamespaceFromSource($contents);
                $info->namespace($namespace);
                return $info;
            }
        }
        return $info;
    }

}
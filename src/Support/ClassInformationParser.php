<?php

namespace LaravelRequire\Support;

class ClassInformationParser
{
    public function __construct()
    {
        //
    }

    public function getNamespaceSection($namespace, $section = 1)
    {
        $parts = explode("\\", "$namespace\\");
        return $parts[max([0,$section - 1])];
    }

    public function getTopLevelNamespace($namespace)
    {
        return $this->getNamespaceSection($namespace, 1);
    }

    public function getClassnameFromSource($code)
    {
        if (preg_match('/\bclass[\s\r\n]*([a-zA-Z0-9_]+)\b/', $code, $m)==1) {
            return $m[1];
        }

        return false;
    }

    public function getNamespaceFromSource($code)
    {
        if (preg_match('/namespace\s*([^;]+);/', $code, $m)==1) {
            return trim($m[1]);
        }

        return false;
    }

}

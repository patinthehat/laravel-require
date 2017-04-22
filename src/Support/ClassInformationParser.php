<?php

namespace LaravelRequire\Support;

/**
 * Handles parsing information from a class file.
 *
 */
class ClassInformationParser
{
    /**
     * Extract a segment of the provided namespace.
     * @param string $namespace
     * @param integer $section
     * @return string
     */
    public function getNamespaceSection($namespace, $section = 1)
    {
        $parts = explode("\\", "$namespace\\");
        return $parts[max([0,$section - 1])];
    }

    /**
     * Get the first segment of the provided namespace, i.e.: App\Test\One
     * returns 'App'.
     * @param string $code
     * @return string|boolean
     */
    public function getTopLevelNamespace($namespace)
    {
        return $this->getNamespaceSection($namespace, 1);
    }

    /**
     * Get the actual classname from provided code.
     * @param string $code
     * @return string|boolean
     */
    public function getClassnameFromSource($code)
    {
        if (preg_match('/\bclass[\s\r\n]*([a-zA-Z0-9_]+)\b/', $code, $m)==1) {
            return $m[1];
        }

        return false;
    }

    /**
     * Get the declared namespace in the provided code
     * @param string $code
     * @return string|boolean
     */
    public function getNamespaceFromSource($code)
    {
        if (preg_match('/namespace\s*([^;]+);/', $code, $m)==1) {
            return trim($m[1]);
        }

        return false;
    }

}

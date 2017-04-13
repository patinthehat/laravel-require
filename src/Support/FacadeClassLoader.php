<?php

namespace LaravelRequire\Support;

class FacadeClassLoader
{
    protected $tempFiles = [];

    public function __construct()
    {
        //
    }

    public function load($filename, $isContent = false)
    {
        if ($isContent) {
            $contents = $filename;
        } else {
            if (!file_exists($filename)) {
                return false;
            }
            $contents = file_get_contents($filename);
        }


        $loaderClassname = $this->getLoaderClassname();
        $loaderfn = $this->getLoaderFilename($loaderClassname);
        $contents = $this->processFacadeFileContents($loaderClassname, $contents);

        $this->writeDataToTempFile($loaderfn, $contents);

        try {
            include_once($loaderfn);
            $facadeName = $loaderClassname::getFacadeName();
        } catch(\Exception $e) {
            return false;
        } finally {
            $this->cleanup();
        }

        return ucfirst($facadeName);
    }

    protected function writeDataToTempFile($filename, $data)
    {
        if (!in_array($filename, $this->tempFiles))
            $this->tempFiles[] = $filename;
        file_put_contents($filename, $data);

        return $this;
    }

    protected function processFacadeFileContents($loaderClassname, $contents)
    {
        $temp = $contents;
        $temp = str_replace('namespace ', '//namespace ', $temp);
        $temp = preg_replace('/use /', '//use ', $temp);
        $temp = preg_replace('/class\s+([a-zA-Z0-9_]+)\s+extends\s+[\\\A-Za-z0-9_]*Facade/', "class $loaderClassname ", $temp);
        $temp = preg_replace('/(protected|private)/', 'public', $temp);
        $temp = preg_replace('/getFacadeAccessor/', 'getFacadeName', $temp);
        //$temp = preg_replace('/protected\s+static\s+function\s+getFacadeAccessor\b/', 'public static function getFacadeName', $temp);

        return $temp;
    }

    protected function getLoaderClassname($refresh = false)
    {
        static $classname = '';
        if ($classname == '' || $refresh === true)
            $classname = (new \ReflectionClass($this))->getShortName().sha1(mt_rand(1, 999999999));

        return $classname;
    }

    protected function getLoaderFilename($classname)
    {
        return "${classname}.laravel-require.facade-loader.php";
    }

    protected function cleanup()
    {
        foreach($this->tempFiles as $file) {
            if (file_exists($file))
                unlink($file);
        }
        $this->tempFiles = [];
        return $this;
    }

}

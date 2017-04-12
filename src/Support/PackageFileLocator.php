<?php

namespace LaravelRequire\Support;

class PackageFileLocator
{
    protected $packageName;

    public function __construct($packageName)
    {
        $this->packageName = $packageName;
    }

    public function locatePackageFiles()
    {
        $fileiterator   = new \RecursiveDirectoryIterator(
                                    $this->getPackagePath($this->packageName),
                                    \FilesystemIterator::KEY_AS_PATHNAME |
                                    \FilesystemIterator::CURRENT_AS_FILEINFO |
                                    \FilesystemIterator::SKIP_DOTS |
                                    \FilesystemIterator::FOLLOW_SYMLINKS
                              );

        $iterator       = new \RecursiveIteratorIterator(
                                    new PackageFilesFilterIterator($fileiterator),
                                    \RecursiveIteratorIterator::SELF_FIRST
                              );
        $result = [];

        foreach ($iterator as $file) {
            if ($file->getExtension() == 'php')
                $result[] = $file;
        }

        return $result;
    }

    public function getPackagePath()
    {
        return base_path() . "/vendor/".strtolower($this->packageName);
    }

    public function packagePathExists()
    {
        return is_dir($this->getPackagePath());
    }



}

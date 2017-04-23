<?php

namespace LaravelRequire\Support\Packages;

/**
 * Handles the locating of files that might contain a service provider or facade.
 *
 */
class PackageFileLocator
{

    /**
     * @var string
     */
    protected $packageName;

    public function __construct($packageName)
    {
        $this->packageName = $packageName;
    }

    /**
     * Recursively scan the package's directory for all files, using a filter
     * to strip out files we know won't have a service provider or Facade.
     * @return \RecursiveIteratorIterator[]
     */
    public function locatePackageFiles()
    {
        $fileiterator   = new \RecursiveDirectoryIterator(
                                    $this->getPackagePath($this->packageName),
                                    \FilesystemIterator::KEY_AS_PATHNAME |
                                    \FilesystemIterator::CURRENT_AS_FILEINFO |
                                    \FilesystemIterator::SKIP_DOTS |
                                    \FilesystemIterator::FOLLOW_SYMLINKS
                              );

        //loop through the file list, and apply a filter, removing files that we know
        //won't contain a Service Provider or Facade.
        $iterator       = new \RecursiveIteratorIterator(
                                    new PackageFilesFilterIterator($fileiterator),
                                    \RecursiveIteratorIterator::SELF_FIRST
                              );
        $result = [];

        //only allow php files
        //TODO Implement FilenameFilter class here
        foreach ($iterator as $file) {
            if ($file->getExtension() == 'php')
                $result[] = $file;
        }

        return $result;
    }

    /**
     * Determine the package's installation directory
     * @return string
     */
    public function getPackagePath()
    {
        return base_path() . "/vendor/".strtolower($this->packageName);
    }

    /**
     * Check to see if the package's path exists.
     * @return boolean
     */
    public function packagePathExists()
    {
        return is_dir($this->getPackagePath());
    }



}

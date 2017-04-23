<?php

namespace LaravelRequire\Support\Packages;


use LaravelRequire\Support\Packages\PackageFileLocator;
use LaravelRequire\Support\Packages\PackageFileScanner;
use LaravelRequire\Exceptions\InvalidPackageNameException;
use LaravelRequire\Support\RegisteredItemInformation;
use LaravelRequire\Support\Rules\FacadeRule;
use LaravelRequire\Support\Rules\ServiceProviderRule;
use LaravelRequire\Support\FacadeClassLoader;

class Packages
{
    public static function validatePackageName($packageName)
    {
        if (preg_match('/[a-zA-Z0-9_\-]+\/[a-zA-Z0-9_\-]+/', $packageName) == 0)
            throw new InvalidPackageNameException("Invalid package name provided: $packageName");
    }

    public static function findPackageFilesToRegister($packageName)
    {
        $locator = new PackageFileLocator($packageName);
        $scanner = new PackageFileScanner();
        $files = $locator->locatePackageFiles();

        foreach ($files as $file) {
            $spInfo = $scanner->scanFile($file->getPathname(), new ServiceProviderRule());
            $fInfo = $scanner->scanFile($file->getPathname(), new FacadeRule(new FacadeClassLoader()));
            if ($fInfo !== false)
                $result[] = $fInfo->type(RegisteredItemInformation::FACADE_TYPE);
            if ($spInfo !== false)
                $result[] = $spInfo->type(RegisteredItemInformation::SERVICE_PROVIDER_TYPE);
        }

        return $result;
    }

}